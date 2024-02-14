<?php

namespace App\Lab\Dashboard;

use App\Lab\Support\Frames;
use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;

class HalPulse implements Loopable
{
    use Ticks;

    public Frames $frames;

    public function __construct()
    {
        $this->frames = new Frames;
    }

    public function onTick(): void
    {
        $this->onNthTick(10, fn () => $this->frames->next());
    }
}
