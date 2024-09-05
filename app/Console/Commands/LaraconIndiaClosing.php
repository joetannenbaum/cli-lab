<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\LaraconIndiaClosing as LabLaraconIndiaClosing;
use Illuminate\Console\Command;

class LaraconIndiaClosing extends Command
{
    protected $signature = 'lab:laracon:india:closing';

    protected $description = 'Type a message! But big!';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        (new LabLaraconIndiaClosing())->prompt();
    }
}
