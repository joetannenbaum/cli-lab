<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LaraconUsTalkConvert extends Command
{
    protected $signature = 'app:laracon-us-talk-convert';

    protected $description = 'Command description';

    protected $characters;

    public function handle()
    {

        // unlink(storage_path('laracon-us-talk-ascii.txt'));

        $this->characters = str_split(
            str_repeat(' ', 0 * 10) . '`.-\':_,^=;><+!rc*/z?sLTv)J7(|Fi{C}fI31tlu[neoZ5Yxjya]2ESwqkP6h9d4VpOGbUAKXHm8RD#$Bg0MNWQ%&@',
        );

        // $files = collect(glob(
        //     storage_path('laracon-us-talk-ascii/*.json')
        // ))->sort(SORT_NATURAL)->values()->each(function ($path) {
        //     $result = collect(json_decode(file_get_contents($path)))->map(function ($line) {
        //         return collect($line)->map(function ($data) {
        //             [$percentage, $ansiEscapeCode] = $data;
        //             return "\e[38;2;{$ansiEscapeCode}m" . $this->characters[(int) max(($percentage * count($this->characters)) - 1, 0)];
        //         })->join('');
        //     });

        //     file_put_contents(storage_path('laracon-us-talk-ascii.txt'), $result->toJson() . PHP_EOL, FILE_APPEND);
        //     // file_put_contents(storage_path('laracon-us-talk-ascii.txt'), file_get_contents($path) . PHP_EOL, FILE_APPEND);
        // });

        // dd('stop');
        collect(glob(storage_path('laracon-us-talk/*.jpg')))->sort(SORT_NATURAL)->values()->each(function ($path) {
            $maxHeight = 100;
            $maxWidth = 100;
            $contrast = 5;

            $characters = str_split(
                str_repeat(' ', $contrast * 10)
                    . '`.-\':_,^=;><+!rc*/z?sLTv)J7(|Fi{C}fI31tlu[neoZ5Yxjya]2ESwqkP6h9d4VpOGbUAKXHm8RD#$Bg0MNWQ%&@'
            );

            $image = imagecreatefromjpeg($path);

            while (imagesx($image) > $maxWidth || imagesy($image) > $maxHeight) {
                $image = imagescale($image, (int) (imagesx($image) * .75), (int) (imagesy($image) * .75));
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

                    // $line[] = $characters[(int) max(($percentage * count($characters)) - 1, 0)];
                    $line[] = [$percentage, collect([$r, $g, $b])->join(';')];
                }

                $lines[] = $line;
            }

            $result = collect($lines)->map(function ($line) {
                return collect($line)->map(function ($data) {
                    [$percentage, $ansiEscapeCode] = $data;
                    return "\e[38;2;{$ansiEscapeCode}m" . $this->characters[(int) max(($percentage * count($this->characters)) - 1, 0)];
                })->join('');
            });

            file_put_contents(storage_path('laracon-us-talk-ascii.txt'), $result->toJson() . PHP_EOL, FILE_APPEND);

            // file_put_contents(storage_path('laracon-us-talk-ascii/' . pathinfo($path, PATHINFO_FILENAME) . '.json'), json_encode($lines));

            // return $lines;
        });
    }
}
