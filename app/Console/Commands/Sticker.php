<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Sticker as LabSticker;
use Illuminate\Console\Command;

class Sticker extends Command implements LabCommand
{
    protected $signature = 'lab:sticker';

    protected $description = 'Support open source, get a sticker. Badabing badaboom.';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        (new LabSticker())->prompt();
    }
}
