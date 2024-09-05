<?php

namespace App\Lab\Renderers;

use App\Lab\DancingBars;
use App\Lab\DancingBars\Bar;
use Chewie\Concerns\Aligns;
use Chewie\Output\Lines;
use Laravel\Prompts\Themes\Default\Renderer;

class DancingBarsRenderer extends Renderer
{
    use Aligns;

    protected int $tableCellWidth = 0;

    public function __invoke(DancingBars $prompt): string
    {
        $barSpacing = 2;
        $barWidth = 6;

        if ($prompt->barCount === 0) {
            $totalWidth = $prompt->terminal()->cols() - 2;
            $totalBarWidth = $barWidth + $barSpacing;
            $prompt->barCount = (int) floor($totalWidth / $totalBarWidth);
            $prompt->maxBarHeight = $prompt->terminal()->lines() - 6;

            return $this;
        }

        $cols = $prompt->bars->map(function (Bar $bar) use ($prompt, $barWidth) {
            $column = collect();

            foreach (range(1, $bar->value->current()) as $n) {
                $char = match ($n) {
                    1 => '▀',
                    $bar->value->current() => '▄',
                    default => '█',
                };

                $column->prepend($this->{$bar->color}(str_repeat($char, $barWidth)));
            }

            while ($column->count() < $prompt->maxBarHeight) {
                $column->prepend(str_repeat(' ', $barWidth));
            }

            return $column;
        });

        Lines::fromColumns($cols)
            ->spacing($barSpacing)
            ->lines()
            ->each($this->line(...));

        return $this;
    }
}
