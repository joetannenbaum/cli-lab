<?php

namespace App\Lab;

use App\Lab\Concerns\CreatesAnAltScreen;
use App\Lab\Concerns\RegistersThemes;
use App\Lab\Concerns\SetsUpAndResets;
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

    public array $post = [];

    public array $posts = [];

    public int $scrollPosition = 0;

    public int $scrollAmount = 5;

    public int $browsePage = 0;

    public int $browseSelected = 0;

    public string $state = 'browse';

    public bool $fetching = false;

    public int $spinnerCount = 0;

    public int $pid = 0;

    public function __construct(?string $slug = null)
    {
        $this->registerTheme(BlogRenderer::class);

        $this->createAltScreen();

        $this->fetchPosts();

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
            ->on([Key::DOWN_ARROW, Key::DOWN], $this->scrollDown(...))
            ->on([Key::UP_ARROW, Key::UP], $this->scrollUp(...))
            ->listenForQuit()
            ->on('b', $this->enterBrowsingMode(...))
            ->listen();
    }

    public function listenForBrowseKeys()
    {
        KeyPressListener::for($this)
            ->clearExisting()
            ->listenForQuit()
            ->on([Key::DOWN_ARROW, Key::DOWN], fn () => $this->browseSelected = min($this->browseSelected + 1, count($this->posts[$this->browsePage]) - 1))
            ->on([Key::UP_ARROW, Key::UP], fn () => $this->browseSelected = max($this->browseSelected - 1, 0))
            ->on([Key::LEFT_ARROW, Key::LEFT], function () {
                $this->browsePage = max($this->browsePage - 1, 0);
                $this->browseSelected = 0;
            })
            ->on([Key::RIGHT_ARROW, Key::RIGHT], function () {
                $this->browsePage = min($this->browsePage + 1, count($this->posts) - 1);
                $this->browseSelected = 0;
            })
            ->on(Key::ENTER, function () {
                $this->enterReadingMode($this->posts[$this->browsePage][$this->browseSelected]['slug']);
            })
            ->listen();
    }

    public function __destruct()
    {
        if ($this->pid > 0) {
            // Only do this in the parent
            $this->exitAltScreen();
        }
    }

    public function value(): mixed
    {
        return null;
    }

    protected function fetchPosts()
    {
        $this->fetching = true;

        $cacheKey = 'blog:posts';

        $this->pid = pcntl_fork();

        if (
            $this->pid == -1
        ) {
            exit('could not fork');
        } elseif ($this->pid) {
            // we are the parent
        } else {
            Cache::remember(
                $cacheKey,
                CarbonInterval::day(),
                fn () => Http::get(
                    config('services.blog.url') . '/api/cli-lab/posts',
                    [
                        'shh' => config('services.blog.secret'),
                    ],
                )->json()
            );

            exit(0);
        }

        $missingPosts = Cache::get($cacheKey) === null;

        while ($missingPosts) {
            $this->render();
            usleep(75_000);
            $this->spinnerCount++;
            $missingPosts = Cache::get($cacheKey) === null;
        }

        $posts = Cache::get($cacheKey);

        $height = self::terminal()->lines() - 12;

        $this->posts = collect($posts)->chunk((int) floor($height / 3))->map(fn ($p) => $p->values())->toArray();

        $this->fetching = false;
    }

    protected function fetchPost($slug)
    {
        $this->fetching = true;

        $cacheKey = 'blog:posts:' . $slug;

        $this->pid = pcntl_fork();

        if (
            $this->pid == -1
        ) {
            exit('could not fork');
        } elseif ($this->pid) {
            // we are the parent
        } else {
            Cache::remember(
                $cacheKey,
                CarbonInterval::day(),
                fn () => Http::get(
                    config('services.blog.url') . '/api/cli-lab/posts/' . $slug,
                    [
                        'shh' => config('services.blog.secret'),
                    ],
                )->json()
            );

            exit(0);
        }

        $missingPost = Cache::missing($cacheKey);

        while ($missingPost) {
            $this->render();
            usleep(75_000);
            $this->spinnerCount++;
            $missingPost = Cache::missing($cacheKey);
        }

        $this->fetching = false;

        return Cache::get($cacheKey);
    }

    protected function enterReadingMode($slug): void
    {
        $this->state = 'reading';

        $post = $this->fetchPost($slug);

        if ($post === null) {
            $this->enterBrowsingMode();

            return;
        }

        $this->post = $post;
        $this->scrollPosition = 0;

        $this->listenForReadingKeys();
    }

    protected function enterBrowsingMode(): void
    {
        $this->state = 'browse';

        $this->listenForBrowseKeys();
    }
}
