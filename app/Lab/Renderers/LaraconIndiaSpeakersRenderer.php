<?php

declare(strict_types=1);

namespace App\Lab\Renderers;

use App\Lab\LaraconIndiaSpeakers;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use Chewie\Output\Lines;
use Illuminate\Support\Collection;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;

class LaraconIndiaSpeakersRenderer extends Renderer
{
    protected int $width;

    protected int $height;

    protected int $leftColumnWidth;

    protected int $spaceBetweenColumns = 4;

    use Aligns;
    use DrawsScrollbars;
    use DrawsBoxes;
    use DrawsHotkeys;
    use HasMinimumDimensions;

    public function __invoke(LaraconIndiaSpeakers $prompt): string
    {
        return $this->minDimensions(fn () => $this->renderApp($prompt), 70, 20);
    }

    protected function renderApp(LaraconIndiaSpeakers $prompt)
    {
        $this->width = $prompt->terminal()->cols() - 1;
        $this->height = $prompt->terminal()->lines() - 5;

        $speakers = $this->getSpeakerLines($prompt);
        $content = $this->getDetailLines($prompt);

        $lines = Lines::fromColumns([$speakers, $content])->spacing($this->spaceBetweenColumns)->lines();

        $this->padVertically($lines, $this->height)->each($this->line(...));

        $this->newLine();

        if ($prompt->activeArea === 'list') {
            $this->hotkey('↑ ↓', 'Navigate');
            $this->hotkey('Enter', 'Select Speaker');
            $this->hotkey('→', 'Focus Detail');
        } else {
            $this->hotkey('↑ ↓', 'Scroll');
            $this->hotkey('←', 'Back to List');
        }

        $this->centerHorizontally($this->hotkeys(), $this->width)->each($this->line(...));

        return $this;
    }

    protected function getSpeakerLines(LaraconIndiaSpeakers $prompt): Collection
    {
        $speakerNames = collect($prompt->speakers)->pluck('name');

        $longest = $speakerNames->map(fn ($name) => mb_strwidth($name))->max() + 3;

        $this->leftColumnWidth = $longest + $this->spaceBetweenColumns;

        return $speakerNames
            ->map(fn ($name) => ' ' . $name . ' ')
            ->map(fn ($name) => mb_str_pad($name, $longest))
            ->map(fn ($name, $index) => $prompt->currentSpeaker === $index ? '>' . $name : ' ' . $name)
            ->map(fn ($name, $index) => $this->getSpeakerName($prompt, $name, $index));
    }

    protected function getSpeakerName(LaraconIndiaSpeakers $prompt, string $name, int $index): string
    {
        if ($prompt->selectedSpeaker === $index) {
            return $this->bold($this->bgGreen($name));
        }

        if ($prompt->activeArea === 'detail') {
            return $this->dim($name);
        }

        if ($prompt->currentSpeaker === $index) {
            return $this->bold($this->green($name));
        }

        return $name;
    }

    protected function getDetailLines(LaraconIndiaSpeakers $prompt): Collection
    {
        $currentSpeaker = $prompt->speakers[$prompt->selectedSpeaker];

        $nameFormatted = $this->bold($currentSpeaker['name']);
        $linkFormatted = $this->cyan($this->underline($currentSpeaker['twitter']));

        return collect([
            $prompt->activeArea === 'list' ? $this->dim($nameFormatted) : $this->bold($nameFormatted),
            $this->dim($currentSpeaker['title']),
            $prompt->activeArea === 'list' ? $this->dim($linkFormatted) : $this->bold($linkFormatted),
            '',
        ])->concat($this->bio($prompt, $currentSpeaker['bio']));
    }

    protected function bio(LaraconIndiaSpeakers $prompt, string $bio): Collection
    {
        $columnWidth = min(80, $this->width - $this->leftColumnWidth);
        $scrollHeight = $this->height - 4;

        $bio = collect(explode(PHP_EOL, $bio))
            ->filter()
            ->map(fn ($line) => wordwrap(
                string: $line,
                width: $columnWidth - 4,
                cut_long_words: true,
            ))
            ->implode(PHP_EOL . PHP_EOL);

        $bio = collect(explode(PHP_EOL, $bio))->map(
            fn ($line) => $prompt->activeArea === 'list' ? $this->dim($line) : $line
        );

        $prompt->detailScrollPosition = max(min($prompt->detailScrollPosition, $bio->count() - $scrollHeight), 0);

        return $this->scrollbar(
            visible: $bio->slice($prompt->detailScrollPosition, $scrollHeight),
            firstVisible: $prompt->detailScrollPosition,
            height: $scrollHeight,
            total: $bio->count(),
            width: $columnWidth,
        );
    }
}
