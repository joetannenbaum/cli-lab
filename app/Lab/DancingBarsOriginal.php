<?php

namespace App\Lab;

use App\Lab\Renderers\DancingBarsRenderer;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Laravel\Prompts\Prompt;

class DancingBarsOriginal extends Prompt
{
    use RegistersRenderers;
    use SetsUpAndResets;

    public array $numbers;

    public array $nextNumbers;

    public array $colors = [];

    public function __construct()
    {
        $this->registerRenderer(DancingBarsRenderer::class);
    }

    public function run(): void
    {
        $this->setup($this->dance(...));
    }

    public function dance(): void
    {
        $listener = KeyPressListener::for($this)->listenForQuit();

        while (true) {
            $this->generateNextNumbers();
            $this->incrementNumbers();

            $listener->once();

            sleep(1);
        }
    }

    public function value(): mixed
    {
        return null;
    }

    protected function incrementNumbers()
    {
        $stillIncrementing = false;

        foreach ($this->nextNumbers as $index => $number) {
            if ($this->numbers[$index] < $number) {
                $this->numbers[$index]++;
                $stillIncrementing = true;
            } elseif ($this->numbers[$index] > $number) {
                $this->numbers[$index]--;
                $stillIncrementing = true;
            }
        }

        $this->render();

        if ($stillIncrementing) {
            usleep(50_000);
            $this->incrementNumbers();
        }
    }

    protected function generateNextNumbers(): void
    {
        $count = 17;

        if (!isset($this->numbers)) {
            $this->numbers = collect(range(0, $count))->map(fn () => 0)->toArray();
        }

        $this->nextNumbers = collect(range(0, $count))->map(fn () => rand(1, 15))->toArray();

        $colors = ['red', 'green', 'yellow', 'blue', 'magenta', 'cyan', 'white'];

        $this->colors = collect(range(0, $count))->map(fn () => $colors[array_rand($colors)])->toArray();
    }
}
