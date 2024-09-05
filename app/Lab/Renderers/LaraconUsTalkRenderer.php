<?php

namespace App\Lab\Renderers;

use Chewie\Output\Lines;
use App\Lab\LaraconUsTalk;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\CapturesOutput;
use Chewie\Concerns\DrawsArt;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;

class LaraconUsTalkRenderer extends Renderer
{
    use Aligns;
    use DrawsArt;
    use DrawsHotkeys;
    use HasMinimumDimensions;
    use DrawsBoxes;
    use CapturesOutput;


    public function __invoke(LaraconUsTalk $prompt): string
    {
        return $this->minDimensions(fn() => $this->renderBooth($prompt), 100, 65);
    }

    protected function renderBooth(LaraconUsTalk $prompt): self
    {
        $this->hotkey('Space', 'Play/Pause');
        $this->hotkey('← →', 'Seek');
        $this->hotkey('↑ ↓', 'Volume');

        $this->center(
            array_merge(
                [

                    'Laracon US Talk (2024) - Joe Tannenbaum',
                    '',
                    $this->underline($this->cyan('https://x.com/joetannenbaum')),
                    '',
                    '',
                ],
                $prompt->currentFrame(),
                [
                    "\e[0m",
                    '',
                ],
                $this->hotkeys(),
                [
                    '',
                    $this->white('Want audio? ') . $this->underline(
                        $this->cyan(
                            url('laracon-us/' . $prompt->channelId)
                        )
                    ),
                ],
            ),
            $prompt->width,
            $prompt->height
        )->each($this->line(...));

        return $this;
    }
}
