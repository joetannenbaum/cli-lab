<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Browse as LabBrowse;
use Illuminate\Console\Command;

class Browse extends Command implements LabCommand
{
    protected $signature = 'lab:browse';

    protected $description = 'Command description';

    public function handle()
    {
        (new LabBrowse)->run();
    }
}
