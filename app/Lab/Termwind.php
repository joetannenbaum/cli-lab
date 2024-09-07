<?php

namespace App\Lab;

use App\Lab\Renderers\BigTextRenderer;
use App\Lab\Renderers\TermwindRenderer;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Input\KeyPressListener;
use Laravel\Prompts\Prompt;

class Termwind extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersRenderers;

    public array $nav = [
        'Home',
        'About',
        'Services',
        'Contact',
    ];

    public array $content = [
        'Welcome to Termwind!',
        'This is a demo of a custom prompt using Termwind.',
        'Use the arrow keys to navigate the menu.',
        'Press "q" to quit.',
    ];

    public int $selected = 0;

    public function __construct()
    {
        $this->registerRenderer(TermwindRenderer::class);

        // $this->createAltScreen();

        KeyPressListener::for($this)
            ->onRight(fn() => $this->selected = min($this->selected + 1, count($this->nav) - 1))
            ->onLeft(fn() => $this->selected = max($this->selected - 1, 0))
            ->listen();
    }

    public function value(): mixed
    {
        return null;
    }
}
