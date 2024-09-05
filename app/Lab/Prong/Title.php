<?php

namespace App\Lab\Prong;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use App\Lab\Prong;
use App\Lab\Support\Animation;

class Title implements Loopable
{
    use Ticks;

    public Animation $value;

    public function __construct(protected Prong $prompt)
    {
        $this->value = Animation::fromValue(8)->lowerLimit(0);
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
