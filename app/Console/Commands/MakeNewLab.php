<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class MakeNewLab extends Command
{
    protected $signature = 'lab:new';

    protected $description = 'Command description';

    public function handle()
    {
        $name = text('What is the name of the lab?');
        $command = text('What is the command signature?', default: Str::slug($name));

        $stubs = [
            'LabCommand' => 'Console/Commands/' . $name,
            'LabApplicationState' => 'Lab/' . $name,
            'LabRenderer' => 'Lab/Renderers/' . $name,
        ];

        foreach ($stubs as $stub => $path) {
            $contents = file_get_contents(resource_path("stubs/{$stub}.php"));
            $contents = str_replace('PLACEHOLDER', $name, $contents);
            $contents = str_replace('lab:signature', 'lab:' . $command, $contents);
            file_put_contents(app_path($path . '.php'), $contents);
        }

        info('Lab created successfully!');
    }
}
