<?php

namespace App\Lab;

use App\Lab\Renderers\StopwatchRenderer;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\RegistersThemes;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Stopwatch extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;
    use SetsUpAndResets;
    use TypedValue;

    public int $elapsedMilliseconds = 0;

    public array $laps = [];

    public function __construct()
    {
        $this->registerTheme(StopwatchRenderer::class);

        $this->createAltScreen();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function run(): void
    {
        $this->setUp($this->start(...));
    }

    public function start(): void
    {
        $listener = KeyPressListener::for($this)->listenForQuit()->on(' ', fn () => $this->laps[] = $this->elapsedMilliseconds)->on('r', fn () => $this->laps = []);

        while (true) {
            usleep(1000);

            $this->elapsedMilliseconds++;

            $this->render();

            $listener->once();

            // if (in_array($key, ['q', Key::CTRL_C])) {
            //     static::terminal()->exit();
            // }

            // if ($key === ' ') {
            //     $this->laps[] = $this->elapsedMilliseconds;
            // }

            // if ($key === 'r') {
            //     $this->laps = [];
            //     $this->elapsedMilliseconds = 0;
            // }
        }
    }

    public function value(): mixed
    {
        return null;
    }
}
