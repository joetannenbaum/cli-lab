<?php

namespace App\Lab;

use App\Lab\Concerns\CreatesAnAltScreen;
use App\Lab\Concerns\Loops;
use App\Lab\Concerns\RegistersThemes;
use App\Lab\Concerns\SetsUpAndResets;
use App\Lab\Input\KeyPressListener;
use App\Lab\Renderers\BrowseRenderer;
use Illuminate\Support\Facades\Artisan;
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

        $this->registerTheme(BrowseRenderer::class);

        $this->createAltScreen();

        KeyPressListener::for($this)
            ->on(['q', Key::CTRL_C], fn () => $this->terminal()->exit())
            ->on([Key::DOWN_ARROW, Key::DOWN], fn () => $this->index = min($this->index + 1, count($this->items[$this->browsePage]) - 1))
            ->on([Key::UP_ARROW, Key::UP], fn () => $this->index = max($this->index - 1, 0))
            ->on([Key::RIGHT_ARROW, Key::RIGHT], function () {
                $this->browsePage = min(count($this->items) - 1, $this->browsePage + 1);
                $this->index = 0;
            })
            ->on([Key::LEFT_ARROW, Key::LEFT], function () {
                $this->browsePage = max(0, $this->browsePage - 1);
                $this->index = 0;
            })
            ->on(Key::ENTER, $this->onEnter(...))
            ->listen();
    }

    public function onEnter(): void
    {
        $this->exitAltScreen();

        $class = $this->items[$this->browsePage][$this->index]['class'];

        app($class)->runLab(true);
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
