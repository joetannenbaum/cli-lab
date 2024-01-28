<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Blog as LabBlog;
use Illuminate\Console\Command;

class Blog extends Command implements LabCommand
{
    protected $signature = 'lab:blog {slug?}';

    protected $description = 'A terminal recreation of my blog';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        (new LabBlog($this->argument('slug')))->prompt();
    }
}
