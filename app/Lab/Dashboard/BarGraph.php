<?php

namespace App\Lab\Dashboard;

use App\Lab\Support\Animation;
use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Chewie\Output\Util;

class BarGraph implements Loopable
{
    use Ticks;

    public $values = [];

    public function __construct()
    {
        $this->values = Util::range(3)->map(
            fn () => Animation::fromRandom(25, 75),
        )->toArray();
    }

    public function onTick(): void
    {
        foreach ($this->values as $value) {
            $value->whenDoneAnimating(fn () => $value->toRandom());
        }
    }
}
