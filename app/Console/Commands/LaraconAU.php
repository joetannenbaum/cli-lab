<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\LaraconAU as LabLaraconAU;
use Illuminate\Console\Command;

class LaraconAU extends Command implements LabCommand
{
    protected $signature = 'lab:laracon-au';

    protected $description = 'Type a message! But big!';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        (new LabLaraconAU())->prompt();
    }
}
