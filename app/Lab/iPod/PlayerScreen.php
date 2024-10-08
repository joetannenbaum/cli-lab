<?php

namespace App\Lab\iPod;

use App\Http\Integrations\SpotifyApi\Requests\PlayTrack;
use Chewie\Input\KeyPressListener;
use App\Lab\iPod;
use Illuminate\Support\Collection;
use Laravel\Prompts\Key;

class PlayerScreen
{
    public int $index = 0;

    public bool $playing = true;

    public string $id;

    public $track;

    public $playlistId;

    public int $startedAt;

    public int $offset;

    public function __construct(
        protected iPod $ipod,
        public string $title,
        public Collection $items,
        public Collection $mapping,
        public bool $arrows = true,
    ) {
    }

    // public function setId(string $id): static
    // {
    //     $this->id = $id;

    //     return $this;
    // }

    public function fetch()
    {
        $this->ipod->spotify->send(new PlayTrack(
            $this->playlistId,
            $this->offset,
            $this->ipod->deviceId,
        ));
    }

    public function setTrack($track)
    {
        $this->startedAt = time();
        $this->track = $track;

        return $this;
    }

    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    public function setPlaylistId($id)
    {
        $this->playlistId = $id;

        return $this;
    }

    public function handleKey($key)
    {
        if ($key === Key::LEFT || $key === Key::LEFT_ARROW) {
            // exec('spotify pause');
            $this->ipod->onBack();

            return false;
        }

        // if ($key === Key::RIGHT || $key === Key::RIGHT_ARROW) {
        //     $this->ipod->onEnter();
        // }

        if ($key === Key::CTRL_C) {
            // exec('spotify pause');
            $this->ipod->terminal()->exit();
        }

        return true;
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
