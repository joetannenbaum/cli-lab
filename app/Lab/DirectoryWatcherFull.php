<?php

namespace App\Lab;

use App\Lab\Renderers\DirectoryWatcherRenderer;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Illuminate\Support\Collection;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Prompt;

class DirectoryWatcherFull extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersRenderers;
    use SetsUpAndResets;
    use TypedValue;

    public Collection $items;

    public Collection $versions;

    public Collection $log;

    public string $total = '';

    public bool $showActivityLog = false;

    public function __construct(public string $path)
    {
        $this->registerRenderer(DirectoryWatcherRenderer::class);

        $this->items = collect();
        $this->versions = collect();
        $this->log = collect();

        // $this->createAltScreen();
    }

    public function watch()
    {
        $this->setup($this->watchDirectory(...));
    }

    public function value(): mixed
    {
        return null;
    }

    protected function watchDirectory()
    {
        $listener = KeyPressListener::for($this)
            ->listenForQuit()
            ->on('a', fn () => $this->showActivityLog = !$this->showActivityLog);

        while (true) {
            $output = shell_exec('ls -lAh ' . $this->path);

            $items = collect(explode(PHP_EOL, $output));

            $this->total = str_replace('total ', '', $items->shift());

            $this->versions->push($this->items);
            $this->versions = $this->versions->take(-20);

            $this->items = $items->map($this->parseItem(...))->filter(fn ($item) => count($item) > 0);

            $this->logDeleted();
            $this->logCreated();
            $this->logModified();

            $this->render();

            $listener->once();

            usleep(100_000);
        }
    }

    protected function logDeleted(): void
    {
        $this->versions->last()
            ->filter(fn ($version) => $this->items->firstWhere('name', $version['name']) === null)
            ->each(fn ($item) => $this->logActivity('deleted', $item));
    }

    protected function logCreated(): void
    {
        $this->items
            ->filter(fn ($item) => $this->versions->last()->firstWhere('name', $item['name']) === null)
            ->each(fn ($item) => $this->logActivity('created', $item));
    }

    protected function logModified(): void
    {
        $lastVersion = $this->versions->last();

        $this->items
            ->filter(fn ($item) => $this->versions->last()->firstWhere('name', $item['name']) !== null)
            ->each(function ($item) use ($lastVersion) {
                $lastVersionOfItem = $lastVersion->firstWhere('name', $item['name']);

                $propertiesToCheck = [
                    'permissions',
                    'owner',
                    'group',
                    'size',
                    'date',
                ];

                foreach ($propertiesToCheck as $property) {
                    if ($lastVersionOfItem[$property] !== $item[$property]) {
                        $this->logActivity('modified', $item, [
                            'from' => $lastVersionOfItem[$property],
                            'to'   => $item[$property],
                        ]);
                    }
                }
            });
    }

    protected function logActivity(string $type, array $item, array $data = []): void
    {
        $this->log->push(
            array_merge([
                'type'      => $type,
                'item'      => $item,
                'timestamp' => time(),
            ], $data)
        );
    }

    protected function parseItem(string $item): array
    {
        $parts = preg_split('/\s+/', $item);

        if (count($parts) <= 1) {
            return [];
        }

        [
            $permissions,
            $hardLinks,
            $owner,
            $group,
            $size,
            $month,
            $day,
            $time,
        ] = $parts;

        $name = implode(' ', array_slice($parts, 8));

        return array_merge(
            compact(
                'permissions',
                'hardLinks',
                'owner',
                'group',
                'size',
                'month',
                'day',
                'time',
                'name',
            ),
            [
                'date'   => "{$month} {$day} {$time}",
                'is_dir' => substr($permissions, 0, 1) === 'd',
            ],
        );
    }
}
