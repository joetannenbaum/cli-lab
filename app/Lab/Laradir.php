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
use Laravel\Prompts\Concerns\Scrolling;
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
    use Scrolling;

    public array $items = [];

    public int $index = 0;

    public int $page = 1;

    public int $perPage = 0;

    public int $bioScrollPosition = 0;

    public array $detail = [];

    public array $filters = [];

    public array $selectedFilters = [];

    public array $results = [];

    public int $currentFilter = 0;

    public int $filterScrollPosition = 0;

    public string $filterFocus = 'categories';

    public array $filtersFromApi;

    public bool $searching = false;

    public int $spinnerCount = 0;

    public int $pid = 0;

    protected PendingRequest $client;

    public function __construct()
    {
        $this->registerTheme(LaradirRenderer::class);

        $this->scroll = 20;

        $this->initializeScrolling(0);

        $this->client = Http::baseUrl('https://laradir.com/api')->acceptJson()->asJson();

        $filters = Cache::remember('laradir-filters', CarbonInterval::day(), fn () => $this->client->get('filters')->json());

        foreach ($filters as $key => $subFilters) {
            $arr = [
                'key'     => $key,
                'filters' => [],
            ];

            foreach ($subFilters as $key => $value) {
                $arr['filters'][] = [
                    'key'   => $key,
                    'value' => $value,
                ];
            }

            $this->filters[] = $arr;
        }

        $this->filtersFromApi = $filters;

        $this->state = 'search';

        $this->createAltScreen();
        $this->listenForSearchKeys();
    }

    public function onEnter(): void
    {
        $item = $this->items[$this->index];

        $this->detail = $item;

        $this->state = 'detail';

        $this->bioScrollPosition = 0;

        $this->listenForDetailKeys();
    }

    public function __destruct()
    {
        if ($this->pid > 0) {
            // Only do this in the parent
            $this->exitAltScreen();
        }
    }

    public function search()
    {
        $this->searching = true;

        $params = array_merge(
            [
                'per_page' => $this->perPage,
                'page'     => $this->page,
                'seed'     => $this->results['meta']['filters']['seed'] ?? null,
            ],
            $this->selectedFilters,
        );

        $cacheKey = collect([
            'laradir',
            'search',
            microtime(),
            md5(json_encode($params)),
        ])->implode(':');

        $this->pid = pcntl_fork();

        if ($this->pid == -1) {
            exit('could not fork');
        } elseif ($this->pid) {
            // we are the parent
        } else {
            Cache::remember(
                $cacheKey,
                CarbonInterval::seconds(10),
                fn () => $this->client->get('search', $params)->json(),
            );

            exit(0);
        }

        do {
            $this->render();
            usleep(75_000);
            $this->spinnerCount++;
        } while (Cache::missing($cacheKey));

        $this->results = Cache::pull($cacheKey);

        $this->items = array_slice($this->results['data'], 0, $this->perPage);
        $this->index = 0;
        $this->searching = false;
        $this->render();
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
                }
            })
            ->onRight(function () {
                $newPage = min($this->results['meta']['last_page'], $this->page + 1);

                if ($newPage !== $this->page) {
                    $this->page = $newPage;
                    $this->search();
                }
            })
            ->on('/', function () {
                $this->state = 'filter';
                $this->currentFilter = 0;
                $this->filterScrollPosition = 0;
                $this->filterFocus = 'categories';
                $this->listenForFilterKeys();
            })
            ->on(Key::ENTER, $this->onEnter(...))
            ->listen();
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

    protected function listenForFilterKeys(): void
    {
        KeyPressListener::for($this)
            ->clearExisting()
            ->listenForQuit()
            ->onUp(function () {
                if ($this->filterFocus === 'categories') {
                    $this->currentFilter = max(0, $this->currentFilter - 1);
                    $this->highlight(0);
                } else {
                    $this->highlightPrevious(count($this->filters[$this->currentFilter]['filters']));
                }
            })
            ->onDown(function () {
                if ($this->filterFocus === 'categories') {
                    $this->currentFilter = min(count($this->filters) - 1, $this->currentFilter + 1);
                    $this->highlight(0);
                } else {
                    $this->highlightNext(count($this->filters[$this->currentFilter]['filters']));
                }
            })
            ->onLeft(fn () => $this->filterFocus = 'categories')
            ->onRight(fn () => $this->filterFocus = 'filters')
            ->on(Key::ENTER, function () {
                $this->state = 'search';
                $this->page = 1;
                $this->search();
                $this->listenForSearchKeys();
            })
            ->on(Key::SPACE, function () {
                if ($this->filterFocus === 'categories') {
                    return;
                }

                $currentFilter = $this->filters[$this->currentFilter];
                $filter = $currentFilter['filters'][$this->highlighted];
                $currentlySelected = $this->selectedFilters[$currentFilter['key']] ?? [];

                if (in_array($filter['key'], $currentlySelected)) {
                    $this->selectedFilters[$currentFilter['key']] = array_values(array_diff($currentlySelected, [$filter['key']]));
                } else {
                    $this->selectedFilters[$currentFilter['key']] = array_values(array_merge($currentlySelected, [$filter['key']]));
                }
            })
            ->listen();
    }
}
