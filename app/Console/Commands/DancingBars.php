<?php

namespace App\Console\Commands;

use App\Lab\DancingBars as LabDancingBars;
use Illuminate\Console\Command;

class DancingBars extends Command
{
    protected $signature = 'lab:dancing-bars';

    protected $description = 'Dance bars! Dance!';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        (new LabDancingBars(storage_path('chaos')))->run();
    }
}
