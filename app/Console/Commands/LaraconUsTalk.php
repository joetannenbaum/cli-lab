<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\LaraconUsTalk as LabLaraconUsTalk;
use Illuminate\Console\Command;

class LaraconUsTalk extends Command implements LabCommand
{
    protected $signature = 'lab:laracon-us-talk';

    protected $description = 'View my talk at Laracon US';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        (new LabLaraconUsTalk())->prompt();
    }
}
