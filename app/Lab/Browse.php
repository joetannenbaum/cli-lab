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

    public int $browsePage = 0;

    public function __construct()
    {
        $height = self::terminal()->lines() - 12;

        $this->items = collect([
            [
                'title'       => 'Resume',
                'description' => 'View my resume',
                'run'         => fn () => (new Resume)->run(),
                'command'     => 'resume',
            ],
            [
                'title'       => 'Prong',
                'description' => "Play a game of Prompts Pong with a friend (or against the computer)",
                'run'         => fn () => (new Prong)->play(),
                'command'     => 'prong',
            ],
            [
                'title'       => 'Nissan Dashboard',
                'description' => 'A terminal recreation of the dashboard of the Nissan 300 ZX (1984)',
                'run'         => fn () => (new Nissan)->run(),
                'command'     => 'nissan',
            ],
            [
                'title'       => 'Data Table',
                'description' => 'Paginated! Searchable! Jump to Page-able!',
                'run'         => fn () => (new DataTable)->prompt(),
                'command'     => 'datatable',
            ],
            [
                'title' => 'My Blog',
                'description' => 'A terminal recreation of my blog',
                'run' => fn () => (new Blog)->prompt(),
                'command' => 'blog',
            ],
        ])->chunk((int) floor($height / 10))->map(fn ($p) => $p->values())->toArray();

        $this->registerTheme(BrowseRenderer::class);

        $this->createAltScreen();

        KeyPressListener::for($this)
            ->on(['q', Key::CTRL_C], fn () => $this->terminal()->exit())
            ->on([Key::DOWN_ARROW, Key::DOWN],  fn () => $this->index = min($this->index + 1, count($this->items[$this->browsePage]) - 1))
            ->on([Key::UP_ARROW, Key::UP],  fn () => $this->index = max($this->index - 1, 0))
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
        $this->items[$this->browsePage][$this->index]['run']();
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
