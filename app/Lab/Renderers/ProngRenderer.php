<?php

namespace App\Lab\Renderers;

use App\Lab\Concerns\Aligns;
use App\Lab\Concerns\DrawsHotkeys;
use App\Lab\Output\Lines;
use App\Lab\Prong;
use App\Lab\Prong\Ball;
use App\Lab\Prong\Title;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;
use Illuminate\Support\Str;

class ProngRenderer extends Renderer
{
    use Aligns;
    use DrawsHotkeys;

    protected int $fullWidth;

    protected int $fullHeight;

    public function __invoke(Prong $prompt): string
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
        $title = $prompt->winner === 1 ? $this->player1Won() : $this->player2Won();
        $title = collect(explode(PHP_EOL, $title));

        $title->push('');
        $title->push('Press ' . $this->bold($this->cyan('q')) . ' to quit or ' . $this->bold($this->cyan('r')) . ' to restart');

        $this->center($title, $this->fullWidth, $this->fullHeight)->each(fn ($line) => $this->line($line));

        return $this;
    }

    protected function titleScreen(Prong $prompt): static
    {
        $title = collect(explode(PHP_EOL, $this->title()));

        $title->push('');
        $title->push('Press ' . $this->bold($this->cyan('any key')) . ' to start');
        $title->push('');
        $title->push('Your Game ID is: ' . $this->bold($this->cyan($prompt->gameId)));

        // - 1... why?
        $title = $title->map(fn ($line, $index) => $index - 1 > $prompt->loopable(Title::class)->value->current() ? '' : $line);

        $this->center($title, $this->fullWidth, $this->fullHeight)->each(fn ($line) => $this->line($line));

        return $this;
    }

    protected function ball(Prong $prong, Ball $ball): string
    {
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

    protected function player1Won()
    {
        return <<<TEXT
         ____  _       ____  __ __    ___  ____        ___   ____     ___      __    __   ___   ____   __
         |    \| |     /    ||  |  |  /  _]|    \      /   \ |    \   /  _]    |  |__|  | /   \ |    \ |  |
         |  o  ) |    |  o  ||  |  | /  [_ |  D  )    |     ||  _  | /  [_     |  |  |  ||     ||  _  ||  |
         |   _/| |___ |     ||  ~  ||    _]|    /     |  O  ||  |  ||    _]    |  |  |  ||  O  ||  |  ||__|
        |  |  |     ||  _  ||___, ||   [_ |    \     |     ||  |  ||   [_     |  `  '  ||     ||  |  | __
         |  |  |     ||  |  ||     ||     ||  .  \    |     ||  |  ||     |     \      / |     ||  |  ||  |
         |__|  |_____||__|__||____/ |_____||__|\_|     \___/ |__|__||_____|      \_/\_/   \___/ |__|__||__|
        TEXT;
    }

    protected function player2Won()
    {
        return <<<TEXT
         ____  _       ____  __ __    ___  ____       ______  __    __   ___       __    __   ___   ____   __
         |    \| |     /    ||  |  |  /  _]|    \     |      ||  |__|  | /   \     |  |__|  | /   \ |    \ |  |
         |  o  ) |    |  o  ||  |  | /  [_ |  D  )    |      ||  |  |  ||     |    |  |  |  ||     ||  _  ||  |
         |   _/| |___ |     ||  ~  ||    _]|    /     |_|  |_||  |  |  ||  O  |    |  |  |  ||  O  ||  |  ||__|
        |  |  |     ||  _  ||___, ||   [_ |    \       |  |  |  `  '  ||     |    |  `  '  ||     ||  |  | __
         |  |  |     ||  |  ||     ||     ||  .  \      |  |   \      / |     |     \      / |     ||  |  ||  |
         |__|  |_____||__|__||____/ |_____||__|\_|      |__|    \_/\_/   \___/       \_/\_/   \___/ |__|__||__|
        TEXT;
    }

    protected function title()
    {
        return <<<TEXT
         ____  ____   ___   ____    ____
        |    \|    \ /   \ |    \  /    |
        |  o  )  D  )     ||  _  ||   __|
        |   _/|    /|  O  ||  |  ||  |  |
        |  |  |    \|     ||  |  ||  |_ |
        |  |  |  .  \     ||  |  ||     |
        |__|  |__|\_|\___/ |__|__||___,_|
        TEXT;
    }
}
