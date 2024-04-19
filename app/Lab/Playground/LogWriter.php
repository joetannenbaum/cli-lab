<?php

namespace App\Lab\Playground;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;

class LogWriter implements Loopable
{
    use Ticks;

    public function __construct()
    {
        touch(__DIR__ . '/log.log');
    }

    protected function onTick(): void
    {
        $this->onNthTick(10, function () {
            file_put_contents(
                __DIR__ . '/log.log',
                sprintf(
                    '[%s] %s',
                    now()->toDateTimeString(),
                    fake()->words(5, true),
                ) . PHP_EOL,
                FILE_APPEND
            );
        });
    }
}
