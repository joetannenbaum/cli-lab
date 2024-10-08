<?php

namespace App\Lab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use App\Lab\Renderers\ResumeRenderer;
use Illuminate\Support\Facades\Log;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Resume extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersRenderers;
    use SetsUpAndResets;
    use TypedValue;

    public int $page = 0;

    public int $selectedPage = 0;

    public int $scrollPosition = 0;

    public int $maxTextWidth = 0;

    public int $height = 0;

    public int $width = 0;

    public int $colorIndex = 0;

    public string $color = 'cyan';

    public array $colors = [
        'cyan',
        'red',
        'green',
        'yellow',
        'blue',
        'magenta',
    ];

    public array $navigation = [
        'Summary',
        'Links',
        'Experience',
        'Skills',
        'Education',
    ];

    protected bool $easterEggLogged = false;

    public function __construct()
    {
        $this->registerRenderer(ResumeRenderer::class);

        $this->createAltScreen();

        KeyPressListener::for($this)
            ->on(['q', Key::CTRL_C], fn() => $this->terminal()->exit())
            ->on([Key::UP, Key::UP_ARROW], fn() => $this->scrollPosition = max(0, $this->scrollPosition - 2))
            ->on([Key::DOWN, Key::DOWN_ARROW], fn() => $this->scrollPosition += 2)
            ->on([Key::RIGHT, Key::RIGHT_ARROW], function () {
                $this->page = $this->selectedPage = min(count($this->navigation) - 1, $this->page + 1);
                $this->scrollPosition = 0;
            })
            ->on([Key::LEFT, Key::LEFT_ARROW], function () {
                $this->page = $this->selectedPage = max(0, $this->page - 1);
                $this->scrollPosition = 0;
            })
            ->on('c', function () {
                $this->colorIndex++;

                if ($this->colorIndex >= count($this->colors)) {
                    $this->colorIndex = 0;
                }

                $this->color = $this->colors[$this->colorIndex];
                $this->logEasterEgg();
            })
            ->listen();
    }

    public function logEasterEgg()
    {
        if ($this->easterEggLogged) {
            return;
        }

        $this->easterEggLogged = true;

        Log::info('Easter egg found', [
            'command' => 'resume',
            'user'    => $_SERVER,
        ]);
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
