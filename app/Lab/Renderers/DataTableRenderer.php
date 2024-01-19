<?php

namespace App\Lab\Renderers;

use App\Lab\Concerns\DrawsHotkeys;
use App\Lab\Concerns\DrawsTables;
use App\Lab\Concerns\HasMinimumDimensions;
use App\Lab\DataTable;
use App\Lab\Output\Util;
use Laravel\Prompts\Themes\Default\Renderer;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;

class DataTableRenderer extends Renderer
{
    use DrawsHotkeys;
    use DrawsTables;
    use HasMinimumDimensions;

    public function __invoke(DataTable $table): string
    {
        return $this->minDimensions(fn () => $this->renderDataTable($table), 80, 20);
    }

    protected function renderDataTable(DataTable $table): string
    {
        $this->renderSearch($table);
        $this->renderJump($table);

        if ($this->output === '') {
            $this->newLine();
        }

        $selectedStyle = new TableCellStyle([
            'bg' => 'white',
            'fg' => 'black',
        ]);

        $rowKeys = array_keys($table->rows[0] ?? []);

        $columnLengths = [];

        foreach ($rowKeys as $key) {
            $columnLengths[$key] = collect($table->rows)->pluck($key)->map(fn ($value) => mb_strlen($value))->max();
        }

        // Columns lengths + table borders + padding + spaces on each side of the table(?)
        $totalTableWidth = array_sum($columnLengths) + (count($columnLengths) * 3) + 2;

        $overflow = $totalTableWidth - $table->terminal()->cols();

        $buffer = $overflow > 0 ? (int) ceil($overflow / count($columnLengths)) : 0;

        // $rows = $table->visible();
        $rows = collect($table->visible())->map(
            fn ($row) => collect($row)->map(
                fn ($value, $key) => str_pad($value, $columnLengths[$key] - $buffer),
            )->map(
                fn ($value, $key) => $this->truncate($value, $columnLengths[$key] - $buffer),
            )->all()
        )->all();

        if (count($rows) > 0) {
            $rows[$table->index] = collect($rows[$table->index])->map(
                fn ($cell) => new TableCell($cell, [
                    'style' => $selectedStyle,
                ]),
            )->all();

            $this->table($rows, $table->headers)->each(fn ($line) => $this->line(' ' . $line));
            $this->newLine();

            $this->line('  ' . $this->dim('Page ') . $table->page . $this->dim(' of ') . $table->totalPages);
            $this->newLine();
        } else {
            $this->newLine();
            $this->line($this->dim('  No results found.'));
            $this->newLine();
        }

        match ($table->state) {
            'search' => count($rows) > 0 ? $this->searchHotkeys($table) : null,
            'jump'   => $this->jumpHotkeys($table),
            default  => $this->defaultHotkeys($table),
        };

        collect($this->hotkeys())->each(fn ($line) => $this->line('  ' . $line));

        $output = $this->output;

        $this->output = '';

        $outputLines = collect(explode(PHP_EOL, $output));

        $header = $outputLines->shift();

        $longest = $outputLines->map(fn ($line) => mb_strwidth(Util::stripEscapeSequences($line)))->max();

        $headerLength = mb_strwidth(Util::stripEscapeSequences($header));

        $header = $header . str_repeat(' ', $longest - $headerLength);

        $outputLines->prepend($header);

        $this->center($outputLines, $table->terminal()->cols() - 2, $table->terminal()->lines() - 6)->each(fn ($line) => $this->line($line));

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

    protected function defaultHotKeys(DataTable $table)
    {
        $this->hotkey('↑ ↓', 'Navigate Records');
        $this->hotkey('←', 'Previous Page', $table->page > 1);
        $this->hotkey('→', 'Next Page', $table->page < $table->totalPages);
        $this->hotkey('/', 'Search');
        $this->hotkey('j', 'Jump to Page');
        $this->hotkey('q', 'Quit');
    }

    protected function renderSearch(DataTable $table)
    {
        if ($table->state !== 'search' && $table->query === '') {
            return;
        }

        if ($table->state !== 'search' && $table->query !== '') {
            $this->line('  ' . $this->dim('Search: ') . $table->query);

            return;
        }

        $this->line('  Search: ' . $table->valueWithCursor(60));
    }

    protected function renderJump(DataTable $table)
    {
        if ($table->state !== 'jump') {
            return;
        }

        $this->line('  Jump to Page: ' . $table->jumpValueWithCursor(60));
    }
}
