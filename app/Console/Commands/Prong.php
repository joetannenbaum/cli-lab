<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Prong as LabProng;
use Illuminate\Console\Command;

class Prong extends Command implements LabCommand
{
    protected $signature = 'lab:prong {gameId?}';

    protected $description = 'Play a game of Prompts Pong with a friend (or against the computer)';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        (new LabProng($this->argument('gameId')))->play();
    }
}
