<?php

namespace App\Lab\Renderers;

use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsArt;
use Chewie\Concerns\DrawsBigNumbers;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use Chewie\Output\Lines;
use App\Lab\ProngSimple;
use App\Lab\ProngSimple\Paddle;
use Chewie\Concerns\CapturesOutput;
use Chewie\Concerns\DrawsBigText;
use Illuminate\Support\Collection;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;

use function Chewie\stripEscapeSequences;

class ProngSimpleRenderer extends Renderer
{
    use Aligns;
    use DrawsArt;
    use DrawsBigNumbers;
    use DrawsHotkeys;
    use HasMinimumDimensions;
    use DrawsBoxes;
    use CapturesOutput;
    use DrawsBigText;

    protected int $width;

    protected int $height;

    public function __invoke(ProngSimple $prompt): string
    {
        $this->width = $prompt->terminal()->cols() - 2;
        $this->height = $prompt->terminal()->lines() - 4;

        $this->setFontDir('alphabet');

        return match ($prompt->state) {
            'title' => $this->renderTitle($prompt),
            'winner' => $this->renderWinner($prompt),
            default => $this->renderGame($prompt),
        };
    }

    protected function gameHeader(ProngSimple $prompt): string
    {
        $speed = $prompt->ball->speed;
        $maxSpeed = $prompt->ball->maxSpeed;
        $block = '█';

        $color = match ($speed) {
            1       => 'green',
            2       => 'yellow',
            3       => 'yellow',
            default => 'red',
        };

        $bars = array_fill(0, $speed, $this->{$color}($block));
        $bars = array_pad($bars, $maxSpeed, $this->dim($block));

        return 'Speed ' . implode(' ', $bars);
    }

    protected function renderWinner(ProngSimple $prompt): static
    {
        $winner = $prompt->winner === 1 ? 'you' : 'computer';

        // Font: Crawford2
        $title = $this->bigText($winner . ' won');

        $title->push('');
        $title->push('Press ' . $this->bold($this->cyan('q')) . ' to quit or ' . $this->bold($this->cyan('r')) . ' to restart');

        $this->center($title, $this->width, $this->height)->each($this->line(...));

        return $this;
    }

    protected function renderTitle(ProngSimple $prompt): static
    {
        $title = $this->bigText('prong')
            ->push('')
            ->push('Press ' . $this->bold($this->cyan('ENTER')) . ' to start')
            ->map(
                fn ($line, $index) => $index > $prompt->title->value->current()
                    // Keep the line length so that nothing shifts if this is the longest line
                    ? str_repeat(' ', mb_strwidth(stripEscapeSequences($line)))
                    : $line
            );

        $this->center($title, $this->width, $this->height)->each($this->line(...));

        return $this;
    }

    protected function renderGame(ProngSimple $prompt): static
    {
        $paddle1 = $this->paddle($prompt->player1, 'red');
        $paddle2 = $this->paddle($prompt->computer, 'green');

        $center = ($prompt->countdown > 0) ? $this->countdown($prompt) : $this->ball($prompt);

        $linesFromCols = Lines::fromColumns([$paddle1, $center, $paddle2])->lines();

        $this->padVertically($linesFromCols, $prompt->gameHeight);

        $boxed = $this->captureOutput(fn () =>  $this->box('', $linesFromCols->implode(PHP_EOL)));

        $lines = collect(explode(PHP_EOL, $boxed));

        $lines->prepend('');
        $lines->prepend($this->gameHeader($prompt));

        $this->hotkey('↑ ↓', 'Move paddle');
        $this->hotkey('q', 'Quit');

        $hotkeys = collect([
            $this->bold($this->red('← You are Player 1')),
            implode(PHP_EOL, $this->hotkeys())
        ])->implode(str_repeat(' ', 4));

        $hotkeyLines = $this->centerHorizontally($hotkeys, $this->width);

        $lines->push(...$hotkeyLines);

        $this->center($lines, $this->width, $this->height - 2)->each($this->line(...));

        return $this;
    }

    protected function countdown(ProngSimple $prompt): Collection
    {
        return $this->center(
            $this->bigNumber($prompt->countdown)->map(fn ($line) => $this->bold($this->cyan($line))),
            $prompt->gameWidth,
            $prompt->gameHeight,
        );
    }

    protected function ball(ProngSimple $prompt): Collection
    {
        $ballLine = str_repeat(' ', $prompt->ball->x->current())
            . $this->cyan('●')
            . str_repeat(' ', max($prompt->gameWidth - $prompt->ball->x->current() - 1, 0));

        // Pad the top
        return collect(array_fill(0, $prompt->ball->y, ''))->push($ballLine);
    }

    protected function paddle(Paddle $paddle, $color): Collection
    {
        $paddleHeight = $paddle->height;

        return collect(array_fill(0, $paddle->value->current(), ''))->merge(
            array_fill(0, $paddleHeight, $this->{$color}('█')),
        );
    }
}
