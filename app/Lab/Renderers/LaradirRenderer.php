<?php

namespace App\Lab\Renderers;

use App\Lab\Concerns\Aligns;
use App\Lab\Concerns\DrawsAscii;
use App\Lab\Concerns\DrawsHotkeys;
use App\Lab\Concerns\DrawsTables;
use App\Lab\Concerns\HasMinimumDimensions;
use App\Lab\Laradir;
use App\Lab\Output\Lines;
use App\Lab\Output\Util;
use Exception;
use Illuminate\Support\Str;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;

class LaradirRenderer extends Renderer
{
    use Aligns;
    use DrawsAscii;
    use DrawsBoxes;
    use DrawsHotkeys;
    use DrawsScrollbars;
    use DrawsTables;
    use HasMinimumDimensions;

    protected int $width;

    protected int $height;

    protected int $maxTextWidth;

    protected array $spinnerFrames = ['⠂', '⠒', '⠐', '⠰', '⠠', '⠤', '⠄', '⠆'];

    public function __invoke(Laradir $prompt): string
    {
        return $this->minDimensions(fn () => $this->renderLaradir($prompt), 110, 30);
    }

    protected function renderLaradir(Laradir $prompt): static
    {
        $this->width = $prompt->terminal()->cols() - 2;
        $this->height = $prompt->terminal()->lines() - 5;
        $this->maxTextWidth = min(80, $this->width);

        if ($prompt->perPage === 0) {
            // Each result is 8 lines, pad to 10 just in case
            $prompt->perPage = (int) floor(($this->height - 10) / 10);

            return $this;
        }

        $this->line($this->bold('Lara') . $this->bold($this->inverse(' dir ')) . $this->dim(' • https://laradir.com/'));

        $this->newLine(2);

        if ($prompt->state === 'search') {
            return $this->renderSearch($prompt);
        }

        if ($prompt->state === 'detail') {
            return $this->renderDetail($prompt);
        }

        if ($prompt->state === 'filter') {
            return $this->renderFilters($prompt);
        }

        return $this;
    }

    public function getResultMeta($result): string
    {
        $meta = [];

        if ($result['availability'] === 'now') {
            $meta[] = $this->green('⏺︎ Available now');
        } else {
            $meta[] = $this->yellow('⏺︎ Available soon');
        }

        if (count($result['levels'])) {
            $meta[] = collect($result['levels'])->map(
                fn ($l) => $this->prompt->filtersFromApi['roles'][$l],
            )->join(', ');
        }

        try {
            $meta[] = $this->prompt->filtersFromApi['locations'][$result['country']] ?? null;
        } catch (Exception) {
            //
        }

        return implode($this->dim(' • '), $meta);
    }

    protected function renderFilters(Laradir $prompt): static
    {
        $firstCol = [];

        $longest = collect($prompt->filters)->map(fn ($k) => strlen($k['key']))->max();

        foreach ($prompt->filters as $i => $filter) {
            $label = Str::of($filter['key'])->ucfirst()->singular()->padRight($longest)->padBoth($longest + 2)->toString();

            if ($i === $prompt->currentFilter) {
                $label = $this->inverse($this->cyan($label));
            }

            if ($prompt->filterFocus === 'categories') {
                $label = $this->bold($label);
            } else {
                $label = $this->dim($label);
            }

            $firstCol[] = $label;
        }

        $secondCol = collect();

        foreach ($prompt->filters[$prompt->currentFilter]['filters'] as $i => $filter) {
            $isHighlighted = $prompt->highlighted === $i;

            if ($prompt->filterFocus === 'categories') {
                $label = $this->dim($filter['value']);
            } elseif ($isHighlighted) {
                $label = $this->bold($filter['value']);
            } else {
                $label = $filter['value'];
            }

            if (in_array($filter['key'], $prompt->selectedFilters[$prompt->filters[$prompt->currentFilter]['key']] ?? [])) {
                $label = $this->green('◼ ') . $label;
            } else {
                $label = $this->dim('◻ ') . $label;
            }

            if ($isHighlighted) {
                $label = $this->cyan('› ') . $label;
            } else {
                $label = '  ' . $label;
            }

            $secondCol->push($label);
        }

        $secondColScroll = $this->scrollbar(
            visible: $secondCol->slice($prompt->firstVisible, $prompt->scroll),
            firstVisible: $prompt->firstVisible,
            height: $prompt->scroll,
            total: $secondCol->count(),
            width: $this->maxTextWidth + 2,
        );

        Lines::fromColumns([$firstCol, $secondColScroll])
            ->spacing(10)
            ->lines()
            ->each(fn ($line) => $this->line($line));

        $this->verticalPadding(3);

        $this->newLine(2);

        $this->hotkey('Enter', 'Apply Filters');
        $this->hotkey('↑ ↓', 'Change selection');
        $this->hotkey('← →', 'Change focus');
        $this->hotkey('Space', 'Toggle selection', $prompt->filterFocus !== 'categories');
        $this->hotkeyQuit();

        foreach ($this->hotkeys() as $line) {
            $this->line($line);
        }

        return $this;
    }

    protected function renderDetail(Laradir $prompt): static
    {
        $title = mb_wordwrap(
            ltrim($prompt->detail['summary']),
            $this->maxTextWidth,
            PHP_EOL,
            true,
        );

        $this->line($this->cyan($this->bold($title)));

        $this->newLine();

        $this->line('https://laradir.com/developers/' . $prompt->detail['uuid']);

        $this->newLine();

        $meta = $this->getResultMeta($prompt->detail);

        $this->line($meta);

        $this->newLine();

        $skills = collect($prompt->detail['technologies']);

        $maxSkills = 10;

        if ($skills->count() > $maxSkills) {
            $skills = Lines::fromColumns($skills->chunk($maxSkills))->spacing(2)->lines();
        }

        $row = [
            collect($prompt->detail['levels'])->map(
                fn ($l) => $prompt->filtersFromApi['roles'][$l],
            )->join(PHP_EOL),
            collect($prompt->detail['types'])->map(
                fn ($l) => $prompt->filtersFromApi['types'][$l],
            )->join(PHP_EOL),
        ];

        $headers = ['Level', 'Open to...'];

        if ($skills->isNotEmpty()) {
            array_unshift($headers, 'Skills');

            array_unshift(
                $row,
                $skills->join(PHP_EOL)
            );
        }

        $this->table([$row], $headers)->each(fn ($l) => $this->line($l));

        $this->newLine();

        $bio = mb_wordwrap(
            ltrim($prompt->detail['bio']),
            $this->maxTextWidth,
            PHP_EOL,
            true
        );

        $bio = collect(explode(PHP_EOL, $bio));

        $lineBreakCount = substr_count($this->output, PHP_EOL);

        $scrollHeight = $this->height - $lineBreakCount - 5;

        $prompt->bioScrollPosition = min($prompt->bioScrollPosition, $bio->count() - $scrollHeight);

        $this->scrollbar(
            visible: $bio->slice($prompt->bioScrollPosition, $scrollHeight),
            firstVisible: $prompt->bioScrollPosition,
            height: $scrollHeight,
            total: $bio->count(),
            width: $this->maxTextWidth + 2,
        )->each(fn ($line) => $this->line($line));

        $this->verticalPadding(3);

        $this->newLine(2);

        $this->hotkey('/', 'Back to search');
        $this->hotkeyQuit();

        foreach ($this->hotkeys() as $line) {
            $this->line($line);
        }

        return $this;
    }

    protected function renderSearch(Laradir $prompt): static
    {
        if ($prompt->searching) {
            $frame = $this->spinnerFrames[$prompt->spinnerCount % count($this->spinnerFrames)];
            $searchIndicator = ' ' . $this->magenta($frame);
        } else {
            $searchIndicator = '';
        }

        $this->line($this->bold('Search Laravel Developers') . $searchIndicator);

        $this->newLine();

        if (count($prompt->selectedFilters)) {
            $filters = collect($prompt->selectedFilters)->map(function ($filters, $key) use ($prompt) {
                return collect($filters)->map(
                    fn ($f) => $this->green('✔︎ ') . $prompt->filtersFromApi[$key][$f],
                );
            })->flatten()->join('  ');

            $this->line($filters);
        } else {
            $this->newLine();
        }

        $this->newLine();

        collect($prompt->items)->each(function ($result, $i) use ($prompt) {
            if ($i > 0) {
                $this->newLine();
                $this->line($this->dim(str_repeat('─', $this->maxTextWidth)));
                $this->newLine();
            }

            $title = $this->bold(
                mb_wordwrap(
                    $this->truncate(ltrim($result['summary']), $this->maxTextWidth),
                    $this->maxTextWidth,
                    PHP_EOL,
                    true,
                ),
            );

            if ($i === $prompt->index) {
                $title = $this->cyan($title);
            } else {
                $title = $this->dim($title);
            }

            $this->line($title);

            $this->newLine();

            $meta = $this->getResultMeta($result);

            if ($i !== $prompt->index) {
                $meta = $this->dim($meta);
            }

            $this->line($meta);

            $this->newLine();

            $bio = mb_wordwrap(
                $this->truncate(
                    str_replace(PHP_EOL, ' ', ltrim($result['bio'])),
                    $this->maxTextWidth * 2
                ),
                $this->maxTextWidth,
                PHP_EOL,
                true
            );

            if ($i !== $prompt->index) {
                $bio = $this->dim($bio);
            }

            $this->line($bio);
        });

        $this->verticalPadding(6);

        $this->newLine(2);

        $total = $prompt->results['meta']['total'] ?? 0;

        if ($total > 0) {
            $dots = Util::range(min($prompt->results['meta']['last_page'] ?? 0, 30))
                ->map(fn ($page) => $page === $prompt->page ? $this->green('⏺︎') : $this->dim('⏺︎'))
                ->join(' ');
        } else {
            $dots = '';
        }

        $this->line($dots);

        $this->newLine(2);

        $this->hotkey('/', 'Search');
        $this->hotkey('↑ ↓', 'Change selection');
        $this->hotkey('←', 'Previous page', $prompt->page > 1);
        $this->hotkey('→', 'Next page', $prompt->page < ($prompt->results['meta']['last_page'] ?? 0));
        $this->hotkey('Enter', 'Select');
        $this->hotkey('c', 'Clear filters', count($prompt->selectedFilters) > 0);
        $this->hotkeyQuit();

        foreach ($this->hotkeys() as $line) {
            $this->line($line);
        }

        return $this;
    }

    protected function verticalPadding(int $padTo): void
    {
        // Count line breaks in current string
        $lineBreaks = substr_count($this->output, PHP_EOL);

        $padding = $this->height - $lineBreaks - $padTo;

        if ($padding > 0) {
            $this->newLine($padding);
        }
    }
}
