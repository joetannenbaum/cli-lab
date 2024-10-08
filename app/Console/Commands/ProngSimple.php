<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\ProngSimple as LabProngSimple;
use Illuminate\Console\Command;

class ProngSimple extends Command
{
    protected $signature = 'lab:prong-simple';

    protected $description = 'Play a game of Prompts Pong with a friend (or against the computer)';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        (new LabProngSimple())->play();
    }
}
