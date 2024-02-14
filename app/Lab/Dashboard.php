<?php

namespace App\Lab;

use App\Lab\Concerns\CreatesAnAltScreen;
use App\Lab\Concerns\Loops;
use App\Lab\Concerns\RegistersThemes;
use App\Lab\Concerns\SetsUpAndResets;
use App\Lab\Dashboard\BarGraph;
use App\Lab\Dashboard\Chat;
use App\Lab\Dashboard\HalPulse;
use App\Lab\Dashboard\Health;
use App\Lab\Dashboard\PercentageBar;
use App\Lab\Input\KeyPressListener;
use App\Lab\Renderers\DashboardRenderer;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
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
        $this->registerTheme(DashboardRenderer::class);

        $this->health = new Health;
        $this->percentageBar = new PercentageBar;
        $this->halPulse = new HalPulse;
        $this->chat = new Chat;
        $this->barGraph = new BarGraph;

        $this->registerLoopable($this->health);
        $this->registerLoopable($this->percentageBar);
        $this->registerLoopable($this->halPulse);
        $this->registerLoopable($this->chat);
        $this->registerLoopable($this->barGraph);

        $this->createAltScreen();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function run()
    {
        $this->setup(fn () => $this->loop($this->showDashboard(...), 100_000));
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

    protected function showDashboard(): void
    {
        $this->render();

        KeyPressListener::for($this)
            ->onUp(fn () => $this->sleepBetweenLoops = max(50_000, $this->sleepBetweenLoops - 50_000))
            ->onDown(fn () => $this->sleepBetweenLoops += 50_000)
            ->listenForQuit()
            ->once();
    }
}
