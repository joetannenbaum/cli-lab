<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Nissan as LabNissan;
use Illuminate\Console\Command;

class Nissan extends Command implements LabCommand
{
    protected $signature = 'lab:nissan';

    protected $description = 'A terminal recreation of the dashboard of the Nissan 300 ZX (1984)';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        (new LabNissan)->run();
    }
}
