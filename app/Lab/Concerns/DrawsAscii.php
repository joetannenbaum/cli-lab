<?php

namespace App\Lab\Concerns;

use Illuminate\Support\Collection;

trait DrawsAscii
{
    protected function asciiLines(string $path): Collection
    {
        return collect(explode(PHP_EOL, file_get_contents(storage_path('ascii/' . $path . '.txt'))));
    }
}
