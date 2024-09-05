<?php

function imageToAscii($path, $maxWidth = 100, $maxHeight = 100)
{
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

            $line[] = $characters[(int) max(($percentage * count($characters)) - 1, 0)];
        }

        $lines[] = $line;
    }

    return $lines;
}


$ascii = imageToAscii(__DIR__ . '/storage/gif-viewer/frames/3o7aTLkyh3yAG6DEuQ/1.jpg', 100, 100);

file_put_contents(__DIR__ . '/test.txt', implode("\n", array_map(fn($line) => implode('', $line), $ascii)));
