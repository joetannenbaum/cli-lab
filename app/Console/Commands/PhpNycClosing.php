<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\PhpNycClosing as LabPhpNycClosing;
use Illuminate\Console\Command;

class PhpNycClosing extends Command implements LabCommand
{
    protected $signature = 'lab:php-x-nyc:closing';

    protected $description = 'Type a message! But big!';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        (new LabPhpNycClosing())->prompt();
    }
}
