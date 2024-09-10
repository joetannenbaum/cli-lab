<?php

namespace App\Lab\Renderers;

use App\Lab\PLACEHOLDER;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use Chewie\Output\Lines;
use Laravel\Prompts\Themes\Default\Renderer;

class PLACEHOLDERRenderer extends Renderer
{
    use Aligns;
    use DrawsHotkeys;
    use HasMinimumDimensions;

    public function __invoke(PLACEHOLDER $prompt): string
    {
        return $this->minDimensions(fn() => $this->renderMessage($prompt), 20, 20);
    }

    protected function renderMessage(PLACEHOLDER $prompt): self
    {
        return $this;
    }
}
