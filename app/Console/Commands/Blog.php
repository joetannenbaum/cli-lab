<?php

namespace App\Console\Commands;

use App\Lab\Blog as LabBlog;
use Illuminate\Console\Command;

class Blog extends Command
{
    protected $signature = 'lab:blog {slug?}';

    protected $description = 'Command description';

    public function handle()
    {
        (new LabBlog)->prompt();
    }
}
