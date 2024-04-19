<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\Blog as LabBlog;
use App\Lab\Termwind as LabTermwind;
use Illuminate\Console\Command;

class Termwind extends Command implements LabCommand
{
    protected $signature = 'lab:termwind';

    protected $description = 'A terminal recreation of my blog';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        (new LabTermwind())->prompt();
    }
}
