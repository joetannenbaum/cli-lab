<?php

namespace App\Lab\LaraconUs;

use App\Lab\Easings;
use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Illuminate\Support\Collection;

class BouncingBall implements Loopable
{
    use Ticks;

    public Collection $frames;

    public Collection $prevFrames;

    public int $totalFade = 15;

    protected int $delay = 0;

    protected int $currentDelay = 0;

    protected $frameCount = 0;

    public int $bounceHeight = 0;

    public function __construct(protected int $totalHeight)
    {
        $this->prevFrames = collect();

        $this->delay = rand(0, 1000);
        $this->currentDelay = $this->delay;

        $totalTicks = 75;
        $this->bounceHeight = $this->totalHeight - 4;

        $bounceFrames =  collect(range(1, $totalTicks))
            ->map(function ($i) use ($totalTicks) {
                $percentage = $i / $totalTicks;
                $position = Easings::easeOutBounce($percentage);

                return intval(round($position * $this->bounceHeight));
            });

        $easeBackTicks = $totalTicks - 25;

        $easeBackFrames = collect(range(1, $easeBackTicks))
            ->map(function ($i) use ($easeBackTicks) {
                $percentage = $i / $easeBackTicks;
                $position = Easings::easeInBack($percentage);

                return $this->bounceHeight + intval(round($position * $this->bounceHeight));
            });

        $this->frames = $bounceFrames
            ->merge(array_fill(0, 25, $this->bounceHeight))
            ->merge($easeBackFrames)
            ->filter(fn($frame) => $frame <= $this->totalHeight)
            ->values();
    }

    public function onTick(): void
    {
        $this->currentDelay = max(0, $this->currentDelay - 1);

        if (!$this->visible()) {
            $this->prevFrames->shift();

            return;
        }

        $this->prevFrames->push($this->position());
        $this->prevFrames = $this->prevFrames->slice(- (23 - $this->totalFade))->values();
        $this->frameCount++;

        if ($this->frameCount === $this->frames->count()) {
            $this->currentDelay = $this->delay;
        }
    }

    public function position()
    {
        if (!$this->visible()) {
            return 0;
        }

        return $this->frames[$this->frameCount % $this->frames->count()];
    }

    public function visible()
    {
        return $this->currentDelay === 0;
    }
}
