<?php

namespace App\Lab\Renderers;

use App\Lab\Browse;
use App\Lab\Concerns\Aligns;
use App\Lab\Concerns\DrawsAscii;
use App\Lab\Concerns\DrawsHotkeys;
use App\Lab\Concerns\DrawsTables;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;

class BrowseRenderer extends Renderer
{
    use Aligns;
    use DrawsAscii;
    use DrawsBoxes;
    use DrawsHotkeys;
    use DrawsScrollbars;
    use DrawsTables;

    public function __invoke(Browse $prompt): string
    {
        collect($prompt->items)->each(function ($item, $index) use ($prompt) {
            if ($prompt->index === $index) {
                $this->line($this->cyan($this->bold($item['title'])));
                $this->line($item['description']);
                $this->newLine();

                return;
            }

            $this->line($this->bold($item['title']));
            $this->line($this->dim($item['description']));
            $this->newLine();
        });

        $this->hotkey('↑ ↓', 'Change selection');
        $this->hotkey('q', 'Quit');

        $this->newLine();

        collect($this->hotkeys())->each(fn ($line) => $this->line(' ' . $line));

        return $this;
    }
}
