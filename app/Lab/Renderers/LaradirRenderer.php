<?php

namespace App\Lab\Renderers;

use App\Lab\Concerns\Aligns;
use App\Lab\Concerns\DrawsAscii;
use App\Lab\Concerns\DrawsHotkeys;
use App\Lab\Concerns\DrawsTables;
use App\Lab\Laradir;
use App\Lab\Output\Lines;
use App\Lab\Output\Util;
use Exception;
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

    protected int $width;

    protected int $height;

    protected int $maxTextWidth;

    public function __invoke(Laradir $prompt): string
    {
        $this->width = $prompt->terminal()->cols() - 2;
        $this->height = $prompt->terminal()->lines() - 5;
        $this->maxTextWidth = min(80, $this->width);

        if ($prompt->perPage === 0) {
            // Each result is 8 lines, pad to 10 just in case
            $prompt->perPage = (int) floor(($this->height - 10) / 10);
            return $this;
        }

        $this->line($this->bold('Laradir') . $this->dim(' • https://laradir.com/'));

        $this->newLine(2);

        if ($prompt->state === 'search') {
            return $this->renderSearch($prompt);
        }

        if ($prompt->state === 'detail') {
            return $this->renderDetail($prompt);
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
            $skills = $skills->map(fn ($s) => "  {$s}");
            $skills = $skills->chunk($maxSkills)->map(fn ($chunk, $i) => $i > 0 ? $chunk : $chunk->map(fn ($s) => ltrim($s)));

            $skills = Lines::fromColumns($skills)->lines();
        }

        $row = [
            collect($prompt->detail['levels'])->map(
                fn ($l) => $prompt->filters['roles'][$l],
            )->join(PHP_EOL),
            collect($prompt->detail['types'])->map(
                fn ($l) => $prompt->filters['types'][$l],
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
        $results = collect($prompt->items)->slice(0, $prompt->perPage);

        $this->line($this->bold('Search Laravel Developers'));

        $this->newLine(2);

        $results->each(function ($result, $i) use ($prompt) {
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

        // foreach ($prompt->filters as $key => $filters) {
        //     $this->line($this->bold(Str::of($key)->ucfirst()->singular()->toString()));

        //     foreach ($filters as $value => $label) {
        //         if (in_array($value, $prompt->selectedFilters[$key] ?? [])) {
        //             $this->line($this->dim('● ') . $label);
        //             continue;
        //         }

        //         $this->line($this->dim('○ ') . $label);
        //     }

        //     $this->newLine();
        // }

        $this->verticalPadding(6);

        $dots = Util::range($prompt->results['meta']['last_page'])
            ->map(fn ($page) => $page === $prompt->page ? $this->green('⏺︎') : $this->dim('⏺︎'))
            ->join(' ');

        $this->newLine(2);

        $this->line($dots);

        $this->newLine(2);

        $this->hotkey('/', 'Search');
        $this->hotkey('↑ ↓', 'Change selection');
        $this->hotkey('←', 'Previous page', $prompt->page > 1);
        $this->hotkey('→', 'Next page', $prompt->page < $prompt->results['meta']['last_page']);
        $this->hotkey('Enter', 'Select');
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
                fn ($l) => $this->prompt->filters['roles'][$l],
            )->join(', ');
        }

        try {
            $meta[] = $this->prompt->filters['locations'][$result['country']] ?? null;
        } catch (Exception) {
            //
        }

        return implode($this->dim(' • '), $meta);
    }
}
