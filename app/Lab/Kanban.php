<?php

namespace App\Lab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\RegistersThemes;
use Chewie\Input\KeyPressListener;
use App\Lab\Renderers\KanbanRenderer;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

use function Laravel\Prompts\text;

class Kanban extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;

    public array $columns = [
        [
            'title' => 'To Do',
            'items' => [
                [
                    'title'       => 'Make Kanban Board',
                    'description' => 'But in the terminal?',
                ],
                [
                    'title'       => 'Eat Pizza',
                    'description' => '(Whole pie).',
                ],
            ],
        ],
        [
            'title' => 'In Progress',
            'items' => [
                [
                    'title'       => 'Get Milk',
                    'description' => 'From the store (whole).',
                ],
                [
                    'title'       => 'Learn Go',
                    'description' => 'charm.sh looks dope.',
                ],
                [
                    'title'       => 'Change Profile Pic',
                    'description' => 'It\'s been a while.',
                ],
            ],
        ],
        [
            'title' => 'Done',
            'items' => [
                [
                    'title'       => 'Wait Patiently',
                    'description' => 'For the next prompt.',
                ],
            ],
        ],
    ];

    public int $itemIndex = 0;

    public int $columnIndex = 0;

    public function __construct()
    {
        $this->registerTheme(KanbanRenderer::class);

        $this->listenForInput();

        $this->createAltScreen();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function listenForInput(): void
    {
        KeyPressListener::for($this)
            ->on(['q', Key::CTRL_C], fn () => $this->terminal()->exit())
            // ->on('n', fn () => $this->addNewItem())
            ->on(Key::ENTER, fn () => $this->moveCurrentItem())
            ->on([Key::UP, Key::UP_ARROW], fn () => $this->itemIndex = max(0, $this->itemIndex - 1))
            ->on([Key::DOWN, Key::DOWN_ARROW], fn () => $this->itemIndex = min(count($this->columns[$this->columnIndex]['items']) - 1, $this->itemIndex + 1))
            ->on([Key::RIGHT, Key::RIGHT_ARROW], $this->nextColumn(...))
            ->on([Key::LEFT, Key::LEFT_ARROW], $this->previousColumn(...))
            ->listen();
    }

    public function value(): bool
    {
        return true;
    }

    protected function nextColumn(): void
    {
        $this->columnIndex = min(count($this->columns) - 1, $this->columnIndex + 1);
        $this->itemIndex = 0;
    }

    protected function previousColumn(): void
    {
        $this->columnIndex = max(0, $this->columnIndex - 1);
        $this->itemIndex = 0;
    }

    protected function addNewItem(): void
    {
        KeyPressListener::for($this)->clearExisting();

        $this->capturePreviousNewLines();
        $this->resetCursorPosition();
        $this->eraseDown();

        $title = text('Title', 'Title of task');

        $description = text('Description', 'Description of task');

        $this->columns[$this->columnIndex]['items'][] = [
            'title'       => $title,
            'description' => $description,
        ];

        $this->listenForInput();
        $this->prompt();
    }

    protected function resetCursorPosition(): void
    {
        $lines = count(explode(PHP_EOL, $this->prevFrame)) - 1;

        $this->moveCursor(-999, $lines * -1);
    }

    protected function moveCurrentItem(): void
    {
        if (count($this->columns[$this->columnIndex]['items']) === 0) {
            return;
        }

        $newColumnIndex = $this->columnIndex + 1;

        if ($newColumnIndex >= count($this->columns)) {
            $newColumnIndex = 0;
        }

        $this->columns[$newColumnIndex]['items'][] = $this->columns[$this->columnIndex]['items'][$this->itemIndex];

        unset($this->columns[$this->columnIndex]['items'][$this->itemIndex]);

        $this->columns[$this->columnIndex]['items'] = array_values($this->columns[$this->columnIndex]['items']);

        $this->itemIndex = max(0, $this->itemIndex - 1);
    }
}
