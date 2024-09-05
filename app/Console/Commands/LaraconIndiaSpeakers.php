<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\LaraconIndiaSpeakers as LabLaraconIndiaSpeakers;
use Illuminate\Console\Command;

class LaraconIndiaSpeakers extends Command
{
    protected $signature = 'lab:laracon:india:speakers';

    protected $description = 'A directory of speakers at Laracon India.';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        (new LabLaraconIndiaSpeakers())->prompt();
    }
}
