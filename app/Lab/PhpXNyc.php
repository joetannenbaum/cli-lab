<?php

namespace App\Lab;

use App\Lab\Concerns\CreatesAnAltScreen;
use App\Lab\Concerns\RegistersThemes;
use App\Lab\Input\KeyPressListener;
use App\Lab\Renderers\PhpXNycRenderer;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

use function Laravel\Prompts\text;

class PhpXNyc extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;
    use TypedValue;

    public function __construct()
    {
        $this->registerTheme(PhpXNycRenderer::class);

        $this->createAltScreen();

        $this->trackTypedValue();

        KeyPressListener::for($this)
            ->listenForQuit()
            // ->on([Key::UP, Key::UP_ARROW], fn () => $this->scrollPosition = max(0, $this->scrollPosition - 2))
            // ->on([Key::DOWN, Key::DOWN_ARROW], fn () => $this->scrollPosition += 2)
            // ->on([Key::RIGHT, Key::RIGHT_ARROW], function () {
            //     $this->page = $this->selectedPage = min(count($this->navigation) - 1, $this->page + 1);
            //     $this->scrollPosition = 0;
            // })
            // ->on([Key::LEFT, Key::LEFT_ARROW], function () {
            //     $this->page = $this->selectedPage = max(0, $this->page - 1);
            //     $this->scrollPosition = 0;
            // })
            // ->on('c', function () {
            //     $this->colorIndex++;

            //     if ($this->colorIndex >= count($this->colors)) {
            //         $this->colorIndex = 0;
            //     }

            //     $this->color = $this->colors[$this->colorIndex];
            //     $this->logEasterEgg();
            // })
            ->listen();

        $name = text('Name');
        $email = text('Email');

        dd($name, $email);
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function value(): mixed
    {
        return null;
    }

    public function valueWithCursor(int $maxWidth): string
    {
        if ($this->typedValue === '') {
            return $this->dim($this->addCursor('', 0, $maxWidth));
        }

        return $this->addCursor($this->typedValue, $this->cursorPosition, $maxWidth);
    }
}
