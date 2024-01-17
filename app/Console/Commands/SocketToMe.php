<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\info;

class SocketToMe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'socket {channel}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = fopen(storage_path('app/sockets/' . $this->argument('channel')), 'r');

        while (true) {

            // Read new lines from file
            $size = filesize(storage_path('app/sockets/' . $this->argument('channel')));

            $contents = fread($file, $size);

            // If there are new lines, send them to the client
            if ($contents) {
                info($contents);
            } else {
                info("No new lines");
            }


            usleep(25_000);
        }
    }
}
