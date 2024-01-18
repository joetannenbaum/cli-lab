<?php

namespace App\Console\Commands;

use App\Lab\Nissan as LabNissan;
use Illuminate\Console\Command;

class Nissan extends Command
{
    protected $signature = 'lab:nissan';

    protected $description = 'Command description';

    public function handle()
    {
        (new LabNissan)->run();
    }
}
