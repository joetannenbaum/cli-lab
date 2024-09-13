<?php

namespace App\Lab\Renderers;

use App\Lab\ArtClass;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use Chewie\Output\Lines;
use Laravel\Prompts\Themes\Default\Renderer;

class ArtClassRenderer extends Renderer
{
    use Aligns;
    use DrawsHotkeys;
    use HasMinimumDimensions;

    public function __invoke(ArtClass $prompt): string
    {
        return $this->minDimensions(fn() => $this->renderMessage($prompt), 20, 20);
    }

    protected function renderMessage(ArtClass $prompt): self
    {
        $i = 0;

        while ($i < $prompt->height) {
            $j = 0;
            $line = '';

            while ($j < $prompt->width) {
                if ($prompt->cursorPosition[0] === $j && $prompt->cursorPosition[1] === $i) {
                    if ($prompt->active) {
                        $line .= $this->{$prompt->currentColor}('●');
                    } else {
                        $line .= $this->{$prompt->currentColor}('○');
                    }
                } else if (isset($prompt->art[$i][$j])) {
                    $line .= $this->{$prompt->art[$i][$j]}('■');
                } else {
                    $line .= ' ';
                }
                $j++;
            }

            $this->line($line);
            $i++;
        }


        if ($prompt->lastSavedId) {
            $this->line(
                'Download now: ' . url('art-class/' . $prompt->lastSavedId),
            );
            $this->newLine();
        } else {
            $this->newLine(2);
        }

        $colors = collect($prompt->colors)->map(
            fn($color, $key) => $this->{'bg' . ucwords($color)}(' ' . $key . ' ')
        )->join(' ');

        $label = $prompt->erasing ? ' Erasing ' : ' Drawing ';

        $this->hotkey('Arrow keys', 'Move cursor');
        $this->hotkey('e', 'Toggle erasing mode');
        $this->hotkey('Enter', 'Save');

        if ($prompt->active) {
            $label = $this->bgWhite($label);

            if ($prompt->erasing) {
                $this->hotkey('Space', 'Turn off drawing mode');
            } else {
                $this->hotkey('Space', 'Turn off erasing mode');
            }
        } else {
            if ($prompt->erasing) {
                $this->hotkey('Space', 'Turn on erasing mode');
            } else {
                $this->hotkey('Space', 'Turn on drawing mode');
            }
        }

        $this->line($colors);
        $this->newLine();
        $this->line(
            $this->spaceBetween($prompt->width, implode(' ', $this->hotkeys()), $label)
        );

        return $this;
    }
}
