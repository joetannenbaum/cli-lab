<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Kanban as LabKanban;
use Illuminate\Console\Command;

class Kanban extends Command implements LabCommand
{
    protected $signature = 'lab:kanban';

    protected $description = 'A simple Kanban board';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        (new LabKanban())->prompt();
    }
}
