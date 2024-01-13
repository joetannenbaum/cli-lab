<?php

namespace App\Lab\Prong;

use App\Lab\Concerns\Ticks;
use App\Lab\Contracts\Tickable;
use App\Lab\Prong;
use App\Lab\Support\Animatable;

class Title implements Tickable
{
    use Ticks;

    public Animatable $value;

    public function __construct(protected Prong $prompt)
    {
        $this->value = Animatable::fromValue(9)->lowerLimit(0);
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
