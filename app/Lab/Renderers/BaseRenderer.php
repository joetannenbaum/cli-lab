<?php

namespace App\Lab\Renderers;

use Chewie\Concerns\HasMinimumDimensions;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\Themes\Default\Renderer;

abstract class BaseRenderer extends Renderer
{
    use HasMinimumDimensions;

    protected ?int $minHeight = null;

    protected ?int $minWidth = null;

    public function __invoke(Prompt $prompt): string
    {
        if ($this->minHeight || $this->minWidth) {
            return $this->minDimensions(fn () => $this->render($prompt), $this->minWidth, $this->minHeight);
        }

        return $this->render($prompt);
    }

    abstract protected function render($prompt): string;
}
