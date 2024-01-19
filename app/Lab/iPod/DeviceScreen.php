<?php

namespace App\Lab\iPod;

use App\Http\Integrations\SpotifyApi\Requests\GetDevices;
use App\Http\Integrations\SpotifyApi\Requests\GetPlaylists;
use App\Lab\Input\KeyPressListener;
use App\Lab\iPod;
use Illuminate\Support\Collection;
use Laravel\Prompts\Key;

class DeviceScreen
{
    public int $index = 0;

    public string $id;

    public $devices;

    public function __construct(
        protected iPod $ipod,
        public string $title,
        public Collection $items,
        public Collection $mapping,
        public bool $arrows = true,
    ) {
    }

    public function visible()
    {
        if ($this->index < 8) {
            return $this->items->slice(0, 8);
        }

        return $this->items->slice($this->index - 7, 8);
    }

    public function fetch(): static
    {
        $this->devices = collect($this->ipod->spotify->send(new GetDevices)->json('devices'));

        if ($this->devices->isEmpty()) {
            $this->items->push('No devices found');
            $this->items->push('Open Spotify to play music');
            $this->mapping->put(
                'No devices found',
                new ListPlaylistsScreen(
                    $this->ipod,
                    'Playlists',
                    collect([]),
                    collect([]),
                ),
            );

            $this->mapping->put(
                'Open Spotify to play music',
                new ListPlaylistsScreen(
                    $this->ipod,
                    'Playlists',
                    collect([]),
                    collect([]),
                ),
            );

            return $this;
        }

        $this->devices->each(function ($device) {
            $label = $device['name'] . ' (' . $device['type'] . ')';

            $this->items->push($label);
            $this->mapping->put(
                $label,
                new ListPlaylistsScreen(
                    $this->ipod,
                    'Playlists',
                    collect([]),
                    collect([]),
                ),
            );
        });

        $this->ipod->deviceId = $this->devices->first()['id'] ?? null;

        return $this;
    }

    public function listenForKeys()
    {
        KeyPressListener::for($this->ipod)
            ->clearExisting()
            ->on(
                [Key::UP, Key::UP_ARROW],
                function () {
                    $this->index = max($this->index - 1, 0);
                    $this->ipod->deviceId = $this->devices->get($this->index)['id'] ?? null;
                },
            )
            ->on(
                [Key::DOWN, Key::DOWN_ARROW],
                function () {
                    $this->index = min($this->index + 1, $this->items->count() - 1);
                    $this->ipod->deviceId = $this->devices->get($this->index)['id'] ?? null;
                },
            )
            ->on(
                [Key::LEFT, Key::LEFT_ARROW],
                fn () => $this->ipod->onBack(),
            )
            ->on(
                [Key::ENTER],
                fn () => $this->ipod->onEnter(),
            )
            ->listen();
    }
}
