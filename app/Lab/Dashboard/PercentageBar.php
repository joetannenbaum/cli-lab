<?php

namespace App\Lab\Dashboard;

use App\Lab\Support\Animation;
use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;

class PercentageBar implements Loopable
{
    use Ticks;

    public Animation $value;

    public function __construct()
    {
        $this->value = Animation::fromValue(25)->lowerLimit(25)->upperLimit(75);
    }

    public function onTick(): void
    {
        $this->value->whenDoneAnimating(function () {
            $this->onNthTick(14, fn () => $this->value->toRandom());
        });
    }
}
