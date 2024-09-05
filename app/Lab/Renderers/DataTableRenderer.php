<?php

namespace App\Lab\Renderers;

use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\DrawsTables;
use Chewie\Concerns\HasMinimumDimensions;
use App\Lab\DataTable;
use Laravel\Prompts\Themes\Default\Renderer;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;

use function Chewie\stripEscapeSequences;

class DataTableRenderer extends Renderer
{
    use DrawsHotkeys;
    use DrawsTables;
    use HasMinimumDimensions;

    public function __invoke(DataTable $prompt): string
    {
        return $this->minDimensions(fn () => $this->renderDataTable($prompt), 80, 20);
    }

    protected function renderDataTable(DataTable $prompt): string
    {
        $width = $prompt->terminal()->cols() - 2;
        $height = $prompt->terminal()->lines() - 6;

        $this->renderSearch($prompt);
        $this->renderJump($prompt);

        if ($this->output === '') {
            $this->newLine();
        }

        $selectedStyle = new TableCellStyle([
            'bg' => 'white',
            'fg' => 'black',
        ]);

        $columnLengths = collect(array_keys($prompt->rows[0] ?? []))
            ->flatMap(fn ($key) => [
                $key => collect($prompt->rows)
                    ->pluck($key)
                    ->map(fn ($value) => mb_strwidth($value))
                    ->max(),
            ]);

        // $columnLengths = [];

        // foreach ($rowKeys as $key) {
        //     $columnLengths[$key] = collect($prompt->rows)->pluck($key)->map(fn ($value) => mb_strwidth($value))->max();
        // }

        // Columns lengths + table borders + padding + spaces on each side of the table(?)
        // $totalTableWidth = $columnLengths->sum() + (count($columnLengths) * 3) + 2;

        // $overflow = $totalTableWidth - $prompt->terminal()->cols();

        // $buffer = $overflow > 0 ? (int) ceil($overflow / count($columnLengths)) : 0;

        $rows = collect($prompt->visible())->map(
            fn ($row) => collect($row)
                ->map(fn ($value, $key) => mb_str_pad($value, $columnLengths[$key]))
                //     // ->map(fn ($value, $key) => mb_str_pad($value, $columnLengths[$key] - $buffer))
                //     // ->map(fn ($value, $key) => $this->truncate($value, $columnLengths[$key] - $buffer))
                ->all(),
        )->all();

        if (count($rows) > 0) {
            $rows[$prompt->index] = collect($rows[$prompt->index])->map(
                fn ($cell) => new TableCell($cell, ['style' => $selectedStyle]),
            )->all();

            $this->table($rows, $prompt->headers)->each(fn ($line) => $this->line($line));
            $this->newLine();

            $this->line($this->dim('Page ') . $prompt->page . $this->dim(' of ') . $prompt->totalPages);
            $this->newLine();
        } else {
            $this->newLine();
            $this->line($this->dim('No results found.'));
            $this->newLine();
        }

        match ($prompt->state) {
            'search' => count($rows) > 0 ? $this->searchHotkeys($prompt) : null,
            'jump'   => $this->jumpHotkeys($prompt),
            default  => $this->defaultHotkeys($prompt),
        };

        collect($this->hotkeys())->each(fn ($line) => $this->line($line));

        $output = $this->output;

        $this->output = '';

        $this->center($output, $width, $height)->each($this->line(...));

        // $outputLines = collect(explode(PHP_EOL, $output));

        // $header = $outputLines->shift();

        // $longest = $outputLines->map(fn ($line) => mb_strwidth(stripEscapeSequences($line)))->max();

        // $headerLength = mb_strwidth(stripEscapeSequences($header));

        // $header .= str_repeat(' ', $longest - $headerLength);

        // $outputLines->prepend($header);

        // $this->center($outputLines, $width, $height)->each($this->line(...));

        return $this;
    }

    protected function searchHotKeys()
    {
        $this->hotkey('Enter', 'Submit');
    }

    protected function jumpHotKeys()
    {
        $this->hotkey('Enter', 'Jump to Page');
    }

    protected function defaultHotKeys(DataTable $prompt)
    {
        $this->hotkey('↑ ↓', 'Navigate Records');
        $this->hotkey('←', 'Previous Page', $prompt->page > 1);
        $this->hotkey('→', 'Next Page', $prompt->page < $prompt->totalPages);
        $this->hotkey('/', 'Search');
        $this->hotkey('j', 'Jump to Page');
        $this->hotkey('q', 'Quit');
    }

    protected function renderSearch(DataTable $prompt): void
    {
        if ($prompt->state === 'search') {
            $this->line(' Search: ' . $prompt->valueWithCursor(60));

            return;
        }

        if ($prompt->query === '') {
            return;
        }

        if ($prompt->query !== '') {
            $this->line(' ' . $this->dim('Search: ') . $prompt->query);

            return;
        }
    }

    protected function renderJump(DataTable $prompt)
    {
        if ($prompt->state !== 'jump') {
            return;
        }

        $this->line(' Jump to Page: ' . $prompt->jumpValueWithCursor(60));
    }
}
