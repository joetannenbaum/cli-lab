<?php

namespace App\Lab\Nissan;

use App\Lab\Concerns\Ticks;
use App\Lab\Contracts\Loopable;
use App\Lab\Nissan;
use App\Lab\Support\Animation;

class Battery implements Loopable
{
    use Ticks;

    public Animation $value;

    public $revCount = 0;

    public function __construct(protected Nissan $prompt)
    {
        $this->value = Animation::fromValue(0)->lowerLimit(0)->upperLimit(12)->pauseAfter(20);
    }

    public function onTick(): void
    {
        $this->value->animate();

        if (!$this->value->isAnimating()) {
            $this->value->to($this->prompt->carStarted ? 12 : 0);
        }
    }

    public function startCar()
    {
        $this->value->to(12);
    }

    public function stopCar()
    {
        $this->value->to(0);
    }

    public function rev()
    {
        $this->revCount++;

        if ($this->revCount % 5 === 0) {
            $this->value->toRelative(-1);
        }
    }
}
