<?php

namespace App\Lab;

use App\Lab\Concerns\CreatesAnAltScreen;
use App\Lab\Concerns\Loops;
use App\Lab\Concerns\RegistersThemes;
use App\Lab\Concerns\SetsUpAndResets;
use App\Lab\Input\KeyPressListener;
use App\Lab\Prong\Ball;
use App\Lab\Prong\Paddle;
use App\Lab\Prong\Title;
use App\Lab\Renderers\ProngRenderer;
use App\Models\ProngGame;
use Illuminate\Support\Str;
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

    public ProngGame $game;

    public bool $observer = false;

    public int $playerNumber = 0;

    public function __construct(public ?string $gameId = null)
    {
        $this->registerTheme(ProngRenderer::class);

        $this->loadGame();

        $this->width = 100;
        $this->height = 26;

        $this->state = 'title';

        $this->createAltScreen();
    }

    public function play(): void
    {
        $this->setup($this->showTitle(...));
    }

    public function value(): mixed
    {
        //
    }

    public function __destruct()
    {
        if ($this->playerNumber === 1) {
            $this->game->update(['player_one' => false]);
        } elseif ($this->playerNumber === 2) {
            $this->game->update(['player_two' => false]);
        }

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

    protected function loadGame(): void
    {
        if ($this->gameId === null) {
            do {
                $this->gameId = strtoupper(Str::random(12));
            } while (ProngGame::where('game_id', $this->gameId)->exists());

            $this->game = ProngGame::create(['game_id' => $this->gameId]);

            $this->setPlayers();

            return;
        }

        $this->game = ProngGame::where('game_id', ($this->gameId))->first();

        if ($this->game === null) {
            $this->gameId = null;

            $this->loadGame();

            return;
        }

        $this->setPlayers();
    }

    protected function setPlayers(): void
    {
        if (!$this->game->player_one) {
            $this->game->update(['player_one' => true]);
            $this->playerNumber = 1;
        } elseif (!$this->game->player_two) {
            $this->game->update(['player_two' => true]);
            $this->playerNumber = 2;
            $this->observer = true;
        } else {
            // You just want to observe this game I guess
            $this->observer = true;
            $this->playerNumber = 3;
        }
    }

    protected function refreshGame(): void
    {
        $this->game = $this->game->fresh();

        if ($this->playerNumber === 1) {
            if ($this->game->player_two_position !== null) {
                $this->loopable('player2')->value->update($this->game->player_two_position);
            }
        } else {
            if ($this->game->player_one_position !== null) {
                $this->loopable('player1')->value->update($this->game->player_one_position);
            }
        }

        if ($this->observer) {
            if ($this->game->ball_x !== null && $this->game->ball_y !== null) {
                $this->loopable(Ball::class)->x = $this->game->ball_x;
                $this->loopable(Ball::class)->y = $this->game->ball_y;
            }
        }
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

            $ball = $this->loopable(Ball::class);

            $fields = ['ball_y' => $ball->y, 'ball_x' => $ball->x, 'ball_direction' => $ball->direction];

            if ($this->playerNumber === 1) {
                $fields['player_one_position'] = $this->loopable('player1')->value->current();
            } else {
                $fields['player_two_position'] = $this->loopable('player2')->value->current();
            }

            $this->game->update($fields);

            $this->refreshGame();

            $this->handleKey(KeyPressListener::once());

            $this->render();
        }, 25_000);

        $this->render();

        while (($key = static::terminal()->read()) !== null) {
            match ($key) {
                'q'         => static::terminal()->exit(),
                Key::CTRL_C => static::terminal()->exit(),
                'r'         => $this->restartGame(),
                default     => null,
            };
        }
    }

    protected function handleKey($key)
    {
        match ($key) {
            Key::CTRL_C     => static::terminal()->exit(),
            Key::UP_ARROW   => $this->loopable($this->playerNumber === 1 ? 'player1' : 'player2')->moveUp(),
            Key::DOWN_ARROW => $this->loopable($this->playerNumber === 1 ? 'player1' : 'player2')->moveDown(),
            Key::UP   => $this->loopable($this->playerNumber === 1 ? 'player1' : 'player2')->moveUp(),
            Key::DOWN => $this->loopable($this->playerNumber === 1 ? 'player1' : 'player2')->moveDown(),
            'q'         => static::terminal()->exit(),
            default         => null,
        };
    }
}
