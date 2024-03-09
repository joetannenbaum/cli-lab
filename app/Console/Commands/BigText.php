<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\BigText as LabBigText;
use Illuminate\Console\Command;

class BigText extends Command implements LabCommand
{
    protected $signature = 'lab:big-text';

    protected $description = 'Type a message! But big!';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        (new LabBigText())->prompt();
    }
}
