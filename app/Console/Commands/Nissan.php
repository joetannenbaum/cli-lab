<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Nissan as LabNissan;
use Illuminate\Console\Command;

class Nissan extends Command implements LabCommand
{
    protected $signature = 'lab:nissan';

    protected $description = 'Command description';

    public function handle()
    {
        (new LabNissan)->run();
    }
}
