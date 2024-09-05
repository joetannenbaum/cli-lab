<?php

namespace App\Lab\LaraconUs;

use App\Lab\Easings;
use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Chewie\Support\Animatable;
use Illuminate\Support\Collection;

class Raindrop implements Loopable
{
    use Ticks;

    public Animatable $value;

    public int $depth;

    public function __construct(public int $totalHeight)
    {
        $this->depth = rand(1, 4);

        $this->value = Animatable::fromValue(0)
            ->upperLimit($this->totalHeight)
            ->lowerLimit(0)
            ->delay(rand(0, 50))
            ->pauseAfter(rand(0, 50));

        $this->value->to($this->totalHeight);
    }

    public function onTick(): void
    {
        $this->onNthTick((int) ceil($this->depth * 2.5), function () {
            $this->value->animate();

            if ($this->value->done()) {
                $this->value->update(0);
                $this->value->to($this->totalHeight);
            }
        });
    }
}
