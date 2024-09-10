<?php

namespace App\Lab\LaraconAU;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Chewie\Support\Animatable;

class Enemy implements Loopable
{
    use Ticks;

    public Animatable $value;

    public int $x;

    public bool $isHit = false;

    public int $sinceHit = 0;

    public function __construct(protected int $width, protected int $height, protected $onComplete)
    {
        $this->x = rand(0, $width - 1);
        $this->value = Animatable::fromValue(-1)->upperLimit($this->height)->lowerLimit(-1);

        $this->value->to($this->height);
        $this->value->delay(rand(0, 25));
    }

    public function hit()
    {
        $this->isHit = true;
    }

    public function onTick()
    {
        $this->onNthTick(10, function () {
            if ($this->isHit) {
                $this->sinceHit++;

                if ($this->sinceHit === 3) {
                    ($this->onComplete)($this);
                }
            } else {
                $this->value->whenDoneAnimating(fn() => ($this->onComplete)($this));
            }
        });
    }
}
