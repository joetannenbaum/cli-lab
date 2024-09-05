<?php

namespace App\Lab;

use App\Lab\Playground\Ball;
use App\Lab\Playground\LogReader;
use App\Lab\Playground\LogWriter;
use App\Lab\Renderers\PlaygroundRenderer;
use Carbon\CarbonInterval;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Laravel\Prompts\Prompt;

class Playground extends Prompt
{
    use RegistersRenderers;
    use SetsUpAndResets;
    use Loops;

    public Ball $ball;

    public Ball $ball2;

    public Ball $ball3;

    public Ball $ball4;

    public LogReader $logReader;

    public LogWriter $logWriter;

    public function __construct()
    {
        $this->registerRenderer(PlaygroundRenderer::class);

        $this->ball = new Ball();

        $this->ball2 = new Ball();

        $this->ball3 = new Ball();

        $this->ball4 = new Ball();

        $this->logReader = new LogReader();

        $this->logWriter = new LogWriter();

        $this->registerLoopables(
            $this->ball,
            $this->ball2,
            $this->ball3,
            $this->ball4,
            $this->logReader,
            $this->logWriter,
        );

        $this->setup($this->run(...));
    }

    public function run(): void
    {
        $listener = KeyPressListener::for($this)->listenForQuit();

        $this->loop(function () use ($listener) {
            $this->render();

            $listener->once();
        }, CarbonInterval::milliseconds(25));
    }

    public function value(): mixed
    {
        return null;
    }
}
