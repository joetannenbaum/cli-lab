<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Laradir as LabLaradir;
use Illuminate\Console\Command;

class Laradir extends Command implements LabCommand
{
    protected $signature = 'lab:laradir';

    protected $description = 'Search Laradir developer directory and view profiles';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        (new LabLaradir)->run();
    }
}
