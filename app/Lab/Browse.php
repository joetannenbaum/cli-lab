<?php

namespace App\Lab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersThemes;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Illuminate\Support\Facades\File;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Browse extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersThemes;
    use SetsUpAndResets;
    use TypedValue;

    public array $items = [];

    public int $index = 0;

    public int $browsePage = 0;

    public function __construct()
    {
        $height = self::terminal()->lines() - 12;

        $commands = File::json(storage_path('app/lab-commands.json'));

        $this->items = collect($commands['commands'])
            ->chunk((int) floor($height / 10))
            ->map(fn ($p) => $p->values())
            ->toArray();

        $this->registerTheme();

        $this->createAltScreen();

        KeyPressListener::for($this)
            ->listenForQuit()
            ->onDown(fn () => $this->index = min($this->index + 1, count($this->items[$this->browsePage]) - 1))
            ->onUp(fn () => $this->index = max($this->index - 1, 0))
            ->onRight(function () {
                $this->index = 0;
                $this->browsePage = min(count($this->items) - 1, $this->browsePage + 1);
            })
            ->onLeft(function () {
                $this->index = 0;
                $this->browsePage = max(0, $this->browsePage - 1);
            })
            ->on(Key::ENTER, $this->onEnter(...))
            ->listen();
    }

    public function onEnter(): void
    {
        $this->exitAltScreen();

        $class = $this->items[$this->browsePage][$this->index]['class'];

        app($class)->runLab();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function run()
    {
        $this->prompt();
    }

    public function value(): mixed
    {
        return null;
    }
}
