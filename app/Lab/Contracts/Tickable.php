<?php

namespace App\Lab\Contracts;

use Laravel\Prompts\Prompt;

interface Tickable
{
    public static function make(Prompt $prompt): static;

    public function tick(): void;
}
