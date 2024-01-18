<?php

namespace App\Console\Commands;

use App\Lab\Laradir as LabLaradir;
use Illuminate\Console\Command;

class Laradir extends Command
{
    protected $signature = 'lab:laradir';

    protected $description = 'Command description';

    public function handle()
    {
        (new LabLaradir)->run();
    }
}
