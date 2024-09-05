<?php

namespace App\Lab\ProngSimple;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use App\Lab\ProngSimple;
use Chewie\Support\Animatable;

class Ball implements Loopable
{
    use Ticks;

    public int $y;

    public int $maxY;

    public int $maxX;

    public Animatable $x;

    public int $direction;

    public int $directionChangeCount = 0;

    public int $speed = 1;

    public int $maxSpeed = 4;

    protected array $directionChangeCallbacks = [];

    public array $steps = [];

    public function __construct(protected ProngSimple $prompt)
    {
        // Account for the size of the ball
        $this->maxX = $this->prompt->gameWidth - 1;
        $this->maxY = $this->prompt->gameHeight - 1;

        // Pick a random side to start on
        $xStart = collect([0, $this->maxX])->random();

        $this->x = Animatable::fromValue($xStart)
            ->lowerLimit(0)
            ->upperLimit($this->maxX)
            ->toggle();

        // Starting Y position
        $this->y = rand(0, $this->maxY);
    }

    public function onTick(): void
    {
        $this->x->whenDoneAnimating(function () {
            $this->start();
            $this->x->toggle();
        });

        if (count($this->steps) > 0) {
            $this->y = array_shift($this->steps);
        }
    }

    public function onDirectionChange(callable $cb, int $every = 1, bool $skipFirst = true)
    {
        $this->directionChangeCallbacks[] = [$cb, $every, $skipFirst];
    }

    public function start()
    {
        $nextY = rand(0, $this->maxY);

        $this->steps = $this->getSteps($nextY);
        $this->direction = $this->x->current() === 0  ? 1 : -1;

        foreach ($this->directionChangeCallbacks as $item) {
            [$cb, $every, $skipFirst] = $item;

            if ($skipFirst && $this->directionChangeCount === 0) {
                continue;
            }

            if ($this->directionChangeCount % $every === 0) {
                $cb();
            }
        }

        $this->directionChangeCount++;
    }

    protected function getSteps(int $nextY): array
    {
        $steps = range($this->y, $nextY);

        $i = 0;

        while (count($steps) < $this->maxX) {
            $steps[] = $steps[$i];
            $i++;
        }

        sort($steps);

        if ($nextY < $this->y) {
            return array_reverse($steps);
        }

        return $steps;
    }
}
