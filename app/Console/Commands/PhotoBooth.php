<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\PhotoBooth as LabPhotoBooth;
use Illuminate\Console\Command;

class PhotoBooth extends Command implements LabCommand
{
    protected $signature = 'lab:photobooth';

    protected $description = 'Type a message! But big!';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        (new LabPhotoBooth())->prompt();
    }
}
