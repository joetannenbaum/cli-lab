<?php

namespace App\Console\Commands;

use App\Lab\iPod as LabiPod;
use Illuminate\Console\Command;

class iPod extends Command
{
    protected $signature = 'lab:ipod';

    protected $description = 'Command description';

    public function handle()
    {
        (new LabiPod)->run();
    }
}
