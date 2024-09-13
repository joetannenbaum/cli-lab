<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\ArtClass as LabArtClass;
use Illuminate\Console\Command;

class ArtClass extends Command implements LabCommand
{
    protected $signature = 'lab:art-class';

    protected $description = 'Type a message! But big!';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        (new LabArtClass())->prompt();
    }
}
