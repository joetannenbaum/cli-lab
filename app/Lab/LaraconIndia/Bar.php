<?php

namespace App\Lab\LaraconIndia;

use App\Lab\Support\Animation;
use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;

class Bar implements Loopable
{
    use Ticks;

    public Animation $value;

    public function __construct(protected int $height)
    {
        $this->value = Animation::fromValue(-1)->lowerLimit(0)->upperLimit($this->height);

        $this->value->to($this->height);
        $this->value->delay(rand(0, 120));
    }

    public function onTick(): void
    {
        $this->value->whenDoneAnimating(fn () => $this->value->current() === 0 ? $this->value->to($this->height) : $this->value->to(0));
    }
}
