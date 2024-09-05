<?php

namespace App\Lab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersThemes;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
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

    public Fuel $fuel;

    public EngineTemp $engineTemp;

    public OilLevel $oilLevel;

    public Battery $battery;

    public Rpm $rpm;

    protected KeyPressListener $listener;

    public function __construct()
    {
        $this->registerTheme(NissanRenderer::class);

        $this->fuel = new Fuel($this);
        $this->engineTemp = new EngineTemp($this);
        $this->oilLevel = new OilLevel($this);
        $this->battery = new Battery($this);
        $this->rpm = new Rpm($this);

        $this->registerLoopables(
            $this->fuel,
            $this->engineTemp,
            $this->oilLevel,
            $this->battery,
            $this->rpm,
        );

        $this->listener = KeyPressListener::for($this)
            ->listenForQuit()
            ->on(Key::ENTER, function () {
                $this->carStarted = !$this->carStarted;

                foreach ($this->loopables as $component) {
                    if ($this->carStarted) {
                        $component->startCar();
                    } else {
                        $component->stopCar();
                    }
                }
            })
            ->on(Key::SPACE, function () {
                foreach ($this->loopables as $component) {
                    $component->rev();
                }
            })
            ->on('b', fn () => $this->rpm->brake());


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

        $this->listener->once();

        if (!$this->carStarted) {
            return;
        }
    }
}
