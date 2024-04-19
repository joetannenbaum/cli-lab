<?php

namespace App\Console\Commands;

use App\Lab\DirectoryWatcher as LabDirectoryWatcher;
use Illuminate\Console\Command;

class DirectoryWatcher extends Command
{
    protected $signature = 'lab:watch-directory';

    protected $description = 'Watch a directory for changes';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        (new LabDirectoryWatcher(storage_path('chaos')))->watch();
    }
}
