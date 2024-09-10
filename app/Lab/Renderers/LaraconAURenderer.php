<?php

namespace App\Lab\Renderers;

use App\Lab\LaraconAU;
use App\Lab\LaraconAU\Laser;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsArt;
use Chewie\Concerns\DrawsBigText;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\HasMinimumDimensions;
use Chewie\Output\Lines;
use Laravel\Prompts\Themes\Default\Renderer;

class LaraconAURenderer extends Renderer
{
    use Aligns;
    use DrawsHotkeys;
    use HasMinimumDimensions;
    use DrawsBigText;

    protected $width;

    protected $height;

    public function __invoke(LaraconAU $prompt): string
    {
        $this->width = $prompt->width;
        $this->height = $prompt->height;

        return $this->minDimensions(fn() => $this->renderMessage($prompt), 20, 20);
    }

    protected function renderMessage(LaraconAU $prompt): self
    {
        if ($prompt->enemyWon) {
            $this->setFontDir('alphabet');
            $this->center($this->bigText('Game Over')->merge([
                '',
                '',
                'You lost!',
                '',
                '',
                'Score: ' . $prompt->score,
            ]), $prompt->width, $prompt->height)->each($this->line(...));

            return $this;
        }

        $lasers = $prompt->lasers->keyBy(fn(Laser $laser) => $laser->value->current());
        $enemies = $prompt->enemies->groupBy(fn($enemy) => $enemy->value->current());

        $this->line('Score: ' . $prompt->score);

        foreach (range(0, $prompt->height) as $y) {
            $laser = $lasers->get($y);
            $enemy = $enemies->get($y);

            $line = str_repeat(' ', $prompt->width);

            if ($laser) {
                $line = substr_replace($line, '|', $laser->x, 1);
            }

            if ($enemy) {
                $enemy->each(function ($enemy) use (&$line) {
                    $line = substr_replace($line, $enemy->isHit ? '🔥' : '👾', $enemy->x, 2);
                });
            }

            $this->line($line);
        }

        $this->line(str_repeat(' ', $prompt->shipPosition - 1) . '🚀');

        return $this;
    }
}
