<?php

namespace App\Lab;

use App\Lab\Renderers\ArtClassRenderer;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Illuminate\Support\Facades\File;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Illuminate\Support\Str;

class ArtClass extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersRenderers;
    use SetsUpAndResets;

    public array $cursorPosition = [0, 0];

    public array $art = [];

    public int $width;

    public int $height;

    public $active = false;

    public $currentColor = 'white';

    public $lastSavedId = null;

    public $colors = [
        'b' => 'black',
        'r' => 'red',
        'g' => 'green',
        'y' => 'yellow',
        'l' => 'blue',
        'm' => 'magenta',
        'c' => 'cyan',
        'w' => 'white',
        // 'a' => 'gray',
    ];

    public $erasing = false;

    public function __construct()
    {
        $this->registerRenderer(ArtClassRenderer::class);

        $this->width = $this->terminal()->cols() - 4;
        $this->height = $this->terminal()->lines() - 4;

        $this->cursorPosition = [(int) floor($this->width / 2), (int) floor($this->height / 2)];

        $listener = KeyPressListener::for($this)
            ->on(['q', Key::CTRL_C], function () {
                echo "\e[?1003l";
                $this->terminal()->exit();
            })
            ->on(Key::SPACE, function () {
                $this->active = !$this->active;
                $this->addToArt();
            })
            ->onUp(function () {
                $this->cursorPosition[1] = max(0, $this->cursorPosition[1] - 1);
                $this->addToArt();
            })
            ->onDown(function () {
                $this->cursorPosition[1] = min($this->height, $this->cursorPosition[1] + 1);
                $this->addToArt();
            })
            ->onLeft(function () {
                $this->cursorPosition[0] = max(0, $this->cursorPosition[0] - 1);
                $this->addToArt();
            })
            ->onRight(function () {
                $this->cursorPosition[0] = min($this->width, $this->cursorPosition[0] + 1);
                $this->addToArt();
            })
            ->on('e', function () {
                $this->erasing = !$this->erasing;
            })
            ->onMouseMove(fn($x, $y) => $this->onMouse($x, $y, false))
            ->onMouseClick(fn($x, $y) => $this->onMouse($x, $y, true))
            ->onMouseDrag(fn($x, $y) => $this->onMouse($x, $y, true))
            ->on(Key::ENTER, function () {
                File::ensureDirectoryExists(storage_path('art-class'));

                $this->lastSavedId = Str::uuid();

                file_put_contents(storage_path('art-class/' . $this->lastSavedId . '.json'), json_encode([
                    'width' => $this->width,
                    'height' => $this->height,
                    'art' => $this->art,
                ]));
            });

        echo "\e[?1003h";

        foreach ($this->colors as $key => $color) {
            $listener->on($key, function () use ($color) {
                $this->currentColor = $color;
            });
        }

        $listener->listen();

        $this->createAltScreen();
    }

    public function onMouse($x, $y, $active = false)
    {
        $this->active = $active;
        $this->cursorPosition = [$x, $y];
        $this->addToArt();
    }

    protected function addToArt()
    {
        if ($this->active) {
            if ($this->erasing) {
                unset($this->art[$this->cursorPosition[1]][$this->cursorPosition[0]]);
            } else {
                $this->art[$this->cursorPosition[1]] ??= [];
                $this->art[$this->cursorPosition[1]][$this->cursorPosition[0]] = $this->currentColor;
            }
        }
    }

    public function value(): mixed
    {
        return null;
    }
}
