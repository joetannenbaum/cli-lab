<?php

namespace App\Lab;

use App\Http\Integrations\Spotify\Spotify;
use App\Http\Integrations\SpotifyApi\Requests\PauseTrack;
use App\Http\Integrations\SpotifyApi\SpotifyApi;
use App\Lab\Concerns\CreatesAnAltScreen;
use App\Lab\Concerns\RegistersThemes;
use App\Lab\Input\KeyPressListener;
use App\Lab\Integrations\Spotify as IntegrationsSpotify;
use App\Lab\iPod\ImportedPhotos;
use App\Lab\iPod\ImportingPhotos;
use App\Lab\iPod\ImportPhotosInfo;
use App\Lab\iPod\ListPlaylistsScreen;
use App\Lab\iPod\iPodScreen;
use App\Lab\iPod\PlayerScreen;
use App\Lab\Renderers\iPodRenderer;
use Carbon\CarbonInterval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Prompts\Prompt;
use Illuminate\Support\Str;
use Laravel\Prompts\Key;

class iPod extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;

    public int $screenIndex = 0;

    public int $nextScreenIndex = 0;

    public int $frame = 0;

    public int $speed = 10_000;

    public Collection $screens;

    public bool $authed = false;

    public string $authKey;

    public IntegrationsSpotify $spotifyHelper;

    public SpotifyApi $spotify;

    public function __construct()
    {
        $this->registerTheme(iPodRenderer::class);

        $this->authKey = strtoupper(Str::random(10));

        $this->spotifyHelper = new IntegrationsSpotify($this->authKey);

        $this->screens = collect([
            new iPodScreen(
                $this,
                'iPod',
                collect([
                    'Playlists',
                ]),
                collect([
                    'Playlists' => new ListPlaylistsScreen(
                        $this,
                        'Playlists',
                        collect([]),
                        collect([]),
                    ),
                ])
            ),
        ]);

        $this->createAltScreen();
    }

    public function __destruct()
    {
        if ($this->authed) {
            $this->spotify->send(new PauseTrack);
        }

        $this->spotifyHelper->forget();

        $this->exitAltScreen();
    }

    public function run()
    {
        $counter = 0;

        while (true) {
            // TODO: Hide cursor
            $this->render();

            $key = KeyPressListener::once();

            if ($key === 'q' || $key === Key::CTRL_C) {
                $this->terminal()->exit();
                break;
            }

            if ($this->spotifyHelper->connected()) {
                $this->spotifyHelper->forgetKey();

                $this->spotify = new SpotifyApi();
                $this->spotify->authenticate($this->spotifyHelper->authenticator());

                $this->authed = true;
                break;
            }

            $counter++;

            // Don't let anyone sit here for more than 5 minutes
            if ($counter > 60 * 5) {
                $this->terminal()->exit();
                break;
            }

            sleep(1);
        }

        $this->screens->first()->listenForKeys();

        $this->prompt();
    }

    public function onEnter()
    {
        $screen = $this->screens->get($this->screenIndex);

        $key = $screen->items->get($screen->index);

        if (!$screen->mapping->has($key)) {
            return;
        }

        $nextScreen = $screen->mapping->get($key);

        $this->screens->push($nextScreen);
        $this->nextScreenIndex = $this->screens->keys()->last();

        if (method_exists($nextScreen, 'fetch')) {
            $nextScreen->fetch();
        }

        while ($this->nextScreenIndex !== $this->screenIndex) {
            $this->render();
            usleep($this->speed);
        }

        $this->screens->get($this->screenIndex)->listenForKeys();

        $screen = $this->screens->get($this->screenIndex);

        if ($screen instanceof PlayerScreen) {
            $screen->startedAt = time();

            while (true) {
                $this->render();

                $fh = fopen('php://stdin', 'r');
                $read = [$fh];
                $write = null;
                $except = null;

                if (stream_select($read, $write, $except, 0) === 1) {
                    $key = fread($fh, 10);

                    if (!$screen->handleKey($key)) {
                        break;
                    }
                }

                fclose($fh);

                usleep($this->speed);
            }
        }

        if ($screen instanceof ImportingPhotos) {
            $screen->imported = 0;

            while ($screen->imported < $screen->total) {
                $screen->imported++;
                $this->render();
                usleep($this->speed * 80);
            }

            $this->screens[$this->screenIndex] = new ImportedPhotos($this, 'Import Done', collect([
                'Done',
                'Erase Card',
            ]), false);

            $this->screens[2]->items->push('Roll #1 (6)');
            $this->screens[2]->index = 1;

            $this->screens = $this->screens->filter(fn ($screen) => !$screen instanceof ImportPhotosInfo)->values();

            $this->screenIndex = $this->nextScreenIndex = $this->screens->keys()->last();

            $this->screens->get($this->screenIndex)->listenForKeys();

            $this->render();
        }
    }

    public function onBack()
    {
        $this->nextScreenIndex = max($this->screenIndex - 1, 0);

        if ($this->nextScreenIndex === $this->screenIndex) {
            return;
        }

        while ($this->nextScreenIndex !== $this->screenIndex) {
            $this->render();
            usleep($this->speed);
        }

        $this->screens->get($this->screenIndex)->listenForKeys();

        $this->screens->pop();
    }

    public function value(): bool
    {
        return true;
    }
}
