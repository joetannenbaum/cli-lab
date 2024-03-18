<?php

namespace App\Lab;

use App\Lab\Renderers\BigTextRenderer;
use Chewie\Art;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\RegistersThemes;
use Chewie\Input\KeyPressListener;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class BigText extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;
    use TypedValue;

    public string $message = "";
    // public string $message = "Type a messsage,\nenter to clear";

    public function __construct()
    {
        $this->registerTheme(BigTextRenderer::class);

        $this->createAltScreen();

        // Art::setDirectory(__DIR__ . '/../art/characters');
        // Art::setDirectory(storage_path('art'));

        $validCharacters = array_merge(
            range('a', 'z'),
            range('A', 'Z'),
            [
                ' ',
                '.',
                ',',
                '?',
                '!',
                "'",
            ],
        );

        KeyPressListener::for($this)
            ->on($validCharacters, fn ($key) => $this->message .= $key)
            ->on(Key::ENTER, fn () => $this->message = '')
            ->on(Key::BACKSPACE, fn () => $this->message = substr($this->message, 0, -1))
            ->on(Key::CTRL_C, fn () => $this->terminal()->exit())
            ->listen();
    }

    public function value(): mixed
    {
        return null;
    }
}
