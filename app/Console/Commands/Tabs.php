<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Tabs as LabTabs;
use Illuminate\Console\Command;

class Tabs extends Command implements LabCommand
{
    protected $signature = 'lab:tabs';

    protected $description = 'Go head, take your laptop on a run.';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        (new LabTabs())->prompt();
    }
}
