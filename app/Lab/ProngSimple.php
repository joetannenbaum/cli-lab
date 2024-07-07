<?php

namespace App\Lab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use App\Lab\ProngSimple\Ball;
use App\Lab\ProngSimple\Paddle;
use App\Lab\ProngSimple\Title;
use App\Lab\Renderers\ProngSimpleRenderer;
use App\Lab\State\ProngGame;
use Chewie\Concerns\RegistersRenderers;
use Illuminate\Support\Lottery;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class ProngSimple extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersRenderers;
    use SetsUpAndResets;

    public $countdown;

    public Ball $ball;

    public Paddle $player1;

    public Paddle $computer;

    public Title $title;

    public int $gameHeight = 26;

    public int $gameWidth = 100;

    public ?int $winner = null;

    protected KeyPressListener $listener;

    protected int $tickSpeed = 25_000;

    public function __construct()
    {
        $this->registerRenderer(ProngSimpleRenderer::class);

        $this->state = 'title';

        $this->createAltScreen();

        $this->title = new Title($this);

        $this->listener = KeyPressListener::for($this);
    }

    public function play(): void
    {
        $this->setup($this->showTitle(...));
    }

    public function value(): mixed
    {
        return null;
    }

    public function determineWinner()
    {
        $winner = match ($this->ball->x->current()) {
            0 => $this->getWinner($this->player1, 2),
            default => $this->getWinner($this->computer, 1),
        };

        if ($winner !== null) {
            $this->state = 'winner';
            $this->winner = $winner;
        }
    }

    protected function getWinner(Paddle $player, int $winnerNumber)
    {
        $paddleStart = $player->value->current();
        $paddleEnd = $player->value->current() + $player->height;

        $isHittingPaddle = $this->ball->y >= $paddleStart && $this->ball->y <= $paddleEnd;

        return $isHittingPaddle ? null : $winnerNumber;
    }

    protected function showTitle()
    {
        $this->registerLoopable($this->title);

        $this->render();

        $this->listener->clearExisting()->listenForQuit()->on(Key::ENTER, fn () => false)->listenNow();

        $this->title->hide();

        $this->loop(function () {
            $this->render();

            return $this->title->value->current() > 0;
        });

        $this->startGame();
    }

    protected function startGame()
    {
        $this->countdown = 3;
        $this->winner = null;

        $this->ball = new Ball($this);
        $this->player1 = new Paddle($this);
        $this->computer = new Paddle($this);

        $this->state = 'playing';

        $this->clearRegisteredLoopables();
        $this->playGame();
    }

    protected function increaseBallSpeed()
    {
        $this->ball->speed = min($this->ball->speed + 1, $this->ball->maxSpeed);
    }

    protected function shrinkPaddle()
    {
        $this->player1->shrink();
        $this->computer->shrink();
    }

    protected function calculateComputerPosition()
    {
        if ($this->ball->direction !== 1) {
            // We only care about the ball going in the computer's direction
            return;
        }

        $nextY = collect($this->ball->steps)->last();

        if (!Lottery::odds(4, 5)->choose()) {
            $nextY -= $this->computer->height + 1;
        }

        $this->computer->value->to($nextY);
    }

    protected function playGame()
    {
        $this->listener
            ->clearExisting()
            ->listenForQuit();

        while ($this->countdown > 0) {
            $this->render();
            $this->listener->once();
            $this->countdown--;
            sleep(1);
        }

        $this->registerLoopables($this->ball, $this->player1, $this->computer);

        $this->ball->onDirectionChange(
            cb: $this->calculateComputerPosition(...),
            skipFirst: false,
        );

        $this->ball->onDirectionChange($this->determineWinner(...));
        $this->ball->onDirectionChange($this->increaseBallSpeed(...), 2);
        $this->ball->onDirectionChange($this->shrinkPaddle(...), 4);

        $this->ball->start();

        $this->listener
            ->onUp($this->player1->moveUp(...))
            ->onDown($this->player1->moveDown(...));

        $this->loop(function (self $loopable) {
            $this->render();

            if ($this->winner !== null) {
                return false;
            }

            $speedAdjustment = ($this->ball->speed - 1) * 4000;

            $loopable->sleepFor($this->tickSpeed - $speedAdjustment);

            $this->listener->once();
        }, $this->tickSpeed);

        $this->listener->clearExisting()->listenForQuit()->on('r', fn () => $this->startGame())->listenNow();
    }
}
