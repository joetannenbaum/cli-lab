<?php

namespace App\Lab\Renderers;

use App\Lab\BigText;
use App\Lab\LaraconIndia\Bar;
use App\Lab\LaraconIndiaClosing;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsArt;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use Chewie\Output\Lines;
use Laravel\Prompts\Themes\Default\Renderer;

use function Chewie\stripEscapeSequences;

class LaraconIndiaClosingRenderer extends Renderer
{
    use Aligns;
    use DrawsArt;
    use DrawsHotkeys;
    use HasMinimumDimensions;

    public function __invoke(LaraconIndiaClosing $prompt): string
    {
        return $this->minDimensions(fn () => $this->renderMessage($prompt), 20, 20);
    }

    protected function renderMessage(LaraconIndiaClosing $prompt): self
    {
        $message = mb_strtolower($prompt->message);

        $width = $prompt->terminal()->cols() - 2;
        $height = $prompt->terminal()->lines() - 4;

        $barWidth = 1;

        $prompt->barCount = floor($width / $barWidth);

        $characterWidth = mb_strwidth($this->artLines('a')->first());

        $messageLines = wordwrap(
            string: $message,
            width: floor($width / $characterWidth),
            cut_long_words: true,
        );

        $lines = collect(explode("\n", $messageLines))
            ->map(fn ($line) => mb_str_split($line))
            ->map(
                fn ($letters) => collect($letters)
                    ->map(fn ($letter) => match ($letter) {
                        ' '     => collect(array_fill(0, 7, str_repeat('+', 4))),
                        '.'     => $this->artLines('period'),
                        ','     => $this->artLines('comma'),
                        '?'     => $this->artLines('question-mark'),
                        '!'     => $this->artLines('exclamation-point'),
                        "'"     => $this->artLines('apostrophe'),
                        default => $this->artLines($letter),
                    })
            )
            ->map(
                fn ($letterLines) => $letterLines->map(
                    fn ($lines) => $lines->map(
                        function ($line) {
                            $chars = collect(mb_str_split($line));
                            $first = $chars->search(fn ($c) => $c !== ' ');
                            $last = $chars->reverse()->search(fn ($c) => $c !== ' ');

                            return $chars->map(function ($char, $index) use ($first, $last) {
                                if ($index < $first) {
                                    return '+';
                                }

                                if ($index > $last) {
                                    return '+';
                                }

                                return $char;
                            })->join('');
                        },
                    ),
                ),
            )
            ->flatMap(fn ($letters) => Lines::fromColumns($letters)->lines()->map(fn ($l) => $this->bold($l)));

        $lines = Lines::fromColumns([$this->getQrCode(), $lines])->spacing(6)->paddingCharacter('+')->spacingCharacter('+')->lines();

        $textLines = $this->centerVertically($lines, $height, '+')->map(function ($line) use ($width) {
            $length = mb_strwidth(stripEscapeSequences($line));

            $padding = max($width - $length, 0);

            return $line . str_repeat('+', $padding);
        });

        $prompt->barHeight = $height;

        $colors = [
            'red',
            'green',
            'yellow',
            'blue',
            'magenta',
            'cyan',
            'white',
        ];

        $bars = $prompt->bars->map(function (Bar $bar, $index) use ($barWidth, $colors, $prompt) {
            $arr = array_fill(0, $prompt->barHeight, str_repeat(' ', $barWidth));

            $colorMethod = $colors[$index % count($colors)];

            if ($bar->value->current() === -1) {
                return $arr;
            }

            array_splice($arr, $bar->value->current(), 0, $this->{$colorMethod}(str_repeat('●', $barWidth)));

            return $arr;
        });

        $textLines = $textLines->map(fn ($line) => mb_str_split($line))->all();

        $emptyLines = collect($textLines)->filter(fn ($line) => count($line) === 0)->keys();

        foreach ($bars as $index => $bar) {
            foreach ($bar as $i => $line) {
                if ($emptyLines->contains($i)) {
                    $textLines[$i][$index] = $line;
                    continue;
                }

                if (isset($textLines[$i][$index]) && $textLines[$i][$index] === '+') {
                    $textLines[$i][$index] = $line;
                }
            }
        }

        collect($textLines)->map(fn ($t) => str_replace('+', ' ', implode('', $t)))->each($this->line(...));

        return $this;
    }

    protected function getQrCode()
    {
        $codeLines = $this->artLines('qr-to-links');

        $longest = $codeLines->map(fn ($l) => mb_strwidth($l))->max();

        $subtitle = $this->centerHorizontally([
            '',
            $this->cyan($this->bold('@joetannenbaum')),
        ], $longest, '+');

        return $codeLines->concat($subtitle)->map(fn ($l) => str_repeat('+', 4) . $l);
    }
}
