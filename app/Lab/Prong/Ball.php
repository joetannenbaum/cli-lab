<?php

namespace App\Lab\Prong;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use App\Lab\Prong;

class Ball implements Loopable
{
    use Ticks;

    public int $y;

    public int $x = 0;

    public int $direction;

    public int $directionChangeCount = 0;

    public int $speed = 1;

    public int $maxSpeed = 1;

    public int $nextSpeed = 1;

    public int $changeSpeedEvery = 6;

    protected $onDirectionChangeCb;

    protected array $steps = [];

    public function __construct(protected Prong $prompt)
    {
        if ($this->prompt->game->playerNumber !== 1) {
            return;
        }

        // Pick a random side to start on
        $this->x = rand(0, 1) === 0 ? 0 : $this->prompt->width - 2;
    }

    public function onTick(): void
    {
        if ($this->prompt->game->playerNumber !== 1) {
            return;
        }

        // $this->speed = $this->nextSpeed;

        if (count($this->steps) === 0) {
            $this->prompt->determineWinner();
            $this->start();
        }

        $this->y = array_shift($this->steps);
        $this->x += $this->direction;
    }

    public function onDirectionChange(callable $cb)
    {
        $this->onDirectionChangeCb = $cb;
    }

    public function start()
    {
        // Account for the size of the ball
        $maxY = $this->prompt->height - 1;

        $this->y ??= rand(0, $maxY);

        $nextY = rand(0, $maxY);

        $steps = range($this->y, $nextY);

        $i = 0;

        while (count($steps) < $this->prompt->width - 2) {
            $steps[] = $steps[$i];
            $i++;
        }

        sort($steps);

        if ($nextY < $this->y) {
            $steps = array_reverse($steps);
        }

        $this->steps = $steps;

        $this->direction = $this->x === 0 ? 1 : -1;

        if ($this->directionChangeCount > 0 && $this->directionChangeCount % $this->changeSpeedEvery === 0) {
            if ($this->speed < $this->maxSpeed) {
                // $this->nextSpeed++;
                // $this->prompt->game->ballSpeed -= 4000;
                // $this->prompt->game->update('ballSpeedLevel', $this->nextSpeed);
            } elseif ($this->prompt->player1->height > 2) {
                $this->prompt->player1->height--;
                $this->prompt->player2->height--;
                $this->prompt->game->update('paddleHeight', $this->prompt->player2->height);
            }
        }

        $this->directionChangeCount++;

        if (isset($this->onDirectionChangeCb)) {
            ($this->onDirectionChangeCb)($this, $nextY);
        }
    }
}
