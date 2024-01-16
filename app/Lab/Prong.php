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
use App\Lab\State\ProngGame;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Lottery;
use Illuminate\Support\Str;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Prong extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersThemes;
    use SetsUpAndResets;

    public int $height = 26;

    public int $width = 100;

    public ?int $winner = null;

    public ProngGame $game;

    public bool $observer = false;

    public bool $againstComputer = false;

    public bool $everyoneReady = false;

    public int $playerNumber = 0;

    public $countdown = 3;

    public int $ballSpeed;

    public int $defaultBallSpeed = 25_000;

    public function __construct(public ?string $gameId = null)
    {
        $this->registerTheme(ProngRenderer::class);

        $this->ballSpeed = $this->defaultBallSpeed;

        $this->loadGame();

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
        // match ($this->playerNumber) {
        //     1       => $this->game->update('playerOne', false),
        //     2       => $this->game->update('playerTwo', false),
        //     default => null,
        // };

        $this->exitAltScreen();
    }

    public function determineWinner()
    {
        if ($this->playerNumber !== 1) {
            return;
        }

        /** @var Ball $ball */
        $ball = $this->loopable(Ball::class);

        /** @var Paddle $player1 */
        $player1 = $this->loopable('player1');

        /** @var Paddle $player2 */
        $player2 = $this->loopable('player2');

        $this->winner = $ball->x === 0 ? $this->getWinner($ball, $player1, 2) : $this->getWinner($ball, $player2, 1);

        if ($this->winner !== null) {
            $this->game->update('winner', $this->winner);

            Log::info('Game complete', [
                'winner' => $this->winner,
                'game'   => $this->game->toArray(),
            ]);
        }
    }

    protected function loadGame(): void
    {
        if ($this->gameId === null) {
            do {
                $this->gameId = strtoupper(Str::random(12));
            } while (ProngGame::exists($this->gameId));

            $this->game = ProngGame::create($this->gameId);

            $this->setPlayers();

            return;
        }

        $this->game = ProngGame::get($this->gameId);

        if ($this->game === null) {
            $this->gameId = null;

            $this->loadGame();

            return;
        }

        $this->setPlayers();
    }

    protected function setPlayers(): void
    {
        // TODO indicate that this is against the computer or not so no one can join and mess up the game

        if (!$this->game->playerOne) {
            $this->game->update('playerOne', true);
            $this->playerNumber = 1;
        } elseif (!$this->game->playerTwo) {
            $this->game->update('playerTwo', true);
            $this->playerNumber = 2;
        } else {
            // You just want to observe this game I guess
            $this->observer = true;
            $this->playerNumber = 3;
        }
    }

    protected function refreshGame(): void
    {
        $this->game->fresh();

        $refreshPlayers = match ($this->playerNumber) {
            1       => $this->updatePlayerTwoPosition(...),
            2       => $this->updatePlayerOnePosition(...),
            default => function () {
                $this->updatePlayerOnePosition();
                $this->updatePlayerTwoPosition();
            },
        };

        $refreshPlayers();

        if ($this->playerNumber !== 1 && $this->game->ball_x !== null && $this->game->ball_y !== null) {
            $this->loopable(Ball::class)->x = $this->game->ball_x;
            $this->loopable(Ball::class)->y = $this->game->ball_y;
            $this->loopable(Ball::class)->speed = $this->game->ball_speed ?? 1;
        }

        $this->winner = $this->game->winner;
    }

    protected function updatePlayerOnePosition()
    {
        if ($this->game->player_one_position !== null) {
            $this->loopable('player1')->value->update($this->game->player_one_position);
        }
    }

    protected function updatePlayerTwoPosition()
    {
        if ($this->game->player_two_position !== null) {
            $this->loopable('player2')->value->update($this->game->player_two_position);
        }
    }

    protected function getWinner(Ball $ball, Paddle $player, int $winnerNumber)
    {
        $okZone = $ball->y >= $player->value->current() && $ball->y <= $player->value->current() + $this->loopable('player1')->height;

        return $okZone ? null : $winnerNumber;
    }

    protected function showTitle()
    {
        $this->registerLoopable(Title::class);

        $this->render();

        while (($key = static::terminal()->read()) !== null) {
            match ($key) {
                'q', Key::CTRL_C => static::terminal()->exit(),
                default     => null,
            };

            if ($key === Key::ENTER) {
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
        }

        $this->state = 'playing';

        match ($this->playerNumber) {
            1       => $this->game->update('playerOneReady', true),
            2       => $this->game->update('playerTwoReady', true),
            default => null,
        };

        $this->playGame();
    }

    protected function restartGame()
    {
        $this->winner = null;

        $this->game = $this->game->reset();

        match ($this->playerNumber) {
            1       => $this->game->update('playerOneReady', true),
            2       => $this->game->update('playerTwoReady', true),
            default => null,
        };

        $this->ballSpeed = $this->defaultBallSpeed;
        $this->everyoneReady = false;

        $this->countdown = 3;

        $this->clearRegisteredLoopables();
        $this->playGame();
    }

    protected function onBallDirectionChange(Ball $ball, int $nextY)
    {
        if (!$this->againstComputer) {
            return;
        }

        if ($ball->direction !== 1) {
            // We only care about the ball going in the computer's direction
            return;
        }

        // Make sure the computer gets at least 4 hits before it starts to possibly miss
        if ($ball->directionChangeCount <= 4 || Lottery::odds(9, 10)->choose()) {
            $this->loopable('player2')->value->to($nextY);
        } else {
            $offset = Lottery::odds(1, 2)->choose() ? 1 : -1;
            $this->loopable('player2')->value->to($nextY - (6 * $offset));
        }
    }

    protected function playGame()
    {
        $this->registerLoopable(Paddle::class, 'player1');
        $this->registerLoopable(Paddle::class, 'player2');
        $this->registerLoopable(Ball::class);

        while (!$this->game->playerOneReady || !$this->game->playerTwoReady) {
            $this->refreshGame();

            $this->render();

            match (KeyPressListener::once()) {
                'q', Key::CTRL_C  => static::terminal()->exit(),
                'c'               => $this->againstComputer = true,
                default           => null,
            };

            if ($this->againstComputer) {
                break;
            }

            usleep(50_000);
        }

        $this->loopable(Ball::class)->onDirectionChange($this->onBallDirectionChange(...));
        $this->loopable(Ball::class)->start();

        $this->everyoneReady = true;

        while ($this->countdown > 0) {
            $this->render();

            $this->handleKey(KeyPressListener::once());

            $this->countdown--;

            usleep(1_000_000);
        }

        $this->loop(function ($loopable) {
            if ($this->winner !== null) {
                return false;
            }

            $loopable->sleepFor($this->ballSpeed);

            $ball = $this->loopable(Ball::class);

            $this->game->updateMany([
                'ballPositionX' => $ball->x,
                'ballPositionY' => $ball->y,
                'ballDirection' => $ball->direction,
            ]);

            match ($this->playerNumber) {
                1       => $this->game->update('playerOnePosition', $this->loopable('player1')->value->current()),
                2       => $this->game->update('playerTwoPosition', $this->loopable('player2')->value->current()),
                default => null,
            };

            $this->refreshGame();

            $this->handleKey(KeyPressListener::once());

            $this->render();
        }, $this->ballSpeed);

        $this->game->update('playerOneReady', false);
        $this->game->update('playerTwoReady', false);

        $this->render();

        while (($key = static::terminal()->read()) !== null) {
            match ($key) {
                'q', Key::CTRL_C => static::terminal()->exit(),
                'r'              => $this->restartGame(),
                default          => null,
            };
        }
    }

    protected function handleKey($key)
    {
        $playerKey = match ($this->playerNumber) {
            1       => 'player1',
            2       => 'player2',
            default => null,
        };

        match ($key) {
            'q', Key::CTRL_C => static::terminal()->exit(),
            default          => null,
        };

        if ($playerKey !== null) {
            match ($key) {
                Key::UP_ARROW, Key::UP     => $this->loopable($playerKey)->moveUp(),
                Key::DOWN_ARROW, Key::DOWN => $this->loopable($playerKey)->moveDown(),
                default                    => null,
            };
        } else {
            // match ($key) {
            //     'n' => $this->newGame(),
            //     default => null,
            // };
        }
    }
}
