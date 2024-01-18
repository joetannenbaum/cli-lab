<?php

namespace App\Lab;

use App\Lab\Concerns\CreatesAnAltScreen;
use App\Lab\Concerns\Loops;
use App\Lab\Concerns\RegistersThemes;
use App\Lab\Concerns\SetsUpAndResets;
use App\Lab\Input\KeyPressListener;
use App\Lab\Renderers\BrowseRenderer;
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

    public function __construct()
    {
        $this->items = [
            [
                'title'       => 'Resume',
                'description' => 'View my resume',
                'run'         => fn () => (new Resume)->run(),
            ],
            [
                'title'       => 'Prong',
                'description' => "Play a game of Prompts Pong with a friend (or against the computer)",
                'run'         => fn () => (new Prong)->play(),
            ],
            [
                'title'       => 'Nissan Dashboard',
                'description' => 'A terminal recreation of the dashboard of the Nissan 300 ZX (1984)',
                'run'         => fn () => (new Nissan)->run(),
            ],
        ];

        $this->registerTheme(BrowseRenderer::class);

        $this->createAltScreen();

        KeyPressListener::for($this)
            ->on(['q', Key::CTRL_C], fn () => $this->terminal()->exit())
            ->on([Key::UP, Key::UP_ARROW], fn () => $this->index = max(0, $this->index - 1))
            ->on([Key::DOWN, Key::DOWN_ARROW], fn () => $this->index = min(count($this->items) - 1, $this->index + 1))
            ->on(Key::ENTER, $this->onEnter(...))
            ->listen();
    }

    public function onEnter(): void
    {
        $this->exitAltScreen();
        $this->items[$this->index]['run']();
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
