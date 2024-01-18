<?php

namespace App\Lab;

use App\Lab\Concerns\CreatesAnAltScreen;
use App\Lab\Concerns\Loops;
use App\Lab\Concerns\SetsUpAndResets;
use App\Lab\Input\KeyPressListener;
use App\Lab\Concerns\RegistersThemes;
use App\Lab\Nissan\Battery;
use App\Lab\Nissan\EngineTemp;
use App\Lab\Nissan\Fuel;
use App\Lab\Nissan\OilLevel;
use App\Lab\Nissan\Rpm;
use App\Lab\Renderers\NissanRenderer;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Nissan extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersThemes;
    use SetsUpAndResets;
    use TypedValue;

    public int $sleepFor = 50_000;

    public bool $carStarted = false;

    public function __construct()
    {
        $this->registerTheme(NissanRenderer::class);

        $this->registerLoopable(Fuel::class);
        $this->registerLoopable(EngineTemp::class);
        $this->registerLoopable(OilLevel::class);
        $this->registerLoopable(Battery::class);
        $this->registerLoopable(Rpm::class);

        $this->createAltScreen();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function run()
    {
        $this->setup($this->showDashboard(...));
    }

    public function value(): mixed
    {
    }

    protected function showDashboard()
    {
        $this->loop($this->runLoop(...));
    }

    protected function runLoop()
    {
        $this->render();

        $key = KeyPressListener::once();

        if ($key === Key::CTRL_C || $key === 'q') {
            $this->terminal()->exit();
        }

        if ($key === Key::ENTER) {
            $this->carStarted = !$this->carStarted;

            foreach ($this->loopables as $component) {
                if ($this->carStarted) {
                    $component->startCar();
                } else {
                    $component->stopCar();
                }
            }
        }

        if (!$this->carStarted) {
            return;
        }

        if ($key === ' ') {
            foreach ($this->loopables as $component) {
                $component->rev();
            }
        }

        if ($key === 'b') {
            $this->loopables[Rpm::class]->brake();
        }
    }
}
