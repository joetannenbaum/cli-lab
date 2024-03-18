<?php

namespace App\Lab\Nissan;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use App\Lab\Nissan;
use App\Lab\Support\Animation;

class OilLevel implements Loopable
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
    }

    public function startCar()
    {
        $this->value->to(10);
    }

    public function stopCar()
    {
        $this->value->to(0);
    }

    public function rev()
    {
        $this->revCount++;

        if ($this->revCount % 10 === 0) {
            $this->value->toRelative(-1);
        }
    }
}
