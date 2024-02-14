<?php

namespace App\Lab\Renderers;

use App\Lab\BigText;
use App\Lab\Concerns\Aligns;
use App\Lab\Concerns\DrawsAscii;
use App\Lab\Concerns\DrawsHotkeys;
use App\Lab\Concerns\HasMinimumDimensions;
use Laravel\Prompts\Themes\Default\Renderer;

class BigTextRenderer extends Renderer
{
    use Aligns;
    use DrawsAscii;
    use DrawsHotkeys;
    use HasMinimumDimensions;

    public function __invoke(BigText $prompt): string
    {
        return $this->minDimensions(fn () => $this->renderMessage($prompt), 20, 20);
    }

    protected function renderMessage(BigText $prompt): self
    {
        $message = strtolower($prompt->message);

        $width = $prompt->terminal()->cols() - 2;
        $height = $prompt->terminal()->lines() - 5;

        $messageLines = wordwrap(
            string: $message,
            width: floor($width / 7),
            cut_long_words: true,
        );

        $lines = collect(explode("\n", $messageLines))
            ->map(fn ($line) => str_split($line))
            ->map(
                fn ($letters) => collect($letters)
                    ->map(fn ($letter) => match ($letter) {
                        ' '     => array_fill(0, 7, str_repeat(' ', 4)),
                        '.'     => $this->asciiLines('alphabet/period'),
                        ','     => $this->asciiLines('alphabet/comma'),
                        '?'     => $this->asciiLines('alphabet/question-mark'),
                        '!'     => $this->asciiLines('alphabet/exclamation-point'),
                        "'"     => $this->asciiLines('alphabet/apostrophe'),
                        default => $this->asciiLines('alphabet/' . $letter),
                    })
            )
            ->flatMap(function ($letters) {

                $lines = collect($letters->shift());

                if ($letters->isNotEmpty()) {
                    $lines = $lines->zip(...$letters->toArray())->map(fn ($line) => $line->implode(''));
                }

                return $lines;
            })
            ->slice(-($height - 4));

        $this->center($lines, $width, $height - 2)
            ->each(fn ($line) => $this->line($line));

        $this->pinToBottom($height, function () use ($message, $width) {
            $this->hotkey('Enter', 'Clear', $message !== '');

            foreach ($this->hotkeys() as $hotkey) {
                $this->centerHorizontally($hotkey, $width)
                    ->each(fn ($line) => $this->line($line));
            }
        });

        return $this;
    }
}
