<?php

namespace App\Console\Commands;

use App\Lab\Prong as LabProng;
use Illuminate\Console\Command;

class Prong extends Command
{
    protected $signature = 'lab:prong {gameId?}';

    protected $description = 'Command description';

    public function handle()
    {
        (new LabProng($this->argument('gameId')))->play();
    }
}
