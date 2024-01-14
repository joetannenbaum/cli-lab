<?php

declare(strict_types=1);

namespace App\Lab\Support;

class SSH
{
    public static function command(string $args)
    {
        return 'ssh cli.lab.joe.codes ' . $args;
    }
}
