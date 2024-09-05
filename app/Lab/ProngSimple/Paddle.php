<?php

namespace App\Lab\ProngSimple;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use App\Lab\ProngSimple;
use App\Lab\Support\Animation;

class Paddle implements Loopable
{
    use Ticks;

    public Animation $value;

    public int $height = 5;

    public function __construct(protected ProngSimple $prompt)
    {
        $this->value = Animation::fromValue((int) floor($this->prompt->gameHeight / 2) - (int) floor($this->height / 2))
            ->lowerLimit(0)
            ->upperLimit($this->prompt->gameHeight - $this->height);
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

    public function shrink()
    {
        if ($this->height === 1) {
            return;
        }

        $this->height--;
        $this->value->upperLimit($this->prompt->gameHeight - $this->height);
    }
}
