<?php

namespace App\Lab\Dashboard;

use App\Lab\Support\Animation;
use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;

class Health implements Loopable
{
    use Ticks;

    public Animation $value;

    public function __construct()
    {
        $this->value = Animation::fromValue(50)->lowerLimit(25)->upperLimit(75);
    }

    public function onTick(): void
    {
        $this->value->whenDoneAnimating(function () {
            $this->onNthTick(10, fn () => $this->value->toRandom());
        });
    }
}
