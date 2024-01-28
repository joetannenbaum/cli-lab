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

class Laradir extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersThemes;
    use SetsUpAndResets;
    use TypedValue;

    public array $items = [];

    public int $index = 0;

    public int $page = 1;

    public int $perPage = 0;

    public int $bioScrollPosition = 0;

    public array $detail = [];

    protected PendingRequest $client;

    public array $filters;

    public array $selectedFilters = [];

    public array $results;

    public function __construct()
    {
        $this->registerTheme(LaradirRenderer::class);

        $this->client = Http::baseUrl('https://laradir.com/api')->acceptJson()->asJson();

        $this->filters = Cache::remember('laradir-filters', CarbonInterval::day(), fn () => $this->client->get('filters')->json());

        $this->state = 'search';

        // $this->createAltScreen();
        $this->listenForSearchKeys();
    }

    protected function listenForSearchKeys(): void
    {
        KeyPressListener::for($this)
            ->clearExisting()
            ->listenForQuit()
            ->onUp(fn () => $this->index = max(0, $this->index - 1))
            ->onDown(fn () => $this->index = min(count($this->items) - 1, $this->index + 1))
            ->onLeft(function () {
                $newPage = max(1, $this->page - 1);

                if ($newPage !== $this->page) {
                    $this->page = $newPage;
                    $this->search();
                    $this->index = 0;
                }
            })
            ->onRight(function () {
                $newPage = min($this->results['meta']['last_page'], $this->page + 1);

                if ($newPage !== $this->page) {
                    $this->page = $newPage;
                    $this->search();
                    $this->index = 0;
                }
            })
            ->on(Key::ENTER, $this->onEnter(...))
            ->listen();
    }

    public function onEnter(): void
    {
        $item = $this->items[$this->index];

        $this->detail = $item;

        $this->state = 'detail';

        $this->bioScrollPosition = 0;

        $this->listenForDetailKeys();
    }

    protected function listenForDetailKeys(): void
    {
        KeyPressListener::for($this)
            ->clearExisting()
            ->listenForQuit()
            ->onUp(fn () => $this->bioScrollPosition = max(0, $this->bioScrollPosition - 1))
            ->onDown(fn () => $this->bioScrollPosition += 1)
            ->on('/', function () {
                $this->state = 'search';
                $this->listenForSearchKeys();
            })
            ->listen();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function search()
    {
        $this->results = $this->client->get('search', [
            'per_page' => $this->perPage,
            'page' => $this->page,
            'seed' => $this->results['meta']['filters']['seed'] ?? null,
        ])->json();

        $this->items = $this->results['data'];
    }

    public function run()
    {
        // Render it once to determine perPage
        $this->render();

        $this->search();

        $this->prompt();
    }

    public function value(): mixed
    {
        return null;
    }
}
