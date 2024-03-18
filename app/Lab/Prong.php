<?php

namespace App\Lab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersThemes;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
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

    public ProngGame $game;

    public $countdown = 3;

    public Ball $ball;

    public Paddle $player1;

    public Paddle $player2;

    public Title $title;

    protected KeyPressListener $listener;

    public function __construct(public ?string $gameId = null)
    {
        $this->registerTheme(ProngRenderer::class);

        $this->loadGame();

        $this->state = 'title';

        $this->createAltScreen();

        $this->ball = new Ball($this);
        $this->player1 = new Paddle($this);
        $this->player2 = new Paddle($this);
        $this->title = new Title($this);

        $this->listener = KeyPressListener::for($this);
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
        $this->exitAltScreen();
    }

    public function determineWinner()
    {
        if ($this->game->playerNumber !== 1) {
            return;
        }

        $this->registerLoopables($this->ball, $this->player1, $this->player2);

        $winner = $this->ball->x === 0 ? $this->getWinner($this->ball, $this->player1, 2) : $this->getWinner($this->ball, $this->player2, 1);

        if ($winner !== null) {
            $this->game->update('winner', $winner);

            Log::info('Game complete', [
                'winner' => $this->game->winner,
                'game'   => $this->game->model->toArray(),
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

        $game = ProngGame::get($this->gameId);

        if ($game !== null) {
            $this->game = $game;
        } else {
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
            $this->game->playerNumber = 1;
        } elseif (!$this->game->playerTwo) {
            $this->game->update('playerTwo', true);
            $this->game->playerNumber = 2;
        } else {
            // You just want to observe this game I guess
            $this->game->observer = true;
            $this->game->playerNumber = 3;
        }
    }

    protected function refreshGame(): void
    {
        $this->game->fresh();

        $refreshPlayers = match ($this->game->playerNumber) {
            1       => $this->updatePlayerTwoPosition(...),
            2       => $this->updatePlayerOnePosition(...),
            default => function () {
                $this->updatePlayerOnePosition();
                $this->updatePlayerTwoPosition();
            },
        };

        $refreshPlayers();

        if ($this->game->playerNumber !== 1 && $this->game->ballPositionX !== null && $this->game->ballPositionY !== null) {
            $this->ball->x = $this->game->ballPositionX;
            $this->ball->y = $this->game->ballPositionY;
            // $this->ball->speed = $this->game->ballSpeedLevel ?? 1;
        }
    }

    protected function updatePlayerOnePosition()
    {
        if ($this->game->playerOnePosition !== null) {
            $this->player1->value->update($this->game->playerOnePosition);
        }
    }

    protected function updatePlayerTwoPosition()
    {
        if ($this->game->playerTwoPosition !== null) {
            $this->player2->value->update($this->game->playerTwoPosition);
        }
    }

    protected function getWinner(Ball $ball, Paddle $player, int $winnerNumber)
    {
        $okZone = $ball->y >= $player->value->current() && $ball->y <= $player->value->current() + $this->player1->height;

        return $okZone ? null : $winnerNumber;
    }

    protected function showTitle()
    {
        $this->registerLoopable($this->title);

        $this->render();

        while (($key = static::terminal()->read()) !== null) {
            match ($key) {
                'q', Key::CTRL_C => static::terminal()->exit(),
                default     => null,
            };

            if ($key === Key::ENTER) {
                $this->title->hide();

                $this->loop(function () {
                    $this->render();

                    if ($this->title->value->current() === 0) {
                        return false;
                    }
                }, 50_000);

                $this->clearRegisteredLoopables();

                break;
            }
        }

        $this->state = 'playing';

        match ($this->game->playerNumber) {
            1       => $this->game->update('playerOneReady', true),
            2       => $this->game->update('playerTwoReady', true),
            default => null,
        };

        $this->playGame();
    }

    protected function restartGame()
    {
        $this->game->reset();

        match ($this->game->playerNumber) {
            1       => $this->game->update('playerOneReady', true),
            2       => $this->game->update('playerTwoReady', true),
            default => null,
        };

        $this->countdown = 3;

        $this->clearRegisteredLoopables();
        $this->playGame();
    }

    protected function onBallDirectionChange(Ball $ball, int $nextY)
    {
        if (!$this->game->againstComputer) {
            return;
        }

        if ($ball->direction !== 1) {
            // We only care about the ball going in the computer's direction
            return;
        }

        // Make sure the computer gets at least 4 hits before it starts to possibly miss
        if ($ball->directionChangeCount <= 4 || Lottery::odds(9, 10)->choose()) {
            $this->player2->value->to($nextY);
        } else {
            $offset = Lottery::odds(1, 2)->choose() ? 1 : -1;
            $this->player2->value->to($nextY - (6 * $offset));
        }
    }

    protected function playGame()
    {
        $this->registerLoopables($this->ball, $this->player1, $this->player2);

        $this->listener->listenForQuit()->on('c', fn () => $this->game->againstComputer = true);

        while (!$this->game->playerOneReady || !$this->game->playerTwoReady) {
            $this->refreshGame();

            $this->render();

            $this->listener->once();

            if ($this->game->againstComputer) {
                break;
            }

            usleep(50_000);
        }

        $this->ball->onDirectionChange($this->onBallDirectionChange(...));
        $this->ball->start();

        $this->game->everyoneReady = true;

        $this->listener->clearExisting()
            ->listenForQuit()
            ->onUp(fn () => $this->move(-1))
            ->onDown(fn () => $this->move(1));

        while ($this->countdown > 0) {
            $this->render();

            $this->listener->once();

            $this->countdown--;

            sleep(1);
        }

        $this->loop(function (self $loopable) {
            if ($this->game->winner !== null) {
                return false;
            }

            // $loopable->sleepFor($this->game->ballSpeed);

            // $this->game->updateMany([
            //     'ballPositionX' => $this->ball->x,
            //     'ballPositionY' => $this->ball->y,
            //     'ballDirection' => $this->ball->direction,
            // ]);

            // match ($this->game->playerNumber) {
            //     1       => $this->game->update('playerOnePosition', $this->player1->value->current()),
            //     2       => $this->game->update('playerTwoPosition', $this->player2->value->current()),
            //     default => null,
            // };

            // $this->refreshGame();

            $this->listener->once();

            $this->render();
        }, 25_000);
        // }, $this->game->ballSpeed);

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

    protected function move(int $direction)
    {
        $player = match ($this->game->playerNumber) {
            1       => $this->player1,
            2       => $this->player2,
            default => null,
        };

        if ($player !== null) {
            match ($direction) {
                -1      => $player->moveUp(),
                1       => $player->moveDown(),
                default => null,
            };
        } else {
            // match ($key) {
            //     'n' => $this->newGame(),
            //     default => null,
            // };
        }
    }
}
