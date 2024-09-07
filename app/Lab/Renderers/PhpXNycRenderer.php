<?php

namespace App\Lab\Renderers;

use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use App\Lab\PhpXNyc;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;

class PhpXNycRenderer extends Renderer
{
    use Aligns;
    use DrawsBoxes;
    use DrawsHotkeys;
    use HasMinimumDimensions;

    protected $width = 80;

    protected $height = 40;

    public function __invoke(PhpXNyc $prompt): string
    {
        return $this->minDimensions(
            width: $this->width,
            height: $this->height,
            render: fn() => $this->render($prompt)
        );
    }

    protected function render(PhpXNyc $prompt): static
    {
        $lines = [
            $this->bold($this->cyan('PHP × NYC')),
            '',
            'A fresh PHP meetup for NYC area devs.',
            'Meet. Learn. Eat. Drink.',
        ];

        collect($lines)->each($this->line(...));

        $this->newLine();

        $lines = collect([
            'Our first meetup is happening on:',
            '',
            $this->bold('February 29, 2024'),
            $this->bold('Midtown Manhattan'),
            $this->bold('6:30pm - 9:30pm'),
            '',
            'Location and speakers will be announced soon.',
            '',
            'We\'d love to see you there.',
            '',
            'Fill out the form below if you\'d like to join.',
        ]);

        $emailInput = $this->boxInternal($prompt, 'Email');

        ray($emailInput);

        $lines = $lines->concat(explode(PHP_EOL, $emailInput));

        $this->box('', $lines->implode(PHP_EOL));

        $output = $this->output;

        $this->output = '';

        $this->center($output, $prompt->terminal()->cols() - 2, $prompt->terminal()->lines() - 2)->each($this->line(...));

        return $this;
    }

    protected function boxInternal(PhpXNyc $prompt, string $title): string
    {
        $originalOutput = $this->output;

        $this->output = '';

        $maxWidth = $this->width - 6;

        $this->box(
            $this->cyan($this->truncate($title, $maxWidth)),
            $prompt->valueWithCursor($maxWidth),
        );

        // return match ($prompt->state) {
        //     'submit' => $this
        //         ->box(
        //             $this->dim($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)),
        //             $this->truncate($prompt->value(), $maxWidth),
        //         ),

        //     'cancel' => $this
        //         ->box(
        //             $this->truncate($prompt->label, $prompt->terminal()->cols() - 6),
        //             $this->strikethrough($this->dim($this->truncate($prompt->value() ?: $prompt->placeholder, $maxWidth))),
        //             color: 'red',
        //         )
        //         ->error('Cancelled.'),

        //     'error' => $this
        //         ->box(
        //             $this->truncate($prompt->label, $prompt->terminal()->cols() - 6),
        //             $prompt->valueWithCursor($maxWidth),
        //             color: 'yellow',
        //         )
        //         ->warning($this->truncate($prompt->error, $prompt->terminal()->cols() - 5)),

        //     default => $this
        //         ->box(
        //             $this->cyan($this->truncate($prompt->label, $prompt->terminal()->cols() - 6)),
        //             $prompt->valueWithCursor($maxWidth),
        //         )
        //         ->when(
        //             $prompt->hint,
        //             fn () => $this->hint($prompt->hint),
        //             fn () => $this->newLine() // Space for errors
        //         )
        // };

        $box = $this->output;

        $this->output = $originalOutput;

        return $box;
    }
}
