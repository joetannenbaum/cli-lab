<?php

namespace App\Lab\iPod;

use App\Http\Integrations\SpotifyApi\Requests\GetPlaylist;
use App\Lab\Input\KeyPressListener;
use App\Lab\iPod;
use Illuminate\Support\Collection;
use Laravel\Prompts\Key;

class PlaylistScreen
{
    public int $index = 0;

    public string $id;

    public string $title;

    public function __construct(
        protected iPod $ipod,
        public array $playlist,
        public Collection $items,
        public Collection $mapping,
        public bool $arrows = true,
    ) {
        $this->title = $playlist['name'];
    }

    public function visible()
    {
        if ($this->index < 8) {
            return $this->items->slice(0, 8);
        }

        return $this->items->slice($this->index - 7, 8);
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function fetch(): static
    {
        $result = $this->ipod->spotify->send(new GetPlaylist($this->id));

        collect($result->json('tracks.items'))->each(function ($track, $index) {
            $this->items->push($track['track']['name']);
            $this->mapping->put(
                $track['track']['name'],
                (new PlayerScreen($this->ipod, 'Now Playing', collect([]), collect([])))
                    ->setTrack($track)
                    ->setOffset($index)
                    ->setPlaylistId($this->playlist['uri']),
            );
        });

        return $this;
    }

    public function listenForKeys()
    {
        KeyPressListener::for($this->ipod)
            ->clearExisting()
            ->on(
                [Key::UP, Key::UP_ARROW],
                fn () => $this->index = max($this->index - 1, 0),
            )
            ->on(
                [Key::DOWN, Key::DOWN_ARROW],
                fn () => $this->index = min($this->index + 1, $this->items->count() - 1),
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
