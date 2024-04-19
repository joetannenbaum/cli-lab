<?php

namespace App\Lab\Playground;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Illuminate\Support\Collection;

class LogReader implements Loopable
{
    use Ticks;

    public Collection $value;

    public function __construct()
    {
        $this->value = collect();

        touch(__DIR__ . '/log.log');
    }

    protected function onTick(): void
    {
        $this->onNthTick(10, function () {
            $output = file_get_contents(__DIR__ . '/log.log');

            $this->value = collect(explode(PHP_EOL, $output))->slice(-10);
        });
    }
}
