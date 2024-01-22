<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Blog as LabBlog;
use Illuminate\Console\Command;

class Blog extends Command implements LabCommand
{
    protected $signature = 'lab:blog {slug?}';

    protected $description = 'Command description';

    public function handle()
    {
        (new LabBlog($this->argument('slug')))->prompt();
    }
}
