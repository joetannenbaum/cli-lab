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
use Chewie\Concerns\RegistersThemes;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Illuminate\Support\Collection;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class LaraconUsClosing extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;
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

    public BouncingBall $bouncingBall;

    public Collection $bouncingBalls;

    public int $bouncingBallHeight = 0;

    public FadeToggle $qrCode;

    public function __construct()
    {
        $this->registerTheme(LaraconUsClosingRenderer::class);
        Art::setDirectory(storage_path('ascii/alphabet'));

        // $this->dots = collect();
        $this->bouncingBalls = collect();

        $this->createAltScreen();

        $this->setup(function () {
            $this->qrCode = new FadeToggle('qr-to-links');

            $this->registerLoopable($this->qrCode);

            $listener = KeyPressListener::for($this)->on(['q', Key::CTRL_C], function () {
                $this->exitAltScreen();
                $this->terminal()->exit();
            });

            $this->loop(function () use ($listener) {
                $listener->once();
                $this->render();

                return !$this->qrCode->done;
            }, 2_500);

            $message = "Thank you,\nLaracon!";

            $letters = mb_str_split($message);

            $this->loop(function () use ($listener, &$letters) {
                $listener->once();
                $this->message .= array_shift($letters);
                $this->render();

                return count($letters) > 0;
            }, 100_000);

            // $this->dots = collect(range(1, $this->dotCount))->map(fn() => new Dot($this->dotHeight));

            // $this->dots->each(fn(Dot $dot) => $this->registerLoopable($dot));

            $this->bouncingBallHeight = $this->terminal()->lines() - 4;
            // $this->bouncingBall = new BouncingBall($this->bouncingBallHeight);
            $this->bouncingBalls = collect(range(1, floor($this->terminal()->cols() / 2)))
                ->map(fn() => new BouncingBall($this->bouncingBallHeight));

            $this->registerLoopables(...$this->bouncingBalls);

            $this->loop(function () use ($listener) {
                $listener->once();
                $this->render();
            }, 15_000);
        });
    }

    public function value(): mixed
    {
        return null;
    }
}
