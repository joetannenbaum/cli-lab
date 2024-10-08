<?php

namespace App\Lab\iPod;

use App\Http\Integrations\SpotifyApi\Requests\GetPlaylists;
use Chewie\Input\KeyPressListener;
use App\Lab\iPod;
use Illuminate\Support\Collection;
use Laravel\Prompts\Key;

class ListPlaylistsScreen
{
    public int $index = 0;

    public string $id;

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
        $result = $this->ipod->spotify->send(new GetPlaylists);

        collect($result->json()['items'])->each(function ($playlist) {
            $this->items->push($playlist['name']);
            $this->mapping->put(
                $playlist['name'],
                (new PlaylistScreen($this->ipod, $playlist, collect([]), collect([])))->setId($playlist['id']),
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
