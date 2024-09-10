<?php

namespace App\Lab\LaraconAU;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Chewie\Support\Animatable;

class Laser implements Loopable
{
    use Ticks;

    public Animatable $value;

    public function __construct(public int $x, protected int $height, protected $onComplete)
    {
        $this->value = Animatable::fromValue($this->height)->upperLimit($this->height)->lowerLimit(0);

        $this->value->to(0);
    }

    public function onTick()
    {
        $this->value->whenDoneAnimating(fn() => ($this->onComplete)($this));
    }
}
