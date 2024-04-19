<?php

namespace App\Console\Commands;

use App\Lab\Playground as LabPlayground;
use Illuminate\Console\Command;

class Playground extends Command
{
    protected $signature = 'lab:playground';

    protected $description = 'Jump in the sandbox.';

    public function handle()
    {
        $value = (new LabPlayground())->prompt();
    }
}
