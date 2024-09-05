<?php

namespace App\Lab\Renderers;

use App\Lab\Dashboard;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\CapturesOutput;
use Chewie\Concerns\DrawsBigNumbers;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use Chewie\Output\Lines;
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
    use CapturesOutput;

    protected int $leftColumnWidth;

    public function __invoke(Dashboard $prompt): string
    {
        return $this->minDimensions(fn () => $this->renderDashboard($prompt), 140, 30);
    }

    protected function renderDashboard(Dashboard $prompt): self
    {
        $columnSpacing = 1;
        $columnWidth = (int) floor($prompt->terminal()->cols() / 2);
        $this->leftColumnWidth = $columnWidth - ($columnSpacing * 2) - 1;

        $this->renderHeader($prompt);

        $health = $this->renderHealth($prompt);
        $percentageBar = $this->renderBattery($prompt);
        $barGraph = $this->renderStats($prompt);

        $spacing = collect(['', '']);

        $leftColumn = $spacing
            ->merge($health)
            ->merge($spacing)
            ->merge($percentageBar)
            ->merge($spacing)
            ->merge($barGraph);

        $chatLines = $this->getChat($prompt);

        $dividerLine = collect(range(1, $chatLines->count()))->map(fn () => $this->dim('│'));

        Lines::fromColumns([
            $leftColumn,
            $dividerLine,
            $chatLines,
        ])->spacing(1)->lines()->each($this->line(...));

        $this->newLine();

        $this->hotkey('↑', 'Speed up');
        $this->hotkey('↓', 'Slow down');
        $this->hotkey('q', 'Quit');

        $this->centerHorizontally(
            $this->hotkeys(),
            $prompt->terminal()->cols() - 2,
        )->each($this->line(...));

        return $this;
    }

    protected function renderStats(Dashboard $prompt)
    {
        $barGraphWidth = $this->leftColumnWidth - 4;

        $colors = [
            'yellow',
            'green',
            'blue',
        ];

        $labels = [
            'POWER',
            'SHIELDS',
            'WEAPONS',
        ];

        $lines = collect([
            $prompt->bar1->value,
            $prompt->bar2->value,
            $prompt->bar3->value,
        ])
            ->map(fn ($value) => round($value->current() / 100 * $barGraphWidth))
            ->map(function ($value, $index) use ($colors, $barGraphWidth, $labels) {
                $color = ($value < $barGraphWidth * .4) ? 'red' : $colors[$index];

                return [
                    $this->bold($this->{$color}($labels[$index])),
                    $this->{$color}(str_repeat('█', $value)),
                    '',
                ];
            })
            ->flatten();

        return $lines;
    }

    protected function renderBattery(Dashboard $prompt)
    {
        $barWidth = $this->leftColumnWidth - 4;
        $barPercentage = $prompt->percentageBar->value->current() / 100;
        $barFilled = round($barWidth * $barPercentage);
        $barEmpty = $barWidth - $barFilled;

        $lines = collect($this->bold($this->cyan('BATTERY EFFICIENCY')));

        $lines->push('┏' . str_repeat('━', $barWidth) . '┓');
        $lines->push('┃' . str_repeat('┃', $barFilled) . str_repeat(' ', $barEmpty) . '┃');
        $lines->push('┗' . str_repeat('━', $barWidth) . '┛');

        return $lines;
    }

    protected function renderHealth(Dashboard $prompt)
    {
        $lines = collect($this->bold($this->cyan('SHIP HEALTH')))->merge($this->bigNumber($prompt->health->value->current()));

        return $this->centerHorizontally($lines, $this->leftColumnWidth - 4);
    }

    protected function renderHeader(Dashboard $prompt)
    {
        $leftHalf = $this->bold(
            $this->red($prompt->halPulse->frames->frame(['●', '○'])) . ' Good afternoon, Dave.'
        );

        $rightHalf = $this->dim(date('Y-m-d H:i:s'));

        $leftHalfLength = mb_strlen($this->stripEscapeSequences($leftHalf));
        $rightHalfLength = mb_strlen($this->stripEscapeSequences($rightHalf));

        $this->line($leftHalf . str_repeat(' ', $prompt->terminal()->cols() - $leftHalfLength - $rightHalfLength) . $rightHalf);
        $this->line($this->dim(str_repeat('─', $prompt->terminal()->cols())));
    }

    protected function getChat(Dashboard $prompt)
    {
        $width = $this->leftColumnWidth;

        $messages = $prompt->chat->messages
            ->map(fn ($message) => [
                $message[0] === 'HAL' ? $this->red($message[0]) : $this->cyan($message[0]),
                $message[1],
            ])
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

        $input = $this->captureOutput(fn () => $this->box('', $prompt->valueWithCursor(60)));

        $height = $prompt->terminal()->lines() - 10;

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
