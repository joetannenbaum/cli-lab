<?php

namespace App\Lab\iPod;

use Chewie\Input\KeyPressListener;
use App\Lab\iPod;
use Illuminate\Support\Collection;
use Laravel\Prompts\Key;

class ImportedPhotos
{
    public int $index = 0;

    public function __construct(
        protected iPod $ipod,
        public string $title,
        public Collection $items,
        public bool $arrows = true,
    ) {
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
                fn () => $this->ipod->onBack(),
            )
            ->listen();
    }
}
