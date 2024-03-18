<?php

namespace App\Lab\Renderers;

use App\Lab\Browse;
use App\Lab\Support\SSH;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\CapturesOutput;
use Chewie\Concerns\DrawsArt;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\DrawsTables;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;

use function Chewie\collectionOf;
use function Chewie\stripEscapeSequences;

class BrowseRenderer extends Renderer
{
    use Aligns;
    use CapturesOutput;
    use DrawsArt;
    use DrawsBoxes;
    use DrawsHotkeys;
    use DrawsScrollbars;
    use DrawsTables;

    public function __invoke(Browse $prompt): string
    {
        $this->artLines('cli-lab')->map($this->cyan(...))->each($this->line(...));
        $this->newLine();
        $this->line('by ' . $this->bold($this->cyan('Joe Tannenbaum')));
        $this->line($this->dim('https://twitter.com/joetannenbaum'));

        $this->newLine(2);

        $longestDescription = collect($prompt->items)
            ->map(fn ($page) => collect($page)->pluck('description'))
            ->flatten()
            ->map(fn ($description) => mb_strlen($description))
            ->max() + 2;

        collect($prompt->items[$prompt->browsePage])->each(function ($item, $index) use ($prompt, $longestDescription) {
            $active = $prompt->index === $index;

            $title = $active ? $this->bold($item['name']) : $this->dim($this->bold($item['name']));
            $description = $active ? $item['description'] : $this->dim($item['description']);
            $footer = $active ? $this->dim('> ') . $this->green(SSH::command($item['arg'])) : '';

            $this->box(
                title: $title,
                body: PHP_EOL . $description . str_repeat(' ', $longestDescription - mb_strlen(stripEscapeSequences($description))) . PHP_EOL,
                footer: $footer,
                color: $prompt->index === $index ? 'cyan' : 'gray',
            );

            $this->newLine();
        });

        if (count($prompt->items) > 1) {
            $dots = collectionOf(1, count($prompt->items))
                ->map(fn ($page) => $page === $prompt->browsePage + 1 ? $this->green('•') : $this->dim('•'))
                ->join(' ');

            $this->line($dots);
            $this->newLine(2);

            $this->hotkey('←', 'Previous page', $prompt->browsePage > 0);
            $this->hotkey('→', 'Next page', $prompt->browsePage < count($prompt->items) - 1);
        }

        $this->hotkey('↑ ↓', 'Change selection');
        $this->hotkey('Enter', 'Select');
        $this->hotkey('q', 'Quit');

        $this->newLine();

        $this->hotkeyLines();

        $this->centerHorizontally($this->captureAndResetOutput(), $prompt->terminal()->cols() - 2)->each($this->line(...));

        return $this;
    }
}
