<?php

declare(strict_types=1);

namespace App\Lab\Support;

class SSH
{
    public static function command(string $args = null)
    {
        $base = 'ssh cli.lab.joe.codes';

        if ($args === null) {
            return $base;
        }

        return $base . ' -t ' . $args;
    }
}
