<?php

namespace App\Lab\Renderers;

use Chewie\Output\Lines;
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

        $boxColor = $this->getBoxColor($prompt);

        $box = $this->captureOutput(function () use ($prompt, $boxColor) {
            $lines = $prompt->state === 'editing'  ? $prompt->latestFromPhone : $prompt->artLines;

            $offset = $prompt->state === 'editing' ? $prompt->editingOffset : (int) floor((count($prompt->artLines) - $prompt->boothHeight) / 2);


            $this->box(
                title: '',
                body: collect($lines)->slice($offset, $prompt->boothHeight)->implode(PHP_EOL),
                color: $boxColor,
            );

            // $this->newLine();

            // $this->hotkey('↑ ↓', 'Adjust Contrast');
            // $this->hotkey('Space', 'Capture');
            // $this->hotkey('o', 'Open Gallery');
            // $this->hotkey('r', 'Record');

            // $this->centerHorizontally($this->hotkeys(), $prompt->width)
            //     ->each($this->line(...));
        });
        $boxLines = collect(explode(PHP_EOL, $box));

        $box = $boxLines->map(function ($line, $index) use ($boxColor, $boxLines) {
            if ($boxColor === 'gray' || $index === 0 || $index > $boxLines->count() - 3) {
                return ' ' . $line . '  ';
            }

            return strlen($line) ?  $this->{$boxColor}('█') . $line . ' ' . $this->{$boxColor}('█') : $line;
        });

        $this->center($box, $prompt->width, $prompt->height)->each($this->line(...));

        return $this;
    }

    protected function getBoxColor(PhotoBooth $prompt): string
    {
        if ($prompt->recording) {
            return 'red';
        }

        if ($prompt->state === 'editing') {
            return 'yellow';
        }

        return $prompt->recentlyCaptured ? 'green' : 'gray';
    }
}
