<?php

namespace App\Lab\Nissan;

use App\Lab\Concerns\Ticks;
use App\Lab\Contracts\Tickable;
use App\Lab\Nissan;
use App\Lab\Support\Animatable;

class Fuel implements Tickable
{
    use Ticks;

    public Animatable $value;

    public $revCount = 0;

    public function __construct(protected Nissan $prompt)
    {
        $this->value = Animatable::fromValue(0)->lowerLimit(0)->upperLimit(13)->pauseAfter(20);
    }

    public function onTick(): void
    {
        $this->value->animate();
    }

    public function startCar()
    {
        $this->value->to(13);
    }

    public function stopCar()
    {
        $this->value->to(0);
    }

    public function rev()
    {
        $this->revCount++;

        if ($this->revCount % 7 === 0) {
            $this->value->toRelative(-1);
        }
    }
}
