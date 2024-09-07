<?php

namespace App\Lab;

use App\Lab\LaraconIndia\Bar;
use App\Lab\Renderers\LaraconIndiaClosingRenderer;
use Chewie\Art;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Illuminate\Support\Collection;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class LaraconIndiaClosing extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersRenderers;
    use TypedValue;
    use Loops;
    use SetsUpAndResets;

    public $selected = false;

    public $index = 0;

    public string $message = '';

    public int $barCount = 0;

    public int $barHeight = 0;

    public Collection $bars;

    public function __construct()
    {
        $this->registerRenderer(LaraconIndiaClosingRenderer::class);

        $this->createAltScreen();

        Art::setDirectory(storage_path('ascii/alphabet'));

        $this->bars = collect();

        $this->setup(function () {
            $message = "Thank you,\nLaracon India!";

            $letters = mb_str_split($message);

            foreach ($letters as $letter) {
                $this->message .= $letter;
                $this->render();
                usleep(100_000);
            }

            $this->bars = collect(range(1, $this->barCount))->map(fn() => new Bar($this->barHeight));

            $this->bars->each(fn(Bar $bar) => $this->registerLoopable($bar));

            $listener = KeyPressListener::for($this)->on(['q', Key::CTRL_C], function () {
                $this->exitAltScreen();
                $this->terminal()->exit();
            });

            $this->loop(function () use ($listener) {
                $listener->once();
                $this->render();
            });
        });
    }

    public function value(): mixed
    {
        return null;
    }
}
