<?php

namespace App\Lab\Renderers;

use App\Lab\BigText;
use App\Lab\LaraconUs\Dot;
use App\Lab\LaraconUs\Raindrop;
use App\Lab\LaraconUsClosing;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsArt;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use Chewie\Output\Lines;
use Laravel\Prompts\Themes\Default\Renderer;

use function Chewie\stripEscapeSequences;

class LaraconUsClosingRenderer extends Renderer
{
    use Aligns;
    use DrawsArt;
    use DrawsHotkeys;
    use HasMinimumDimensions;

    public function __invoke(LaraconUsClosing $prompt): string
    {
        return $this->minDimensions(fn() => $this->renderMessage($prompt), 20, 20);
    }

    protected function renderMessage(LaraconUsClosing $prompt): self
    {
        $width = $prompt->terminal()->cols() - 2;
        $height = $prompt->terminal()->lines() - 4;

        $message = mb_strtolower($prompt->message);

        $dotWidth = 1;

        $prompt->dotCount = floor($width / $dotWidth);

        $characterWidth = mb_strwidth($this->artLines('a')->first());

        $messageLines = wordwrap(
            string: $message,
            width: floor($width / $characterWidth),
            cut_long_words: true,
        );

        $lines = collect(explode("\n", $messageLines))
            ->map(fn($line) => mb_str_split($line))
            ->map(
                fn($letters) => collect($letters)
                    ->map(fn($letter) => match ($letter) {
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
                fn($letterLines) => $letterLines->map(
                    fn($lines) => $lines->map(
                        function ($line) {
                            $chars = collect(mb_str_split($line));
                            $first = $chars->search(fn($c) => $c !== ' ');
                            $last = $chars->reverse()->search(fn($c) => $c !== ' ');

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
            ->flatMap(fn($letters) => Lines::fromColumns($letters)->lines()->map(fn($l) => $this->bold($l)));

        $lines = Lines::fromColumns([$this->getQrCode($prompt), $lines])->spacing(6)->paddingCharacter('+')->spacingCharacter('+')->lines();

        $textLines = $this->centerVertically($lines, $height, '+')->map(function ($line) use ($width) {
            $length = mb_strwidth(stripEscapeSequences($line));

            $padding = max($width - $length, 0);

            return $line . str_repeat('+', $padding);
        });

        $prompt->dotHeight = $height;

        $colors = [
            'red',
            'green',
            'yellow',
            'blue',
            'magenta',
            'cyan',
            'white',
        ];

        // $dots = $prompt->dots->map(function (Dot $dot, $index) use ($dotWidth, $colors, $prompt) {
        //     $arr = array_fill(0, $prompt->dotHeight, str_repeat(' ', $dotWidth));

        //     $colorMethod = $colors[$index % count($colors)];

        //     if ($dot->value->current() === -1) {
        //         return $arr;
        //     }

        //     array_splice($arr, $dot->value->current(), 0, $this->{$colorMethod}(str_repeat('●', $dotWidth)));

        //     return $arr;
        // });



        $cols = $prompt->bouncingBalls->flatMap(function ($ball) use ($prompt) {
            $ballLines = collect(array_fill(0, $ball->position(), ' '))
                ->concat([$ball->visible() ? '●' : ' '])
                ->concat(array_fill(0, $prompt->bouncingBallHeight - $ball->position(), ' '));

            foreach ($ball->prevFrames as $index => $position) {
                if ($ballLines[$position] !== ' ') {
                    continue;
                }

                $color = 255 - $ball->totalFade - $index;

                $ballLines[$position] = "\e[38;5;{$color}m●\e[39m";
            }

            return [array_fill(0, $prompt->bouncingBallHeight, ' '), $ballLines];
        });

        $textLines = $textLines->map(fn($line) => mb_str_split($line))->all();

        $emptyLines = collect($textLines)->filter(fn($line) => count($line) === 0)->keys();

        foreach ($cols as $index => $dot) {
            foreach ($dot as $i => $line) {
                if ($emptyLines->contains($i)) {
                    $textLines[$i][$index] = $line;
                    continue;
                }

                if (isset($textLines[$i][$index]) && $textLines[$i][$index] === '+') {
                    $textLines[$i][$index] = $line;
                }
            }
        }

        collect($textLines)->map(fn($t) => str_replace('+', ' ', implode('', $t)))->each($this->line(...));

        return $this;
    }

    protected function getQrCode(LaraconUsClosing $prompt)
    {
        $lines = $prompt->qrCode->getLines()->map(function ($line) {
            return $line->map(function ($char) {

                [$state, $value] = $char;

                if ($state > 23) {
                    return ' ';
                }

                $color = 255 - $state;

                return "\e[38;5;{$color}m{$value}\e[39m";
            })->implode('');
        });

        $codeLines = $this->artLines('qr-to-links');

        $longest = $codeLines->map(fn($l) => mb_strwidth($l))->max();

        $subtitle = $this->centerHorizontally([
            '',
            $this->cyan($this->bold('@joetannenbaum')),
        ], $longest, '+');

        return $lines->concat($subtitle)->map(fn($l) => str_repeat('+', 4) . $l);
    }
}
