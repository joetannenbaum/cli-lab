<?php

namespace App\Lab;

use App\Lab\Concerns\CreatesAnAltScreen;
use App\Lab\Concerns\SetsUpAndResets;
use App\Lab\Concerns\RegistersThemes;
use App\Lab\Input\KeyPressListener;
use App\Lab\Renderers\BlogRenderer;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Blog extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;
    use SetsUpAndResets;
    use TypedValue;

    public array $post;

    public array $posts;

    public int $scrollPosition = 0;

    public int $scrollAmount = 5;

    public int $browsePage = 0;

    public int $browseSelected = 0;

    public string $state = 'browse';

    public function __construct(string $slug = null)
    {
        $this->registerTheme(BlogRenderer::class);

        $this->createAltScreen();

        $posts = Cache::remember('blog:posts', CarbonInterval::day(), fn () => Http::get('http://127.0.0.1:8000/api/cli-lab/posts')->json());

        $height = self::terminal()->lines() - 12;

        $this->posts = collect($posts)->chunk((int) floor($height / 3))->map(fn ($p) => $p->values())->toArray();

        if ($slug) {
            $this->enterReadingMode($slug);
        } else {
            $this->enterBrowsingMode();
        }
    }

    public function scrollDown(): void
    {
        $this->scrollPosition += $this->scrollAmount;
    }

    public function scrollUp(): void
    {
        $this->scrollPosition -= $this->scrollAmount;

        if ($this->scrollPosition < 0) {
            $this->scrollPosition = 0;
        }
    }

    public function listenForReadingKeys()
    {
        KeyPressListener::for($this)
            ->clearExisting()
            ->on([Key::DOWN_ARROW, Key::DOWN],  $this->scrollDown(...))
            ->on([Key::UP_ARROW, Key::UP],  $this->scrollUp(...))
            ->listenForQuit()
            ->on('b', $this->enterBrowsingMode(...))
            ->listen();
    }

    public function listenForBrowseKeys()
    {
        KeyPressListener::for($this)
            ->clearExisting()
            ->listenForQuit()
            ->on([Key::DOWN_ARROW, Key::DOWN],  fn () => $this->browseSelected = min($this->browseSelected + 1, count($this->posts[$this->browsePage]) - 1))
            ->on([Key::UP_ARROW, Key::UP],  fn () => $this->browseSelected = max($this->browseSelected - 1, 0))
            ->on([Key::LEFT_ARROW, Key::LEFT],  function () {
                $this->browsePage = max($this->browsePage - 1, 0);
                $this->browseSelected = 0;
            })
            ->on([Key::RIGHT_ARROW, Key::RIGHT],  function () {
                $this->browsePage = min($this->browsePage + 1, count($this->posts) - 1);
                $this->browseSelected = 0;
            })
            ->on(Key::ENTER,  function () {
                $this->enterReadingMode($this->posts[$this->browsePage][$this->browseSelected]['slug']);
            })
            ->listen();
    }

    protected function enterReadingMode($slug): void
    {
        $this->state = 'reading';

        $this->post = Cache::remember(
            "blog:post:{$slug}",
            CarbonInterval::day(),
            fn () =>
            Http::get('http://127.0.0.1:8000/api/cli-lab/posts/' . $slug)->json()
        );

        $this->listenForReadingKeys();
    }

    protected function enterBrowsingMode(): void
    {
        $this->state = 'browse';

        $this->listenForBrowseKeys();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function value(): mixed
    {
        return null;
    }
}
