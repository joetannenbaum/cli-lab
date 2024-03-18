<?php

namespace App\Lab\Renderers;

use App\Lab\Dashboard;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsBigNumbers;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;

class DashboardRenderer extends Renderer
{
    use Aligns;
    use DrawsBigNumbers;
    use DrawsBoxes;
    use DrawsHotkeys;
    use DrawsScrollbars;
    use HasMinimumDimensions;

    protected int $leftColumnWidth;

    public function __invoke(Dashboard $dashboard): string
    {
        return $this->minDimensions(fn () => $this->renderDashboard($dashboard), 140, 30);
    }

    protected function renderDashboard(Dashboard $dashboard): self
    {
        $this->leftColumnWidth = (int) floor($dashboard->terminal()->cols() / 2);

        $this->renderHeader($dashboard);

        $health = $this->renderHealth($dashboard);

        $percentageBar = $this->renderPercentageBar($dashboard);

        $barGraph = $this->renderBarGraph($dashboard);

        $leftColumn = $health->merge($percentageBar)->merge($barGraph);

        $chatLines = $this->getChat($dashboard);

        while ($leftColumn->count() < $chatLines->count()) {
            $leftColumn->push(str_repeat(' ', $this->leftColumnWidth));
        }

        $leftColumn = $leftColumn->map(function ($line) {
            $lineLength = mb_strlen($this->stripEscapeSequences($line));

            if ($lineLength > $this->leftColumnWidth) {
                return mb_substr($line, 0, $this->leftColumnWidth - $lineLength);
            }

            return $line . str_repeat(' ', max($this->leftColumnWidth - $lineLength, 0));
        })->map(fn ($line) => $line . $this->dim('│'));

        $leftColumn->zip($chatLines)->each(function ($lines) {
            $this->line($lines->implode(' '));
        });

        $this->newLine();

        $this->hotkey('↑', 'Speed up');
        $this->hotkey('↓', 'Slow down');
        $this->hotkey('q', 'Quit');

        $this->centerHorizontally(
            $this->hotkeys(),
            $dashboard->terminal()->cols() - 2,
        )->each($this->line(...));

        return $this;
    }

    protected function renderBarGraph(Dashboard $dashboard)
    {
        $barGraphWidth = $this->leftColumnWidth - 4;

        $colors = [
            'yellow',
            'green',
            'blue',
        ];

        $lines = collect($dashboard->barGraph->values)->map(fn ($value) => round($value->current() / 100 * $barGraphWidth))
            ->map(function ($value, $index) use ($colors, $barGraphWidth) {
                $color = ($value < $barGraphWidth * .4) ? 'red' : $colors[$index];

                return $this->{$color}(str_repeat('█', $value));
            });

        $lines = collect([
            $this->bold($this->yellow('POWER')),
            $this->bold($this->green('SHIELDS')),
            $this->bold($this->blue('WEAPONS')),
        ])->zip($lines)->zip(['', '', ''])->flatten();

        $lines->prepend('');
        $lines->push('');

        return $lines;
    }

    protected function renderPercentageBar(Dashboard $dashboard)
    {
        $barWidth = $this->leftColumnWidth - 4;
        $barPercentage = $dashboard->percentageBar->value->current() / 100;
        $barFilled = round($barWidth * $barPercentage);
        $barEmpty = $barWidth - $barFilled;

        $lines = collect($this->bold($this->cyan('BATTERY EFFICIENCY')));

        $lines->push('┏' . str_repeat('━', $barWidth) . '┓');
        $lines->push('┃' . str_repeat('┃', $barFilled) . str_repeat(' ', $barEmpty) . '┃');
        $lines->push('┗' . str_repeat('━', $barWidth) . '┛');

        $lines->prepend('');
        $lines->push('');

        return $lines;
    }

    protected function renderHealth(Dashboard $dashboard)
    {
        $lines = collect($this->bold($this->cyan('SHIP HEALTH')))->merge($this->bigNumber($dashboard->health->value->current()));

        $lines->prepend('');
        $lines->push('');

        return $this->centerHorizontally($lines, $this->leftColumnWidth - 4);
    }

    protected function renderHeader(Dashboard $dashboard)
    {
        $leftHalf = $this->bold(
            $this->red($dashboard->halPulse->frames->frame(['●', '○'])) . ' Good afternoon, Dave.'
        );

        $rightHalf = $this->dim(date('Y-m-d H:i:s'));

        $leftHalfLength = mb_strlen($this->stripEscapeSequences($leftHalf));
        $rightHalfLength = mb_strlen($this->stripEscapeSequences($rightHalf));

        $this->line($leftHalf . str_repeat(' ', $dashboard->terminal()->cols() - $leftHalfLength - $rightHalfLength) . $rightHalf);
        $this->line($this->dim(str_repeat('─', $dashboard->terminal()->cols())));
    }

    protected function getChat(Dashboard $dashboard)
    {
        $width = $this->leftColumnWidth - 4;

        $messages = $dashboard->chat->messages->map(
            fn ($message) => [$message[0] === 'HAL' ? $this->red($message[0]) : $this->cyan($message[0]), $message[1]],
        )
            ->map(function ($message) use ($width) {
                [$speaker, $message] = $message;

                $message = collect(explode(PHP_EOL, wordwrap($message, $width - 2)))->map(function ($line) use ($width) {
                    $padding = $width - mb_strlen($this->stripEscapeSequences($line));

                    return str_pad($line, max($padding, 0));
                });

                $message->prepend($speaker);

                $message->push(str_repeat(' ', $width));

                return $message;
            })
            ->flatten();

        $output = $this->output;

        $this->output = '';

        $this->box('', $dashboard->valueWithCursor(60));

        $input = $this->output;

        $this->output = $output;

        $height = $dashboard->terminal()->lines() - 10;

        $emptyLines = max($height - $messages->count(), 0);

        $scrollbar = $this->scrollbar(
            visible: $messages->slice(-$height),
            firstVisible: max(abs($messages->count() - $height), 0),
            height: $height,
            total: $messages->count(),
            width: $width,
        )->map(fn ($line) => '  ' . $line);

        if ($emptyLines > 0) {
            $scrollbar = collect()->times($emptyLines, fn () => str_repeat(' ', $width))->merge($scrollbar);
        }

        $input = collect(explode(PHP_EOL, $input))->filter();

        return $scrollbar->merge($input);
    }
}
