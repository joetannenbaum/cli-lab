<?php

namespace App\Lab;

use App\Lab\Dashboard\BarGraph;
use App\Lab\Dashboard\Chat;
use App\Lab\Dashboard\HalPulse;
use App\Lab\Dashboard\Health;
use App\Lab\Dashboard\PercentageBar;
use App\Lab\Dashboard\RandomValue;
use App\Lab\Renderers\DashboardRenderer;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Prompt;

class Dashboard extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersRenderers;
    use SetsUpAndResets;
    use TypedValue;

    public array $components = [];

    public RandomValue $health;

    public RandomValue $percentageBar;

    public HalPulse $halPulse;

    public Chat $chat;

    public RandomValue $bar1;

    public RandomValue $bar2;

    public RandomValue $bar3;

    public function __construct()
    {
        $this->registerRenderer(DashboardRenderer::class);

        $this->health = new RandomValue(
            lowerLimit: 25,
            upperLimit: 75,
            initialValue: 50,
            pauseAfter: 10,
        );

        $this->percentageBar = new RandomValue(
            lowerLimit: 25,
            upperLimit: 75,
            initialValue: 25,
            pauseAfter: 14,
        );

        $this->bar1 = new RandomValue(lowerLimit: 25, upperLimit: 75);
        $this->bar2 = new RandomValue(lowerLimit: 25, upperLimit: 75);
        $this->bar3 = new RandomValue(lowerLimit: 25, upperLimit: 75);

        $this->halPulse = new HalPulse;
        $this->chat = new Chat;

        $this->registerLoopables(
            $this->health,
            $this->percentageBar,
            $this->halPulse,
            $this->chat,
            $this->bar1,
            $this->bar2,
            $this->bar3,
        );

        $this->createAltScreen();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function run()
    {
        $listener = KeyPressListener::for($this)
            ->onUp(fn() => $this->sleepBetweenLoops = max(50_000, $this->sleepBetweenLoops - 50_000))
            ->onDown(fn() => $this->sleepBetweenLoops += 50_000)
            ->listenForQuit();

        $this->setup(fn() => $this->loop(fn() => $this->showDashboard($listener), 100_000));
    }

    public function value(): mixed
    {
        //
    }

    public function valueWithCursor(int $maxWidth): string
    {
        if ($this->chat->currentlyTyping === '') {
            return $this->dim($this->addCursor('Chat with HAL', 0, $maxWidth));
        }

        return $this->addCursor($this->chat->currentlyTyping, strlen($this->chat->currentlyTyping), $maxWidth);
    }

    protected function showDashboard(KeyPressListener $listener): void
    {
        $this->render();
        $listener->once();
    }
}
