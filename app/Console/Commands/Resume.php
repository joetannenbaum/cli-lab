<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Resume as LabResume;
use Illuminate\Console\Command;

class Resume extends Command implements LabCommand
{
    protected $signature = 'lab:resume';

    protected $description = 'View my resume';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        (new LabResume)->run();
    }
}
