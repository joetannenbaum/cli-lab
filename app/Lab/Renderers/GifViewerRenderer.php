<?php

namespace App\Lab\Renderers;

use Chewie\Output\Lines;
use App\Lab\GifViewer;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\CapturesOutput;
use Chewie\Concerns\DrawsArt;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;

class GifViewerRenderer extends Renderer
{
    use Aligns;
    use DrawsArt;
    use DrawsHotkeys;
    use HasMinimumDimensions;
    use DrawsBoxes;
    use CapturesOutput;


    public function __invoke(GifViewer $prompt): string
    {
        return $this->minDimensions(fn() => $this->renderBooth($prompt), 20, 20);
    }

    protected function renderBooth(GifViewer $prompt): self
    {
        if ($prompt->state === 'searching') {
            $this->center(collect(
                [$this->dim('search for a gif'), '', $prompt->query]
            ), $prompt->width, $prompt->height)->each($this->line(...));

            return $this;
        }

        if ($prompt->gif) {
            $this->center(
                collect($prompt->gif->frames[$prompt->gif->currentFrame]),
                $prompt->width,
                $prompt->height
            )->each($this->line(...));
        } else {
            $this->center(
                collect(['']),
                $prompt->width,
                $prompt->height
            )->each($this->line(...));
        }

        return $this;
    }
}
