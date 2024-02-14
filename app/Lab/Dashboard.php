<?php

namespace App\Lab;

use App\Lab\Dashboard\BarGraph;
use App\Lab\Dashboard\Chat;
use App\Lab\Dashboard\HalPulse;
use App\Lab\Dashboard\Health;
use App\Lab\Dashboard\PercentageBar;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersThemes;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Prompt;

class Dashboard extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersThemes;
    use SetsUpAndResets;
    use TypedValue;

    public array $components = [];

    public Health $health;

    public PercentageBar $percentageBar;

    public HalPulse $halPulse;

    public Chat $chat;

    public BarGraph $barGraph;

    public function __construct()
    {
        $this->registerTheme();

        $this->health = new Health;
        $this->percentageBar = new PercentageBar;
        $this->halPulse = new HalPulse;
        $this->chat = new Chat;
        $this->barGraph = new BarGraph;

        $this->registerLoopables(
            $this->health,
            $this->percentageBar,
            $this->halPulse,
            $this->chat,
            $this->barGraph,
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
            ->onUp(fn () => $this->sleepBetweenLoops = max(50_000, $this->sleepBetweenLoops - 50_000))
            ->onDown(fn () => $this->sleepBetweenLoops += 50_000)
            ->listenForQuit();

        $this->setup(fn () => $this->loop(fn () => $this->showDashboard($listener), 100_000));
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
