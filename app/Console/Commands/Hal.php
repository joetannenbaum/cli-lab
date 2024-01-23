<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Dashboard;
use Illuminate\Console\Command;

class Hal extends Command implements LabCommand
{
    protected $signature = 'lab:hal';

    protected $description = 'An animated dashboard of an imaginary space ship';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        (new Dashboard())->run();
    }
}
