<?php

namespace App\Lab\Nissan;

use App\Lab\Concerns\Ticks;
use App\Lab\Contracts\Tickable;
use App\Lab\Nissan;
use App\Lab\Support\Animatable;

class EngineTemp implements Tickable
{
    use Ticks;

    public Animatable $value;

    public $revCount = 0;

    public function __construct(protected Nissan $prompt)
    {
        $this->value = Animatable::fromValue(0)->lowerLimit(0)->upperLimit(12)->pauseAfter(20);
    }

    public function onTick(): void
    {
        $this->value->animate();

        if (!$this->value->isAnimating()) {
            $this->value->to($this->prompt->carStarted ? 3 : 0);
        }
    }

    public function startCar()
    {
        $this->value->to(3);
    }

    public function stopCar()
    {
        $this->value->to(0);
    }

    public function rev()
    {
        $this->revCount++;

        if ($this->revCount % 2 === 0) {
            $this->value->toRelative(1);
        }
    }
}
