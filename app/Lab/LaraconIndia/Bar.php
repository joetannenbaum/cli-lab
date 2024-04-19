<?php

namespace App\Lab\LaraconIndia;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Chewie\Support\Animatable;

class Bar implements Loopable
{
    use Ticks;

    public Animatable $value;

    public function __construct(protected int $height)
    {
        $this->value = Animatable::fromValue(-1)->lowerLimit(0)->upperLimit($this->height);

        $this->value->to($this->height);
        $this->value->delay(rand(0, 120));
    }

    public function onTick(): void
    {
        $this->value->whenDoneAnimating(fn () => $this->value->toggle());
    }
}
