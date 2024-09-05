<?php

namespace App\Lab\Renderers;

use App\Lab\Playground;
use Laravel\Prompts\Themes\Default\Renderer;

class PlaygroundRenderer extends Renderer
{
    public function __invoke(Playground $prompt): string
    {
        $this->line(str_repeat(' ', $prompt->ball->value->current()) . '🏀');
        $this->line(str_repeat(' ', $prompt->ball2->value->current()) . '🏀');
        $this->line(str_repeat(' ', $prompt->ball3->value->current()) . '🏀');
        $this->line(str_repeat(' ', $prompt->ball4->value->current()) . '🏀');

        $prompt->logReader->value->each($this->line(...));

        return $this;
    }
}
