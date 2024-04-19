<?php

namespace App\Lab\Visualizer;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Chewie\Support\Animatable;

class Loudness implements Loopable
{
    use Ticks;

    public Animatable $value;

    protected array $ticks;

    public function __construct(protected array $info, protected string $property, protected int $speed)
    {
        $maxLoudness = collect($this->info)->pluck($this->property)->max();
        $minLoudest = collect($this->info)->pluck($this->property)->min();

        foreach ($this->info as $segment) {
            $this->ticks[] = [
                'start'    => (int) floor($segment['start'] * 1000),
                'value'    => (int) floor(($segment[$this->property] - $minLoudest) / ($maxLoudness - $minLoudest) * 100),
            ];
        }

        $this->value = Animatable::fromValue(0)->upperLimit(100)->lowerLimit(0);
    }

    public function onTick()
    {
        if ($this->tickCount * $this->speed >= $this->ticks[0]['start']) {
            array_shift($this->ticks);
        }

        $this->value->to($this->ticks[0]['value']);
        $this->value->animate();
    }
}
