<?php

namespace App\Lab\Renderers;

use App\Lab\Concerns\Aligns;
use App\Lab\Concerns\HasMinimumDimensions;
use App\Lab\Sticker;
use App\Lab\Concerns\DrawsHotkeys;
use App\Lab\Sticker\Bar;
use App\Lab\Sticker\Input;
use Chewie\Concerns\DrawsArt;
use Chewie\Output\Lines;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;
use Illuminate\Support\Str;

class StickerRenderer extends Renderer
{
    use Aligns;
    use HasMinimumDimensions;
    use DrawsHotkeys;
    use DrawsBoxes;
    use DrawsArt;

    public function __invoke(Sticker $prompt): string
    {
        return $this->minDimensions(fn () => $this->renderForm($prompt), 70, 40);
    }

    protected function renderForm(Sticker $prompt): self
    {
        if ($prompt->stickersLeft <= 0) {
            $this->line($this->cyan($this->bold("  Sorry, all stickers have been claimed.")));
            $this->newLine();
            $this->line("  I'll be giving away more stickers in the future. Stay tuned!");

            $this->newLine(2);

            $this->hotkey('Ctrl+C', 'Exit');

            collect($this->hotkeys())->each(fn ($line) => $this->line('  ' . $line));

            return $this;
        }

        match ($prompt->state) {
            'form' => $this->renderFormState($prompt),
            'submitted' => $this->renderSubmittedState($prompt),
            default => $this->renderInitialState($prompt),
        };

        return $this;
    }

    protected function renderSubmittedState(Sticker $prompt): self
    {
        $message = 'thank you!';

        $width = $prompt->terminal()->cols() - 2;
        $height = $prompt->terminal()->lines() - 4;

        $barWidth = 1;

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
            ->flatMap(fn ($letters) => Lines::fromColumns($letters)->lines());

        $textLines = $this->center($lines, $width, $height, '+');

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

    protected function renderInitialState(Sticker $prompt): self
    {
        $this->line($this->cyan($this->bold("  I'm giving away CLI Lab stickers.")));
        $this->newLine();

        $this->line($this->green("  But there's a catch."));
        $this->newLine();

        $rules = [
            "You have to either:",
            '',
            "1. " . $this->bold('Be') . " an open source maintainer.",
            "2. " . $this->bold('Sponsor') . " (one-time or recurring) an open source maintainer in the last 30 days. Any amount.",
            '',
            "Upload a screenshot to a public URL to verify your support and I'll send a sticker your way.",
            '',
            "This is an honor system, I'm not doing deep investigation on your contribution. But hey, let's support open source together.",
            '',
            $this->bold("Do you qualify for the sticker? Heck yeah!"),
            '',
            "Great news: There are {$prompt->stickersLeft} " . Str::plural('sticker', $prompt->stickersLeft) . " left.",
            '',
            $this->magenta($this->bold("Press Enter to fill out the form.")),
            '',
            str_repeat($this->dim('-'), 60),
            $this->dim("* Unfortunately, I can only ship to the US at this time. Sorry international friends. I love you all and will figure out a cost-effective way to send you stickers in the future. Trust and believe."),
        ];

        $wrapped = wordwrap(implode(PHP_EOL, $rules), 60);

        collect(explode(PHP_EOL, $wrapped))->each(fn ($line) => $this->line('  ' . $line));

        $this->newLine();

        return $this;
    }

    protected function renderFormState(Sticker $prompt): self
    {
        $this->line($this->cyan($this->bold("  Hey: You're awesome.")));

        $this->newLine();

        $this->line('  Tell me where to send your sticker!');

        $this->newLine();

        $prompt->inputs->each(fn (Input $input) => $this->renderInput($input));

        $this->newLine(2);

        $this->hotkey('Tab', 'Next field');
        $this->hotkey('Shift+Tab', 'Previous field');
        $this->hotkey('Enter', 'Submit');

        collect($this->hotkeys())->each(fn ($line) => $this->line('  ' . $line));

        return $this;
    }

    protected function renderInput(Input $input): self
    {
        $color = $this->getColor($input);

        $this->box(
            title: $input->label,
            body: $input->valueWithCursor(60),
            color: $color,
            footer: $input->hint,
            info: $input->errors[0] ?? '',
        );

        return $this;
    }

    protected function getColor(Input $input): string
    {
        if (!$input->isValid) {
            return 'yellow';
        }

        if ($input->isFocused) {
            return 'cyan';
        }

        return 'gray';
    }
}
