<?php

namespace App\Lab;

use App\Lab\Concerns\CreatesAnAltScreen;
use App\Lab\Concerns\Loops;
use App\Lab\Concerns\RegistersThemes;
use App\Lab\Concerns\SetsUpAndResets;
use App\Lab\Input\KeyPressListener;
use App\Lab\Renderers\LaradirRenderer;
use Carbon\CarbonInterval;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Illuminate\Support\Str;

class Laradir extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersThemes;
    use SetsUpAndResets;
    use TypedValue;

    public array $items = [];

    public int $index = 0;

    protected PendingRequest $client;

    protected string $seed;

    public array $filters;

    public array $selectedFilters = [];

    public function __construct()
    {
        $this->registerTheme(LaradirRenderer::class);

        $this->client = Http::baseUrl('https://laradir.com/api')->acceptJson()->asJson();

        $this->seed = Str::random(10);

        $this->filters = Cache::remember('laradir-filters', CarbonInterval::day(), fn () => $this->client->get('filters')->json());

        // $this->createAltScreen();

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
