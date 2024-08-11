<?php

namespace App\Lab\Renderers;

use App\Lab\PhotoBooth;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\CapturesOutput;
use Chewie\Concerns\DrawsArt;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;

class PhotoBoothRenderer extends Renderer
{
    use Aligns;
    use DrawsArt;
    use DrawsHotkeys;
    use HasMinimumDimensions;
    use DrawsBoxes;
    use CapturesOutput;


    public function __invoke(PhotoBooth $prompt): string
    {
        return $this->minDimensions(fn() => $this->renderBooth($prompt), 20, 20);
    }

    protected function renderBooth(PhotoBooth $prompt): self
    {
        if (count($prompt->artLines) === 0) {
            $color = $prompt->waitingTicks % 23;
            $color = 23 - $color;
            $color = 255 - $color;

            $this->center("\e[38;5;{$color}mWarming up the camera...\e[39m", $prompt->width, $prompt->height)
                ->each($this->line(...));

            return $this;
        }

        $box = $this->captureOutput(function () use ($prompt) {
            $lines = $prompt->latestFromPhoneCountdown > 0 ? $prompt->latestFromPhone : $prompt->artLines;
            $this->box(
                title: '',
                body: collect($lines)->slice(0, $prompt->boothHeight)->implode(PHP_EOL),
                color: $this->getBoxColor($prompt),
            );

            $this->newLine();

            $this->hotkey('↑ ↓', 'Adjust Contrast');
            $this->hotkey('Space', 'Capture');
            $this->hotkey('o', 'Open Gallery');
            $this->hotkey('r', 'Record');

            $this->centerHorizontally($this->hotkeys(), $prompt->width)
                ->each($this->line(...));
        });


        $this->center($box, $prompt->width, $prompt->height)->each($this->line(...));

        return $this;
    }

    protected function getBoxColor(PhotoBooth $prompt): string
    {
        if ($prompt->recording) {
            return 'red';
        }

        return $prompt->recentlyCaptured ? 'green' : 'gray';
    }
}
