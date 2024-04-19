<?php

namespace App\Console\Commands;

use App\Lab\Visualizer as LabVisualizer;
use Illuminate\Console\Command;

class Visualizer extends Command
{
    protected $signature = 'lab:visualizer';

    protected $description = 'Visualize it, man.';

    public function handle()
    {
        (new LabVisualizer)->run();
    }
}
