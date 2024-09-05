<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Stopwatch as LabStopwatch;
use Illuminate\Console\Command;

class Stopwatch extends Command implements LabCommand
{
    protected $signature = 'lab:stopwatch';

    protected $description = 'Go head, take your laptop on a run.';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        (new LabStopwatch())->prompt();
    }
}
