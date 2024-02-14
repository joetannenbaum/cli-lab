<?php

namespace App\Lab\Nissan;

use App\Lab\Concerns\Ticks;
use App\Lab\Contracts\Loopable;
use App\Lab\Nissan;
use App\Lab\Support\Animation;

class Rpm implements Loopable
{
    use Ticks;

    public Animation $value;

    public Animation $rpms;

    public Animation $multiplier;

    public $factor = 2;

    protected $rpmsMultiplier = 3;

    public function __construct(protected Nissan $prompt)
    {
        $this->value = Animation::fromValue(0)->lowerLimit(0)->upperLimit(99);
        $this->rpms = Animation::fromValue(0)->lowerLimit(0)->upperLimit(99);
        $this->multiplier = Animation::fromValue($this->factor)->lowerLimit($this->factor)->upperLimit(38)->step($this->factor)->pauseAfter(10);
    }

    public function onTick(): void
    {
        $this->value->animate();
        $this->rpms->animate();
        $this->multiplier->animate();

        if (!$this->multiplier->isAnimating()) {
            // Cool the engine down
            if ($this->prompt->carStarted) {
                $this->multiplier->to($this->factor * 2);
                $this->value->to(3);
            } else {
                $this->multiplier->to($this->factor);
                $this->value->to(0);
            }

            $this->rpms->to(0);
        }
    }

    public function startCar()
    {
        $this->multiplier->to($this->factor * 2);
        $this->value->to(3);
    }

    public function stopCar()
    {
        $this->multiplier->to($this->factor);
        $this->value->to(0);
    }

    public function rev()
    {
        $this->multiplier->toRelative($this->factor);
        $this->value->toRelative(rand(3, 5));
        $this->rpms->toRelative($this->value->next() * $this->rpmsMultiplier);
    }

    public function brake()
    {
        $this->multiplier->toRelative(-$this->factor);
        $this->value->toRelative(-rand(3, 5));
        $this->rpms->toRelative($this->value->next() * $this->rpmsMultiplier);
    }
}
