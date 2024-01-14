<?php

namespace App\Lab\Prong;

use App\Lab\Concerns\Ticks;
use App\Lab\Contracts\Tickable;
use App\Lab\Prong;
use App\Lab\Support\Animatable;

class Paddle implements Tickable
{
    use Ticks;

    public Animatable $value;

    public int $height = 5;

    public function __construct(protected Prong $prompt)
    {
        $this->value = Animatable::fromValue((int) floor($prompt->height / 2) - (int) floor($this->height / 2))
            ->lowerLimit(0)
            ->upperLimit($this->prompt->height - $this->height);
    }

    public function onTick(): void
    {
        $this->value->animate();
    }

    public function moveUp()
    {
        $this->value->toRelative(-1);
    }

    public function moveDown()
    {
        $this->value->toRelative(1);
    }
}
