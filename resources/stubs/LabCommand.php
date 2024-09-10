<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\PLACEHOLDER as LabPLACEHOLDER;
use Illuminate\Console\Command;

class PLACEHOLDER extends Command implements LabCommand
{
    protected $signature = 'lab:signature';

    protected $description = 'Type a message! But big!';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        (new LabPLACEHOLDER())->prompt();
    }
}
