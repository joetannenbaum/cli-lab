<?php

namespace App\Lab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Input\KeyPressListener;
use App\Lab\Renderers\DataTableRenderer;
use Chewie\Concerns\RegistersRenderers;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class DataTable extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersRenderers;
    use TypedValue;

    public array $headers;

    public array $rows;

    public int $perPage = 10;

    public int $page = 1;

    public int $index = 0;

    public string $query = '';

    public int $totalPages;

    public string $jumpToPage = '';

    protected KeyPressListener $listener;

    public function __construct()
    {
        $this->registerRenderer(DataTableRenderer::class);

        $this->headers = ['name' => 'Name', 'email' => 'Email', 'address' => 'Address'];

        $this->rows = collect(range(1, 100))->map(
            fn ($i) => [
                'name'    => fake()->name(),
                'email'   => fake()->email(),
                'address' => str_replace(PHP_EOL, ' ', fake()->address()),
            ]
        )->all();

        // file_put_contents(__DIR__ . '/datatable.json', json_encode($this->rows, JSON_PRETTY_PRINT));

        $this->totalPages = $this->getTotalPages($this->rows);

        $this->listener = KeyPressListener::for($this);

        $this->browse();

        $this->createAltScreen();
    }

    public function visible(): array
    {
        if ($this->query === '') {
            $this->totalPages = $this->getTotalPages($this->rows);

            return array_slice($this->rows, ($this->page - 1) * $this->perPage, $this->perPage);
        }

        $filtered = array_filter(
            $this->rows,
            fn ($row)  => str_contains(
                mb_strtolower(implode(' ', $row)),
                mb_strtolower($this->query),
            ),
        );

        $this->totalPages = $this->getTotalPages($filtered);

        $results = array_slice($filtered, 0, $this->perPage);

        if (count($results) > 0) {
            return $results;
        }

        return [
            [
                'name'    => 'No results',
                'email'   => '',
                'address' => '',
            ],
        ];
    }

    protected function getTotalPages(array $records): int
    {
        return  (int) ceil(count($records) / $this->perPage);
    }

    public function value(): array
    {
        return $this->visible()[$this->index];
    }

    public function valueWithCursor(int $maxWidth): string
    {
        return $this->getValueWithCursor($this->query, $maxWidth);
    }

    public function jumpValueWithCursor(int $maxWidth): string
    {
        return $this->getValueWithCursor($this->jumpToPage, $maxWidth);
    }

    protected function getValueWithCursor(string $value, int $maxWidth): string
    {
        if ($value === '') {
            return $this->dim($this->addCursor('', 0, $maxWidth));
        }

        return $this->addCursor($value, $this->cursorPosition, $maxWidth);
    }

    protected function quit(): void
    {
        $this->state = 'cancel';
        exit;
    }

    protected function browse(): void
    {
        $this->state = 'browse';

        $this->listener
            ->clearExisting()
            ->listenForQuit()
            ->on(
                [Key::UP, Key::UP_ARROW],
                fn () => $this->index = max(0, $this->index - 1),
            )
            ->on(
                [Key::DOWN, Key::DOWN_ARROW],
                fn () => $this->index = min($this->perPage - 1, $this->index + 1),
            )
            ->on(
                [Key::RIGHT, Key::RIGHT_ARROW],
                function () {
                    $this->page = min($this->totalPages, $this->page + 1);
                    $this->index = 0;
                },
            )
            ->on(
                [Key::LEFT, Key::LEFT_ARROW],
                function () {
                    $this->page = max(1, $this->page - 1);
                    $this->index = 0;
                },
            )
            ->on(Key::ENTER, $this->submit(...))
            ->on('/', $this->search(...))
            ->on('j', $this->jump(...))
            ->listen();
    }

    protected function search(): void
    {
        $this->state = 'search';
        $this->index = 0;
        $this->page = 1;

        $this->listener
            ->clearExisting()
            ->listenToInput($this->query, $this->cursorPosition)
            ->on(
                Key::ENTER,
                function () {
                    if (count($this->visible()) === 0) {
                        return;
                    }

                    $this->browse();
                },
            )
            ->listen();
    }

    protected function jump(): void
    {
        $this->state = 'jump';
        $this->index = 0;

        $this->listener
            ->clearExisting()
            ->listenToInput($this->jumpToPage, $this->cursorPosition)
            ->on(
                Key::ENTER,
                function () {
                    if ($this->jumpToPage === '') {
                        $this->browse();
                        return;
                    }

                    if (!is_numeric($this->jumpToPage)) {
                        return;
                    }

                    if ($this->jumpToPage < 1 || $this->jumpToPage > $this->totalPages) {
                        return;
                    }

                    $this->page = (int) $this->jumpToPage;
                    $this->jumpToPage = '';
                    $this->browse();
                },
            )
            ->listen();
    }
}
