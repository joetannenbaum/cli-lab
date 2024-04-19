<?php

namespace App\Lab\Dashboard;

use App\Lab\Support\Animation;
use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;

use function Chewie\collectionOf;

class Bar implements Loopable
{
    use Ticks;

    public $values = [];

    public function __construct()
    {
        $this->values = Animation::fromRandom(25, 75);
    }

    public function onTick(): void
    {
        foreach ($this->values as $value) {
            $value->whenDoneAnimating(fn () => $value->toRandom());
        }
    }
}
