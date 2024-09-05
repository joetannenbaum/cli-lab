<?php

namespace App\Lab\Playground;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Chewie\Support\Animatable;

class Ball implements Loopable
{
    use Ticks;

    public Animatable $value;

    public function __construct()
    {
        $this->value = Animatable::fromValue(0)->lowerLimit(0)->upperLimit(20);
    }

    protected function onTick(): void
    {
        $this->value->whenDoneAnimating(fn () => $this->value->toRandom());
    }
}
