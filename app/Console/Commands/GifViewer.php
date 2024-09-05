<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\GifViewer as LabGifViewer;
use Illuminate\Console\Command;

class GifViewer extends Command
{
    protected $signature = 'lab:gif-viewer';

    protected $description = 'Type a message! But big!';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        (new LabGifViewer())->prompt();
    }
}
