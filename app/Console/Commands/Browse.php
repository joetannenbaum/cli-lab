<?php

namespace App\Console\Commands;

use App\Lab\Browse as LabBrowse;
use Illuminate\Console\Command;

class Browse extends Command
{
    protected $signature = 'lab:browse';

    protected $description = 'Command description';

    public function handle()
    {
        (new LabBrowse)->run();
    }
}
