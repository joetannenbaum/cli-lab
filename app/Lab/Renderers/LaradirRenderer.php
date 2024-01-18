<?php

namespace App\Lab\Renderers;

use App\Lab\Concerns\Aligns;
use App\Lab\Concerns\DrawsAscii;
use App\Lab\Concerns\DrawsHotkeys;
use App\Lab\Concerns\DrawsTables;
use App\Lab\Laradir;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;
use Illuminate\Support\Str;

class LaradirRenderer extends Renderer
{
    use Aligns;
    use DrawsAscii;
    use DrawsBoxes;
    use DrawsHotkeys;
    use DrawsScrollbars;
    use DrawsTables;

    public function __invoke(Laradir $prompt): string
    {
        foreach ($prompt->filters as $key => $filters) {
            $this->line($this->bold(Str::of($key)->ucfirst()->singular()->toString()));

            foreach ($filters as $value => $label) {
                if (in_array($value, $prompt->selectedFilters[$key] ?? [])) {
                    $this->line($this->dim('● ') . $label);
                    continue;
                }

                $this->line($this->dim('○ ') . $label);
            }

            $this->newLine();
        }

        return $this;
    }
}
