<?php

namespace App\Lab\Renderers;

use App\Lab\Concerns\Aligns;
use App\Lab\Concerns\DrawsAscii;
use App\Lab\Concerns\DrawsBigNumbers;
use App\Lab\Concerns\DrawsHotkeys;
use App\Lab\Concerns\HasMinimumDimensions;
use App\Lab\Output\Lines;
use App\Lab\Output\Util;
use App\Lab\Prong;
use App\Lab\Prong\Ball;
use App\Lab\Prong\Title;
use App\Lab\Support\SSH;
use Laravel\Prompts\Themes\Default\Renderer;

class ProngRenderer extends Renderer
{
    use Aligns;
    use DrawsBigNumbers;
    use DrawsHotkeys;
    use HasMinimumDimensions;
    use DrawsAscii;

    protected int $fullWidth;

    protected int $fullHeight;

    public function __invoke(Prong $prompt): string
    {
        return $this->minDimensions(
            width: $prompt->width,
            height: $prompt->height + 16,
            render: fn () => $this->render($prompt)
        );
    }

    protected function render(Prong $prompt): static
    {
        $this->fullWidth = $prompt->terminal()->cols() - 2;
        $this->fullHeight = $prompt->terminal()->lines() - 4;

        if ($prompt->state === 'title') {
            return $this->titleScreen($prompt);
        }

        if ($prompt->winner !== null) {
            return $this->winnerScreen($prompt);
        }

        return $this->playGame($prompt);
    }

    protected function playGame(Prong $prompt): static
    {
        /** @var Ball $ball */
        $ball = $prompt->loopable(Ball::class);

        /** @var Paddle $player1 */
        $player1 = $prompt->loopable('player1');

        /** @var Paddle $player2 */
        $player2 = $prompt->loopable('player2');

        $paddle1 = $this->paddle($prompt, $player1->value->current(), 'red');
        $paddle2 = $this->paddle($prompt, $player2->value->current(), 'green');
        $ball = $this->ball($prompt, $ball);

        $cols = collect([$paddle1, $ball, $paddle2])->map(fn ($el) => explode(PHP_EOL, $el));

        $cols = Lines::fromColumns($cols)
            ->alignNone()
            ->lines()
            ->filter(fn ($line) => $line !== '')
            ->map(fn ($line) => $this->dim('│ ') . $line . $this->dim(' │'))
            ->prepend($this->dim('┌' . str_repeat('─', $prompt->width + 4) . '┐'))
            ->prepend('')
            ->prepend(($prompt->game->player_one_ready && $prompt->game->player_two_ready) ? '' : 'To join this game: ' . $this->cyan(SSH::command('prong ' . $prompt->gameId)))
            ->push($this->dim('└' . str_repeat('─', $prompt->width + 4) . '┘'));

        $this->center($cols, $this->fullWidth, $this->fullHeight - 2)->each(
            fn ($line) => $this->line($line)
        );

        $this->hotkey('↑', 'Move up');
        $this->hotkey('↓', 'Move down');
        $this->hotkey('q', 'Quit');

        $hotkeys = collect($this->hotkeys())->implode(PHP_EOL);

        if ($prompt->playerNumber === 1) {
            $hotkeys = $this->bold('← You are Player 1    ') . $hotkeys;
        } else {
            $hotkeys = $hotkeys . $this->bold('    You are Player 2 →');
        }

        $this->centerHorizontally($hotkeys, $this->fullWidth)->each(fn ($line) => $this->line($line));

        return $this;
    }

    protected function winnerScreen(Prong $prompt): static
    {
        $title = $prompt->winner === 1 ? $this->asciiLines('player-one-won') : $this->asciiLines('player-two-won');

        $title->push('');
        $title->push('Press ' . $this->bold($this->cyan('q')) . ' to quit or ' . $this->bold($this->cyan('r')) . ' to restart');

        $this->center($title, $this->fullWidth, $this->fullHeight)->each(fn ($line) => $this->line($line));

        return $this;
    }

    protected function titleScreen(Prong $prompt): static
    {
        $title = $this->asciiLines('prong');

        $title->push('');
        $title->push('Press ' . $this->bold($this->cyan('any key')) . ' to start');
        $title->push('');
        $title->push('Play with a friend:');
        $title->push($this->bold($this->cyan(SSH::command('prong ' . $prompt->gameId))));

        $title = $title->map(
            fn ($line, $index) => $index > $prompt->loopable(Title::class)->value->current()
                // Keep the line length so that nothing shifts if this is the longest line
                ? str_repeat(' ', mb_strwidth(Util::stripEscapeSequences($line)))
                : $line
        );

        $this->center($title, $this->fullWidth, $this->fullHeight)->each(fn ($line) => $this->line($line));

        return $this;
    }

    protected function ball(Prong $prong, Ball $ball): string
    {
        if (!$prong->game->player_one_ready || !$prong->game->player_two_ready) {
            return $this->center(
                'Waiting for other player...',
                $prong->width,
                $prong->height,
            )->map(fn ($line) => str_pad($line, $prong->width))->implode(PHP_EOL);
        }

        if ($prong->countdown > 0) {
            return $this->center(
                $this->bigNumber($prong->countdown)->map(fn ($line) => $this->bold($this->cyan($line))),
                $prong->width,
                $prong->height,
            )->map(fn ($line) => $line === '' ? str_pad($line, $prong->width) : $line)->implode(PHP_EOL);
        }

        $emptyLine = str_repeat(' ', $prong->width) . PHP_EOL;

        // Pad the top
        $output = str_repeat($emptyLine, $ball->y);

        // Draw the ball
        $output .= str_repeat(' ', $ball->x)
            . $this->cyan('●')
            . str_repeat(' ', max($prong->width - $ball->x - 1, 0))
            . PHP_EOL;

        $bottomPadding = $prong->height - $ball->y;

        if ($bottomPadding > 0) {
            $output .= str_repeat($emptyLine, $bottomPadding);
        }

        return $output;
    }

    protected function paddle(Prong $prong, $y, $color): string
    {
        $paddleHeight = 5;
        $output = str_repeat(' ' . PHP_EOL, $y);

        $output .= str_repeat($this->{$color}('█') . PHP_EOL, $paddleHeight) . ' ' . PHP_EOL;

        $extraLines = $prong->height - $y - $paddleHeight;

        if ($extraLines > 0) {
            $output .= str_repeat(' ' . PHP_EOL, $extraLines);
        }

        return $output;
    }
}
