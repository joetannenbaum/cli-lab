<?php

namespace App\Lab\Renderers;

use App\Lab\Concerns\DrawsHotkeys;
use App\Lab\Kanban;
use App\Lab\Output\Util;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;

class KanbanRenderer extends Renderer
{
    use DrawsBoxes;
    use DrawsHotkeys;
    use DrawsScrollbars;

    public function __invoke(Kanban $kanban): string
    {
        $totalWidth = $kanban->terminal()->cols() - 2;
        $totalHeight = $kanban->terminal()->lines() - 10;

        // Column width should be the total width divided by the number of columns
        $columnWidth = (int) floor($totalWidth / count($kanban->columns));
        // Column width minus some padding
        $cardWidth = $columnWidth - 6;

        // Loop through our columns and render each one
        $columns = collect($kanban->columns)->map(function ($column, $columnIndex) use (
            $cardWidth,
            $columnWidth,
            $kanban,
            $totalHeight,
        ) {
            // Loop through each card in the column and render it
            $cards = collect($column['items'])->map(
                fn ($card, $cardIndex) => $this->getBoxOutput(
                    title: $cardIndex === $kanban->itemIndex && $kanban->columnIndex === $columnIndex ? $card['title'] : $this->dim($card['title']),
                    body: PHP_EOL . $card['description'] . PHP_EOL,
                    color: $cardIndex === $kanban->itemIndex && $kanban->columnIndex === $columnIndex ? 'green' : 'dim',
                    width: $cardWidth,
                ),
            );

            $cardContent = PHP_EOL . $cards->implode(PHP_EOL);

            // Add new lines to the card content to make it the same height as the terminal
            $cardContent .= str_repeat(PHP_EOL, $totalHeight - count(explode(PHP_EOL, $cardContent)) + 1);

            $columnTitle = $kanban->columns[$columnIndex]['title'];

            $columnContent = $this->getBoxOutput(
                $kanban->columnIndex === $columnIndex ? $this->cyan($columnTitle) : $this->dim($columnTitle),
                $cardContent,
                $kanban->columnIndex === $columnIndex ? 'cyan' : 'dim',
                $columnWidth,
                false,
            );

            return explode(PHP_EOL, $columnContent);
        });

        // Zip the columns together so we can render them side by side
        collect($columns->shift())
            ->zip(...$columns)
            ->map(fn ($lines) => $lines->implode(''))
            // Render the lines
            ->each(fn ($line) => $this->line($line));

        $this->hotkey('Enter', 'Move item');
        $this->hotkey('↑', 'Previous card', $kanban->itemIndex > 0);
        $this->hotkey('↓', 'Next card', $kanban->itemIndex < count($kanban->columns[$kanban->columnIndex]['items']) - 1);
        $this->hotkey('→', 'Next column', $kanban->columnIndex < count($kanban->columns) - 1);
        $this->hotkey('←', 'Previous column', $kanban->columnIndex > 0);
        // $this->hotkey('n', 'New card');
        $this->hotkey('q', 'Quit');

        foreach ($this->hotkeys() as $hotkey) {
            $this->line('  ' . $hotkey);
        }

        return $this;
    }

    protected function getBoxOutput(string $title, string $body, string $color, int $width, $wordwrap = true): string
    {
        // Reset the output string
        $this->output = '';

        // Set the minWidth to the desired width, the box method
        // uses this to calculate how wide the box should be
        $this->minWidth = $width - 5;

        if ($wordwrap) {
            $body = mb_wordwrap($body, $width - 4, PHP_EOL, true);
        }

        $this->box(
            title: $title,
            body: $body,
            color: $color,
        );

        $content = $this->output;

        $this->output = '';

        return $content;
    }
}
