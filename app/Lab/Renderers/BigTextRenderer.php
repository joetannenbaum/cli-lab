<?php

namespace App\Lab\Renderers;

use App\Lab\BigText;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsArt;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use Chewie\Output\Lines;
use Laravel\Prompts\Themes\Default\Renderer;

class BigTextRenderer extends Renderer
{
    use Aligns;
    use DrawsArt;
    use DrawsHotkeys;
    use HasMinimumDimensions;

    public function __invoke(BigText $prompt): string
    {
        return $this->minDimensions(fn () => $this->renderMessage($prompt), 20, 20);
    }

    protected function renderMessage(BigText $prompt): self
    {
        $message = mb_strtolower($prompt->message);

        // Version without wordwrap
        // $lines = collect(mb_str_split($message))
        //     ->map(fn ($letter) => match ($letter) {
        //         ' '     => array_fill(0, 7, str_repeat(' ', 4)),
        //         '.'     => $this->artLines('alphabet/period'),
        //         ','     => $this->artLines('alphabet/comma'),
        //         '?'     => $this->artLines('alphabet/question-mark'),
        //         '!'     => $this->artLines('alphabet/exclamation-point'),
        //         "'"     => $this->artLines('alphabet/apostrophe'),
        //         default => $this->artLines('alphabet/' . $letter),
        //     });

        // $lines = Lines::fromColumns($lines)->lines();
        // Lines::fromColumns($lines)->lines()->each($this->line(...));

        // Version with wordwrap

        $width = $prompt->terminal()->cols() - 2;
        $height = $prompt->terminal()->lines() - 3;

        $messageLines = wordwrap(
            string: $message,
            width: floor($width / 7),
            cut_long_words: true,
        );

        $lines = collect(explode("\n", $messageLines))
            ->map(fn ($line) => collect(mb_str_split($line)))
            ->map(
                fn ($letters) => $letters->map(fn ($letter) => match ($letter) {
                    ' '     => array_fill(0, 7, str_repeat(' ', 4)),
                    '.'     => $this->artLines('alphabet/period'),
                    ','     => $this->artLines('alphabet/comma'),
                    '?'     => $this->artLines('alphabet/question-mark'),
                    '!'     => $this->artLines('alphabet/exclamation-point'),
                    "'"     => $this->artLines('alphabet/apostrophe'),
                    default => $this->artLines('alphabet/' . $letter),
                })
            )
            ->flatMap(fn ($letters) => Lines::fromColumns($letters)->lines())
            ->slice(($height - 2) * -1);

        $this->center($lines, $width, $height)->each($this->line(...));

        // $this->hotkey('Enter', 'Clear', $message !== '');

        // $this->centerHorizontally($this->hotkeys(), $width)->each($this->line(...));

        return $this;
    }
}
