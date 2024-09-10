<?php

namespace App\Lab;

use App\Lab\LaraconAU\Enemy;
use App\Lab\LaraconAU\Laser;
use App\Lab\Renderers\LaraconAURenderer;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Illuminate\Support\Collection;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class LaraconAU extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersRenderers;
    use Loops;
    use SetsUpAndResets;

    public int $shipPosition;

    public int $width;

    public int $height;

    /** @var Collection<Laser> */
    public Collection $lasers;

    public Collection $enemies;

    public int $score = 0;

    public bool $enemyWon = false;

    public function __construct()
    {
        $this->registerRenderer(LaraconAURenderer::class);

        // $this->createAltScreen();

        $this->width = $this->terminal()->cols() - 4;
        $this->height = $this->terminal()->lines() - 6;

        $this->lasers = collect();
        $this->enemies = collect();

        $this->shipPosition = (int) floor($this->width / 2);

        $this->setup(function () {
            $listener = KeyPressListener::for($this)
                ->onRight(fn() => $this->shipPosition = min($this->width, $this->shipPosition + 1))
                ->onLeft(fn() => $this->shipPosition = max(0, $this->shipPosition - 1))
                ->listenForQuit()
                ->on(Key::SPACE, fn() => $this->fire());

            $this->loop(function () use ($listener) {
                $listener->once();
                $this->spawnEnemy();
                $this->checkForHit();
                $this->checkForEnemyWin();
                $this->render();
            });
        });
    }

    protected function checkForHit()
    {
        $this->lasers->each(function ($laser) {
            $this->enemies->each(function ($enemy) use ($laser) {
                if ($laser->x === $enemy->x && $laser->value->current() === $enemy->value->current()) {
                    $this->removeLaser($laser);
                    $enemy->hit();
                    $this->score++;
                }
            });
        });
    }

    protected function removeLaser(Laser $laser)
    {
        $this->removeLoopable($laser);
        $this->lasers = $this->lasers->filter(fn($l) => $l !== $laser);
    }

    protected function removeEnemy(Enemy $enemy)
    {
        $this->removeLoopable($enemy);
        $this->enemies = $this->enemies->filter(fn($e) => $e !== $enemy);
    }

    protected function checkForEnemyWin()
    {
        $this->enemies->each(function ($enemy) {
            if ($enemy->value->current() === $this->height) {
                $this->enemyWon = true;
            }
        });
    }

    protected function fire()
    {
        $laser = new Laser($this->shipPosition, $this->height, $this->removeLaser(...));

        $this->lasers->push($laser);

        $this->registerLoopable($laser);
    }

    protected function spawnEnemy()
    {
        if ($this->enemies->count() === 5) {
            return;
        }

        $enemy = new Enemy($this->width, $this->height, $this->removeEnemy(...));

        $this->enemies->push($enemy);

        $this->registerLoopable($enemy);
    }

    public function value(): mixed
    {
        return null;
    }
}
