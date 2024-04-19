<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Shop as LabShop;
use Illuminate\Console\Command;

class Shop extends Command implements LabCommand
{
    protected $signature = 'lab:shop';

    protected $description = 'Go ahead, go window shopping.';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        (new LabShop())->prompt();
    }
}
