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

    public function __construct()
    {
        $this->registerTheme(DashboardRenderer::class);

        $this->registerLoopable(Health::class);
        $this->registerLoopable(PercentageBar::class);
        $this->registerLoopable(HalPulse::class);
        $this->registerLoopable(Chat::class);
        $this->registerLoopable(BarGraph::class);

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
        $chat = $this->loopable(Chat::class);

        if ($chat->currentlyTyping === '') {
            return $this->dim($this->addCursor('Chat with HAL', 0, $maxWidth));
        }

        return $this->addCursor($chat->currentlyTyping, strlen($chat->currentlyTyping), $maxWidth);
    }

    protected function showDashboard(): void
    {
        $this->render();

        match (KeyPressListener::once()) {
            'q', Key::CTRL_C => $this->terminal()->exit(),
            Key::UP_ARROW, Key::UP => $this->sleepBetweenLoops = max(50_000, $this->sleepBetweenLoops - 50_000),
            Key::DOWN_ARROW, Key::DOWN => $this->sleepBetweenLoops += 50_000,
            default => null,
        };
    }
}
