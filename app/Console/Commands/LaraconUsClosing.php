<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\LaraconUsClosing as LabLaraconUsClosing;
use Illuminate\Console\Command;

class LaraconUsClosing extends Command implements LabCommand
{
    protected $signature = 'lab:laracon:us:closing';

    protected $description = 'Type a message! But big!';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        (new LabLaraconUsClosing())->prompt();
    }
}
