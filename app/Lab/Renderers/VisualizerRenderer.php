<?php

namespace App\Lab\Renderers;

use App\Lab\Visualizer;
use App\Lab\Visualizer\Loudness;
use Chewie\Concerns\Aligns;
use Chewie\Output\Lines;
use Laravel\Prompts\Themes\Default\Renderer;

class VisualizerRenderer extends Renderer
{
    use Aligns;

    public function __invoke(Visualizer $prompt): string
    {
        $barHeight = $prompt->terminal()->lines() - 20;
        $barWidth = 1;
        $barSpacing = 1;

        $colors = [
            'cyan',
            'red',
            'green',
            'yellow',
            'blue',
            'magenta',
            'white',
            'cyan',
            'white',
            'magenta',
            'blue',
            'yellow',
            'green',
            'red',
            'cyan',
        ];

        $cols = collect([
            // $prompt->timbre3,
            // $prompt->timbre2,
            // $prompt->timbre1,
            // $prompt->pitch3,
            // $prompt->pitch2,
            // $prompt->pitch1,
            $prompt->loudnessMax,
            $prompt->loudnessStart,
            $prompt->loudnessMax,
            // $prompt->pitch1,
            // $prompt->pitch2,
            // $prompt->pitch3,
            // $prompt->timbre1,
            // $prompt->timbre2,
            // $prompt->timbre3,
        ])->map(function ($loudness, $index) use ($barHeight, $barWidth, $colors) {
            $value = (int) floor(($loudness->value->current() / 100) * $barHeight);

            $column = collect();

            foreach (range(1, $value) as $n) {
                $char = match ($n) {
                    1 => '▀',
                    $value => '▄',
                    default => '█',
                };

                $column->prepend($this->{$colors[$index]}(str_repeat($char, $barWidth)));
            }

            while ($column->count() < $barHeight) {
                $column->prepend(str_repeat(' ', $barWidth));
            }

            return $column;
        });

        $lines = Lines::fromColumns($cols)
            ->spacing($barSpacing)
            ->lines();

        $this->centerHorizontally($lines, $prompt->terminal()->cols() - 2)->each($this->line(...));

        // $this->line($prompt->loudness->value->current());

        return $this;
    }
}
