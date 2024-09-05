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
        $height = $prompt->terminal()->lines() - 8;

        // $cols = $prompt->raindrops->map(function (Raindrop $drop) {
        //     $char = [
        //         1 => '|',
        //         2 => '◉',
        //         3 => '◎',
        //         4 => '◯',
        //     ][$drop->depth];

        //     // if ($drop->value->current() <= 23) {
        //     //     $val = 23 - $drop->value->current();
        //     //     $color = 255 - $val;
        //     //     //     // } else if ($drop->value->current() >= $drop->totalHeight - 23) {
        //     //     //     //     $color = 255 - ($drop->totalHeight - $drop->value->current());
        //     // } else {
        //     $color = 255 - (($drop->depth - 1) * 5);
        //     // }

        //     if ($drop->depth === 1) {
        //         $char = $this->bold($char);
        //     }

        //     return collect(array_fill(0, $drop->value->current(), ' '))
        //         ->concat(["\e[38;5;{$color}m{$char}\e[39m"])
        //         ->concat(array_fill(0, $drop->totalHeight - $drop->value->current(), ' '));
        // });

        // Lines::fromColumns($cols)->spacing(2)->lines()->each($this->line(...));

        // return $this;

        // $cols = $prompt->bouncingBalls->map(function ($ball) use ($prompt) {
        //     $ballLines = collect(array_fill(0, $ball->position(), ' '))
        //         ->concat([$ball->visible() ? '●' : ' '])
        //         ->concat(array_fill(0, $prompt->bouncingBallHeight - $ball->position(), ' '));

        //     // $ballLines[$ball->bounceHeight] = '_';

        //     foreach ($ball->prevFrames as $index => $position) {
        //         if ($ballLines[$position] !== ' ') {
        //             continue;
        //         }

        //         $color = 255 - $ball->totalFade - $index;

        //         $ballLines[$position] = "\e[38;5;{$color}m●\e[39m";
        //     }

        //     return $ballLines;
        // });

        // Lines::fromColumns($cols)->spacing(2)->lines()->each($this->line(...));

        // return $this;

        $lines = $prompt->fadeToggle->getLines()->map(function ($line) {
            return $line->map(function ($char) {

                [$state, $value] = $char;

                if ($state > 23) {
                    return ' ';
                }

                $color = 255 - $state;

                return "\e[38;5;{$color}m{$value}\e[39m";
            })->implode('');
        });

        $message = mb_strtolower($prompt->message);

        $this->center($lines, $width, $height)->each($this->line(...));

        return $this;

        $i = 0;

        $colors = [
            'red',
            'green',
            'yellow',
            'blue',
            'magenta',
            'cyan',
            'white',
        ];

        $longestType = collect($prompt->dotPositions)->keys()->map(fn($type) => mb_strwidth($type))->max();

        collect($prompt->dotPositions)->map(function ($position, $type) use (&$colors, &$i, $prompt, $longestType) {
            $buffer = $prompt->lowestValue;

            if ($prompt->direction === 1) {
                $spaceCount = $prompt->dotSafeZone - $position;
                $paddingStart = str_repeat(' ', max($spaceCount, 0) + $buffer);
            } else {
                $spaceCount = $position;
                $paddingStart = str_repeat(' ', max($position, 0) + $buffer);
            }

            if ($spaceCount < 0) {
                $paddingStart = substr($paddingStart, abs($spaceCount));
            }

            $color = $colors[$i++ % count($colors)];

            return $this->{$color}(str_pad($type, $longestType, ' ', STR_PAD_LEFT)) . '  ' . $paddingStart . $this->{$color}('●');
        })->values()->each($this->line(...));

        return $this;

        // $this->newLine(40 - $prompt->dotPosition);
        // $this->line('●');
        // $this->newLine($prompt->dotPosition);

        return $this;

        dd($height);

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

        $lines = Lines::fromColumns([$this->getQrCode(), $lines])->spacing(6)->paddingCharacter('+')->spacingCharacter('+')->lines();

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

        $dots = $prompt->dots->map(function (Dot $dot, $index) use ($dotWidth, $colors, $prompt) {
            $arr = array_fill(0, $prompt->dotHeight, str_repeat(' ', $dotWidth));

            $colorMethod = $colors[$index % count($colors)];

            if ($dot->value->current() === -1) {
                return $arr;
            }

            array_splice($arr, $dot->value->current(), 0, $this->{$colorMethod}(str_repeat('●', $dotWidth)));

            return $arr;
        });

        $textLines = $textLines->map(fn($line) => mb_str_split($line))->all();

        $emptyLines = collect($textLines)->filter(fn($line) => count($line) === 0)->keys();

        foreach ($dots as $index => $dot) {
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

    protected function getQrCode()
    {
        return collect();

        $codeLines = $this->artLines('qr-to-links');

        $longest = $codeLines->map(fn($l) => mb_strwidth($l))->max();

        $subtitle = $this->centerHorizontally([
            '',
            $this->cyan($this->bold('@joetannenbaum')),
        ], $longest, '+');

        return $codeLines->concat($subtitle)->map(fn($l) => str_repeat('+', 4) . $l);
    }
}
