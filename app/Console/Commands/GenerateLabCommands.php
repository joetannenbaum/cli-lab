<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\StructureDiscoverer\Discover;

class GenerateLabCommands extends Command
{
    protected $signature = 'lab:generate-commands';

    protected $description = 'Generate commands JSON for the SSH server to read';

    public function handle()
    {
        $commands = Discover::in(__DIR__)->classes()->implementing(LabCommand::class)->get();

        $commandInfo = collect($commands)->map(fn ($command) => app($command))->map(fn (Command $c) => [
            'class'       => get_class($c),
            'name'        => $c->displayName ?? collect(explode('\\', get_class($c)))->last(),
            'command'     => collect(explode(' ', invade($c)->signature))->first(),
            'description' => invade($c)->description,
        ])->map(fn ($c) => array_merge($c, [
            'arg' => str_replace('lab:', '', $c['command']),
        ]));

        [$defaultCommand, $finalCommands] = $commandInfo->partition(fn ($c) => $c['name'] === 'Browse');

        $defaultCommand = $defaultCommand->first();
        $finalCommands = $finalCommands->sortBy('name')->values();

        File::put(storage_path('app/lab-commands.json'), json_encode([
            'default'  => $defaultCommand,
            'commands' => $finalCommands->toArray(),
        ]));
    }
}
