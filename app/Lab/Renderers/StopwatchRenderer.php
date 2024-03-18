<?php

namespace App\Lab\Renderers;

use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsBigNumbers;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use App\Lab\Stopwatch;
use Chewie\Concerns\DrawsArt;
use Laravel\Prompts\Themes\Default\Renderer;

class StopwatchRenderer extends Renderer
{
    use Aligns;
    use DrawsArt;
    use DrawsBigNumbers;
    use DrawsHotkeys;
    use HasMinimumDimensions;

    public function __invoke(Stopwatch $prompt): string
    {
        return $this->minDimensions(fn () => $this->renderMessage($prompt), 20, 20);
    }

    protected function renderMessage(Stopwatch $prompt): self
    {
        $minutes = floor($prompt->elapsedMilliseconds / 60000);
        $seconds = floor(($prompt->elapsedMilliseconds - $minutes * 60000) / 1000);
        $milliseconds = $prompt->elapsedMilliseconds - ($minutes * 60000) - ($seconds * 1000);

        $minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
        $seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);
        $milliseconds = str_pad($milliseconds, 3, '0', STR_PAD_LEFT);

        $colon = <<<'COLON'

        •
        •
        COLON;

        $bigMinutes = $this->bigNumber($minutes);
        $bigSeconds = $this->bigNumber($seconds);
        $bigColon = collect(explode(PHP_EOL, $colon))->map(
            fn ($line) => mb_str_pad($line, 1, ' '),
        );

        $stopwatchLines = collect($bigMinutes)
            ->zip(...[$bigColon, $bigSeconds])
            ->map(fn ($line) => $line->implode(''))
            ->map(fn ($line, $index) => $index === 1 ? $line . ' ' . $milliseconds : $line . str_repeat(' ', 4))
            ->map(fn ($line) => str_repeat(' ', 4) . $line);

        $this->centerHorizontally($stopwatchLines, 27)->each($this->line(...));

        if (count($prompt->laps) > 0) {
            $this->newLine();
            $this->line(str_repeat(' ', 5) . $this->bold($this->cyan('Lap')) . str_repeat(' ', 9) . $this->bold($this->green('Total')) . str_repeat(' ', 4));
            $this->line(str_repeat(' ', 5) . $this->dim(str_repeat('─', 21)));
        }

        foreach ($prompt->laps as $index => $lap) {
            $previousLap = $prompt->laps[$index - 1] ?? 0;
            $this->renderLap($index, $lap, $previousLap);
        }

        $this->newLine();

        $this->hotkey('Space', 'Lap');
        $this->hotkey('R', 'Reset');

        foreach ($this->hotkeys() as $line) {
            $this->line($line);
        }

        $width = $prompt->terminal()->cols() - 2;
        $height = $prompt->terminal()->lines() - 5;

        $output = $this->output;

        $this->output = '';

        $this->center($output, $width, $height)->each($this->line(...));

        return $this;
    }

    protected function renderLap(int $index, int $lap, int $previousLap): void
    {
        $minutes = floor($lap / 60000);
        $seconds = floor(($lap - $minutes * 60000) / 1000);
        $milliseconds = $lap - ($minutes * 60000) - ($seconds * 1000);

        $minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
        $seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);
        $milliseconds = str_pad($milliseconds, 3, '0', STR_PAD_LEFT);

        $lapNumber = str_pad($index + 1, 2, '0', STR_PAD_LEFT);

        $diff = $lap - $previousLap;

        $diffMinutes = floor($diff / 60000);
        $diffSeconds = floor(($diff - $diffMinutes * 60000) / 1000);
        $diffMilliseconds = $diff - ($diffMinutes * 60000) - ($diffSeconds * 1000);

        $diffMinutes = str_pad($diffMinutes, 2, '0', STR_PAD_LEFT);
        $diffSeconds = str_pad($diffSeconds, 2, '0', STR_PAD_LEFT);
        $diffMilliseconds = str_pad($diffMilliseconds, 3, '0', STR_PAD_LEFT);

        $this->line($this->dim($lapNumber . '   ') . $this->cyan("{$diffMinutes}:{$diffSeconds}.{$diffMilliseconds}") . $this->green("   {$minutes}:{$seconds}.{$milliseconds}"));
    }
}
