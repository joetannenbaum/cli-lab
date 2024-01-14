<?php

namespace App\Lab\Concerns;

trait Loops
{
    public array $loopables = [];

    protected int $sleepBetweenLoops = 50_000;

    public function loopable(string $component)
    {
        return $this->loopables[$component];
    }

    public function sleepFor(int $microseconds): static
    {
        $this->sleepBetweenLoops = $microseconds;

        return $this;
    }

    protected function registerLoopable(string $component, ?string $key = null): void
    {
        $this->loopables[$key ?? $component] = new $component($this);
    }

    protected function clearRegisteredLoopables(): void
    {
        $this->loopables = [];
    }

    protected function loop($cb, int $sleepFor = 50_000)
    {
        $this->sleepBetweenLoops = $sleepFor;

        while (true) {
            $continue = $cb($this);

            if ($continue === false) {
                break;
            }

            foreach ($this->loopables as $component) {
                $component->tick();
            }

            usleep($this->sleepBetweenLoops);
        }
    }
}
