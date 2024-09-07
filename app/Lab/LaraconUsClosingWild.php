<?php

namespace App\Lab;

use App\Lab\LaraconUs\BouncingBall;
use App\Lab\LaraconUs\Dot;
use App\Lab\LaraconUs\FadeToggle;
use App\Lab\LaraconUs\Raindrop;
use App\Lab\Renderers\LaraconUsClosingRenderer;
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

class LaraconUsClosingWild extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersRenderers;
    use TypedValue;
    use Loops;
    use SetsUpAndResets;

    public $selected = false;

    public $index = 0;

    public string $message = '';

    public int $dotCount = 0;

    public int $dotHeight = 0;

    public Collection $dots;

    public $easings = [];

    public $dotPositions = [];

    public $dotTotalWidth = 0;

    public $dotSafeZone = 0;

    public $direction = -1;

    public $lowestValue = 0;

    public FadeToggle $fadeToggle;

    public BouncingBall $bouncingBall;

    public Collection $bouncingBalls;

    public Collection $raindrops;

    public int $bouncingBallHeight = 0;

    public function __construct()
    {
        $this->registerRenderer(LaraconUsClosingRenderer::class);
        Art::setDirectory(storage_path('ascii/alphabet'));

        $this->createAltScreen();

        // $this->setup($this->rain(...));
        // $this->setup($this->ballBounce(...));
        $this->setup($this->fadeToggle(...));
        $this->setup($this->runEasingAnimations(...));

        dd('stop');


        $this->dots = collect();

        $this->render();

        $this->dots = collect(range(1, 1))->map(fn() => new Dot($this->dotHeight));

        $this->dots->each(fn(Dot $dot) => $this->registerLoopable($dot));

        $listener = KeyPressListener::for($this)->on(['q', Key::CTRL_C], function () {
            $this->exitAltScreen();
            $this->terminal()->exit();
        });

        $this->loop(function () use ($listener) {
            $listener->once();
            $this->render();
        });

        return;


        $this->setup(function () {
            $message = "Thank you,\nLaracon!";

            $letters = mb_str_split($message);

            foreach ($letters as $letter) {
                $this->message .= $letter;
                $this->render();
                usleep(100_000);
            }

            $this->dots = collect(range(1, $this->dotCount))->map(fn() => new Dot($this->dotHeight));

            $this->dots->each(fn(Dot $dot) => $this->registerLoopable($dot));

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

    protected function rain()
    {
        $this->raindrops = collect(range(1, floor($this->terminal()->cols() / 4)))
            ->map(fn() => new Raindrop($this->terminal()->lines() - 4));

        $this->registerLoopables(...$this->raindrops);

        $listener = KeyPressListener::for($this)->on(['q', Key::CTRL_C], function () {
            $this->exitAltScreen();
            $this->terminal()->exit();
        });

        $this->loop(function () use ($listener) {
            $listener->once();
            $this->render();
        }, 8_000);

        dd('hi');
    }

    protected function ballBounce()
    {
        $this->bouncingBallHeight = $this->terminal()->lines() - 4;
        // $this->bouncingBall = new BouncingBall($this->bouncingBallHeight);
        $this->bouncingBalls = collect(range(1, floor($this->terminal()->cols() / 4)))
            ->map(fn() => new BouncingBall($this->bouncingBallHeight));

        $this->registerLoopables(...$this->bouncingBalls);

        $listener = KeyPressListener::for($this)->on(['q', Key::CTRL_C], function () {
            $this->exitAltScreen();
            $this->terminal()->exit();
        });

        $this->loop(function () use ($listener) {
            $listener->once();
            $this->render();
        }, 15_000);

        dd('hi');
    }

    protected function fadeToggle()
    {
        $this->fadeToggle = new FadeToggle('laravel-logo');

        $this->registerLoopable($this->fadeToggle);

        $listener = KeyPressListener::for($this)->on(['q', Key::CTRL_C], function () {
            $this->exitAltScreen();
            $this->terminal()->exit();
        });

        sleep(3);

        $this->loop(function () use ($listener) {
            $listener->once();
            $this->render();
        }, 2_500);

        dd('hi');
    }

    protected function runEasingAnimations()
    {
        $totalTimeInSeconds = 2;
        $totalTimeInMicroseconds = $totalTimeInSeconds * 1_000_000;
        $microsecondsPerFrame = 5_000;
        $totalTicks = $totalTimeInMicroseconds / $microsecondsPerFrame;

        $this->dotTotalWidth = $this->terminal()->cols() - 22;
        $this->dotSafeZone = (int) (floor($this->dotTotalWidth * .25)) * 2;

        $this->easings = collect(get_class_methods(Easings::class))
            // ->filter(fn ($method) => str_contains($method, 'Back') || str_contains($method, 'Elastic'))
            // ->filter(fn ($method) => str_contains($method, 'Back'))
            // ->sort()
            ->groupBy(function ($method) {
                if (str_contains($method, 'InOut')) {
                    return 'in_out';
                }

                return (str_contains($method, 'In')) ? 'in' : 'out';
            })
            ->sortBy(function ($methods, $key) {
                if ($key === 'in') {
                    return 3;
                }

                if ($key === 'out') {
                    return 1;
                }

                return 2;
            })
            // ->groupBy(fn ($method) => Str::of($method)->snake()->explode('_')->last())
            ->flatten()
            ->values()
            ->mapWithKeys(
                fn($easing) => [
                    $easing => collect(range(1, $totalTicks))
                        ->map(function ($i) use ($easing, $totalTicks) {
                            $percentage = $i / $totalTicks;
                            $position = Easings::{$easing}($percentage);

                            return intval(round($position * $this->dotSafeZone));
                        }),
                ]
            );

        $this->lowestValue = max(
            abs($this->easings->map(fn($frames) => $frames->min())->min()),
            $this->easings->map(fn($frames) => $frames->max() - $this->dotSafeZone)->max(),
        );

        $listener = KeyPressListener::for($this)->on(['q', Key::CTRL_C], function () {
            $this->exitAltScreen();
            $this->terminal()->exit();
        });

        $tick = 0;

        while (true) {
            foreach ($this->easings as $easing => $frames) {
                $this->dotPositions[$easing] = $frames[$tick];
            }

            $listener->once();
            $this->render();
            usleep($microsecondsPerFrame);

            $tick++;

            if ($tick === $totalTicks) {
                usleep(500_000);
                $tick = 0;
                $this->direction *= -1;
            }
        }
    }

    public function value(): mixed
    {
        return null;
    }
}
