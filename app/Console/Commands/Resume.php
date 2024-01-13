<?php

namespace App\Console\Commands;

use App\Lab\Resume as LabResume;
use Illuminate\Console\Command;

class Resume extends Command
{
    protected $signature = 'lab:resume';

    protected $description = 'Command description';

    public function handle()
    {
        (new LabResume)->run();
    }
}
