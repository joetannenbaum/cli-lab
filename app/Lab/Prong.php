<?php

namespace App\Lab;

use App\Lab\Concerns\CreatesAnAltScreen;
use App\Lab\Concerns\Loops;
use App\Lab\Concerns\SetsUpAndResets;
use App\Lab\Input\KeyPressListener;
use App\Lab\Concerns\RegistersThemes;
use App\Lab\Prong\Ball;
use App\Lab\Prong\Paddle;
use App\Lab\Prong\Title;
use App\Lab\Renderers\ProngRenderer;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Prong extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersThemes;
    use SetsUpAndResets;

    public int $height;

    public int $width;

    public ?int $winner = null;

    public function __construct()
    {
        $this->registerTheme(ProngRenderer::class);

        $this->width = $this->terminal()->cols() - 3;
        $this->height = $this->terminal()->lines() - 8;

        $this->state = 'title';

        $this->createAltScreen();
    }

    public function play()
    {
        $this->setup($this->showTitle(...));
    }

    public function value(): mixed
    {
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function determineWinner()
    {
        /** @var Ball $ball */
        $ball = $this->loopable(Ball::class);

        /** @var Paddle $player1 */
        $player1 = $this->loopable('player1');

        /** @var Paddle $player2 */
        $player2 = $this->loopable('player2');

        $this->winner = $ball->x === 0 ? $this->getWinner($ball, $player1, 2) : $this->getWinner($ball, $player2, 1);
    }

    protected function getWinner(Ball $ball, Paddle $player, int $winnerNumber)
    {
        $okZone = $ball->y >= $player->value->current() && $ball->y <= $player->value->current() + 5;

        return $okZone ? null : $winnerNumber;
    }

    protected function showTitle()
    {
        $this->registerLoopable(Title::class);

        $this->render();

        while (static::terminal()->read() !== null) {
            $this->loopable(Title::class)->hide();

            $this->loop(function () {
                $this->render();

                if ($this->loopable(Title::class)->value->current() === 0) {
                    return false;
                }
            }, 50_000);

            $this->clearRegisteredLoopables();

            break;
        }

        $this->state = 'playing';

        $this->playGame();
    }

    protected function restartGame()
    {
        $this->winner = null;
        $this->clearRegisteredLoopables();
        $this->playGame();
    }

    protected function playGame()
    {
        $this->registerLoopable(Paddle::class, 'player1');
        $this->registerLoopable(Paddle::class, 'player2');
        $this->registerLoopable(Ball::class);

        $this->loopable(Ball::class)->start();

        $this->loop(function () {
            if ($this->winner !== null) {
                return false;
            }

            $this->handleKey(KeyPressListener::once());

            $this->render();
        }, 25_000);

        $this->render();

        while (($key = static::terminal()->read()) !== null) {
            match ($key) {
                'q' => static::terminal()->exit(),
                'r' => $this->restartGame(),
                default => null,
            };
        }
    }

    protected function handleKey($key)
    {
        match ($key) {
            Key::CTRL_C => static::terminal()->exit(),
            'w'         => $this->loopable('player1')->moveUp(),
            's'         => $this->loopable('player1')->moveDown(),
            'i'         => $this->loopable('player2')->moveUp(),
            'k'         => $this->loopable('player2')->moveDown(),
            default     => null,
        };
    }
}
