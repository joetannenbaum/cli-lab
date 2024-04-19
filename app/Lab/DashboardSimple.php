<?php

namespace App\Lab;

use App\Lab\Dashboard\Chat;
use App\Lab\Dashboard\HalPulse;
use App\Lab\Dashboard\RandomValue;
use App\Lab\Renderers\DashboardSimpleRenderer;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Prompt;

class DashboardSimple extends Prompt
{
    use Loops;
    use RegistersRenderers;
    use SetsUpAndResets;
    use TypedValue;

    public array $components = [];

    public RandomValue $health;

    public RandomValue $bar1;

    public RandomValue $bar2;

    public RandomValue $bar3;

    public HalPulse $halPulse;

    public Chat $chat;

    public function __construct()
    {
        $this->registerRenderer(DashboardSimpleRenderer::class);

        $this->health = new RandomValue(
            lowerLimit: 25,
            upperLimit: 75,
            initialValue: 50,
            pauseAfter: 10,
        );

        $this->bar1 = new RandomValue(lowerLimit: 10, upperLimit: 90);
        $this->bar2 = new RandomValue(lowerLimit: 10, upperLimit: 90);
        $this->bar3 = new RandomValue(lowerLimit: 10, upperLimit: 90);

        $this->halPulse = new HalPulse;
        $this->chat = new Chat;

        $this->registerLoopables(
            $this->health,
            $this->halPulse,
            $this->chat,
            $this->bar1,
            $this->bar2,
            $this->bar3,
        );
    }

    public function run()
    {
        $this->setup($this->showDashboard(...));
    }

    public function value(): mixed
    {
        return null;
    }

    protected function showDashboard(): void
    {
        $listener = KeyPressListener::for($this)->listenForQuit();

        $this->loop(function () use ($listener) {
            $this->render();
            $listener->once();
        }, 100_000);
    }

    public function valueWithCursor(int $maxWidth): string
    {
        if ($this->chat->currentMessage === '') {
            return $this->dim($this->addCursor('Chat with HAL', 0, $maxWidth));
        }

        return $this->addCursor($this->chat->currentMessage, strlen($this->chat->currentMessage), $maxWidth);
    }
}
