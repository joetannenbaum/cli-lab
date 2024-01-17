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
    use DrawsAscii;
    use DrawsBigNumbers;
    use DrawsHotkeys;
    use HasMinimumDimensions;

    public int $height = 26;

    public int $width = 100;

    protected int $fullWidth;

    protected int $fullHeight;

    public function __invoke(Prong $prompt): string
    {
        return $this->minDimensions(
            width: $this->width,
            height: $this->height + 16,
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

        if ($prompt->game->winner !== null) {
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
            ->map(fn ($line) => $this->dim('│ ') . $line . $this->dim(' │'))
            ->prepend($this->dim('┌' . str_repeat('─', $this->width + 4) . '┐'))
            ->prepend('')
            ->prepend($this->gameHeader($prompt))
            ->push($this->dim('└' . str_repeat('─', $this->width + 4) . '┘'));

        $this->center($cols, $this->fullWidth, $this->fullHeight - 2)->each(
            fn ($line) => $this->line($line)
        );

        if ($prompt->game->observer) {
            // $this->hotkey('n', 'Start your own game');
        } else {
            $this->hotkey('↑', 'Move up');
            $this->hotkey('↓', 'Move down');
        }

        $this->hotkey('q', 'Quit');

        $hotkeys = collect($this->hotkeys())->implode(PHP_EOL);

        if ($prompt->game->playerNumber === 1) {
            $hotkeys = $this->bold($this->red('← You are Player 1    ')) . $hotkeys;
        } elseif ($prompt->game->playerNumber === 2) {
            $hotkeys = $hotkeys . $this->bold($this->green('    You are Player 2 →'));
        } else {
            $hotkeys = $this->bold('You are watching    ') . $hotkeys;
        }

        $this->centerHorizontally($hotkeys, $this->fullWidth)->each(fn ($line) => $this->line($line));

        return $this;
    }

    protected function gameHeader(Prong $prompt): string
    {
        if ($prompt->game->everyoneReady) {
            $speed = $prompt->loopable(Ball::class)->speed;
            $maxSpeed = $prompt->loopable(Ball::class)->maxSpeed;

            $color = match ($speed) {
                1       => 'green',
                2       => 'green',
                3       => 'yellow',
                4       => 'yellow',
                default => 'red',
            };

            return 'Speed ' . rtrim($this->{$color}(str_repeat('█ ', $speed)) . $this->dim(str_repeat('█ ', $maxSpeed - $speed)));
        }

        return 'To join this game: ' . $this->cyan(SSH::command('prong ' . $prompt->gameId));
    }

    protected function winnerScreen(Prong $prompt): static
    {
        if ($prompt->game->winner === 1) {
            // Font: Crawford2
            $title = $this->asciiLines('player-one-won');
        } elseif ($prompt->game->againstComputer) {
            $title = $this->asciiLines('computer-won');
        } else {
            $title = $this->asciiLines('player-two-won');
        }

        $title->push('');
        $title->push('Press ' . $this->bold($this->cyan('q')) . ' to quit or ' . $this->bold($this->cyan('r')) . ' to restart');

        $this->center($title, $this->fullWidth, $this->fullHeight)->each(fn ($line) => $this->line($line));

        return $this;
    }

    protected function titleScreen(Prong $prompt): static
    {
        $title = $this->asciiLines('prong');

        $title->push('');
        $title->push('Press ' . $this->bold($this->cyan('ENTER')) . ' to start');

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
        if (!$prong->game->everyoneReady) {
            return $this->center(
                [
                    'Waiting for other player...',
                    '',
                    'Press ' . $this->bold($this->cyan('c')) . ' to play against the computer',
                ],
                $this->width,
                $this->height,
            )->map(fn ($line) => str_pad($line, $this->width))->implode(PHP_EOL);
        }

        if ($prong->countdown > 0) {
            return $this->center(
                $this->bigNumber($prong->countdown)->map(fn ($line) => $this->bold($this->cyan($line))),
                $this->width,
                $this->height,
            )->map(fn ($line) => $line === '' ? str_pad($line, $this->width) : $line)->implode(PHP_EOL);
        }

        $emptyLine = str_repeat(' ', $this->width) . PHP_EOL;

        // Pad the top
        $output = str_repeat($emptyLine, $ball->y);

        // Draw the ball
        $output .= str_repeat(' ', $ball->x)
            . $this->cyan('●')
            . str_repeat(' ', max($this->width - $ball->x - 1, 0))
            . PHP_EOL;

        $bottomPadding = $this->height - $ball->y - 1;

        if ($bottomPadding > 0) {
            $output .= str_repeat($emptyLine, $bottomPadding);
        }

        return rtrim($output, PHP_EOL);
    }

    protected function paddle(Prong $prompt, $y, $color): string
    {
        $paddleHeight = $prompt->loopable('player1')->height;

        $output = str_repeat(' ' . PHP_EOL, $y);

        $output .= str_repeat($this->{$color}('█') . PHP_EOL, $paddleHeight);

        $extraLines = $this->height - $y - $paddleHeight;

        if ($extraLines > 0) {
            $output .= str_repeat(' ' . PHP_EOL, $extraLines);
        }

        return rtrim($output, PHP_EOL);
    }
}
