<?php

namespace App\Lab\ProngSimple;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use App\Lab\ProngSimple;
use Chewie\Support\Animatable;

class Title implements Loopable
{
    use Ticks;

    public Animatable $value;

    public function __construct(protected ProngSimple $prompt)
    {
        $this->value = Animatable::fromValue(8)->lowerLimit(0);
    }

    public function onTick(): void
    {
        $this->value->animate();
    }

    public function hide()
    {
        $this->value->to(0);
    }
}
