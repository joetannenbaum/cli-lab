<?php

namespace App\Lab\Visualizer;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Chewie\Support\Animatable;

class Average implements Loopable
{
    use Ticks;

    public Animatable $value;

    protected array $ticks;

    public function __construct(protected array $info, protected string $property, protected int $chunkNumber, protected int $speed)
    {
        $chunks = collect($this->info)->pluck($this->property)->map(fn ($items) => collect($items)->chunk(3)->get($this->chunkNumber));

        foreach ($this->info as $index => $segment) {
            $chunk = $chunks->get($index);

            $min = $chunk->min();
            $max = $chunk->max();

            $this->ticks[] = [
                'start'    => (int) floor($segment['start'] * 1000),
                'value'    => (int) floor(($chunk->sum() - $min) / ($max - $min) * 100),
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
