<?php

namespace App\Lab\GifViewer;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Chewie\Support\Animatable;
use Illuminate\Support\Collection;

class Gif implements Loopable
{
    use Ticks;

    protected $baseCharacters = '`.-\':_,^=;><+!rc*/z?sLTv)J7(|Fi{C}fI31tlu[neoZ5Yxjya]2ESwqkP6h9d4VpOGbUAKXHm8RD#$Bg0MNWQ%&@';

    protected $brightness = 1;

    protected $characters;

    public Collection $frames;

    protected Collection $baseFrames;

    public int $currentFrame = 0;

    public function __construct(string $dir, protected int $maxWidth, protected int $maxHeight)
    {
        $this->characters = str_split(
            str_repeat(' ', $this->brightness * 10) . $this->baseCharacters
        );

        $this->baseFrames = collect(glob("{$dir}/*.jpg"))->sort(SORT_NATURAL)->values()->map(function ($path) {
            $image = imagecreatefromjpeg($path);

            while (imagesx($image) > $this->maxWidth || imagesy($image) > $this->maxHeight) {
                $image = imagescale(
                    $image,
                    (int) (imagesx($image) * .75),
                    (int) (imagesy($image) * .75)
                );
            }

            $width = imagesx($image);
            $height = imagesy($image);

            $lines = [];

            for ($y = 0; $y < $height; $y++) {
                $line = [];

                for ($x = 0; $x < $width; $x++) {
                    $rgb = imagecolorat($image, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    $average = ($r + $g + $b) / 3;
                    $percentage = $average / 255;

                    $line[] = [$percentage, collect([$r, $g, $b])->join(';')];
                }

                $lines[] = $line;
            }

            return $lines;
        });

        $this->adjustBrightness(0);
    }

    public function adjustBrightness($by)
    {
        $this->brightness  = max(0, min(10, $this->brightness + $by));

        $this->characters = str_split(
            str_repeat(' ', $this->brightness * 10) . $this->baseCharacters
        );

        $this->frames = $this->baseFrames->map(function ($frame) {
            return collect($frame)->map(function ($line) {
                return collect($line)->map(function ($data) {
                    [$percentage, $ansiEscapeCode] = $data;
                    return "\e[38;2;{$ansiEscapeCode}m" . $this->characters[(int) max(($percentage * count($this->characters)) - 1, 0)] . "\e[0m";
                })->join('');
            });
        });
    }

    public function onTick()
    {
        $this->onNthTick(2, function () {
            $this->currentFrame = ($this->currentFrame + 1) % $this->frames->count();
        });
    }
}
