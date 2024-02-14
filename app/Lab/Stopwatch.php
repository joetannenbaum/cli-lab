<?php

namespace App\Lab;

use App\Lab\Concerns\CreatesAnAltScreen;
use App\Lab\Concerns\RegistersThemes;
use App\Lab\Concerns\SetsUpAndResets;
use App\Lab\Input\KeyPressListener;
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
        $this->registerTheme();

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
        while (true) {
            usleep(1000);

            $this->elapsedMilliseconds++;

            $this->render();

            $key = KeyPressListener::once();

            if (in_array($key, ['q', Key::CTRL_C])) {
                static::terminal()->exit();
            }

            if ($key === ' ') {
                $this->laps[] = $this->elapsedMilliseconds;
            }

            if ($key === 'r') {
                $this->laps = [];
                $this->elapsedMilliseconds = 0;
            }
        }
    }

    public function value(): mixed
    {
        return null;
    }
}
