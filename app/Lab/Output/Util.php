<?php

declare(strict_types=1);

namespace App\Lab\Output;

use Illuminate\Support\Collection;

class Util
{
    public static function stripEscapeSequences(string $text): string
    {
        $text = preg_replace("/\e[^m]*m/", '', $text);

        return preg_replace("/<(?:(?:[fb]g|options)=[a-z,;]+)+>(.*?)<\/>/i", '$1', $text);
    }

    public static function range(...$args): Collection
    {
        if (count($args) === 1) {
            return collect(range(1, $args[0]));
        }

        return collect(range($args[0], $args[1]));
    }

    public static function wordwrap(string $text, int $width = 75, string $break = "\n", bool $cut = false): string
    {
        preg_match_all("/\e[^m]*m/", $text, $matches, PREG_OFFSET_CAPTURE);

        $stripped = preg_replace("/\e[^m]*m/", '', $text);

        preg_match_all("/\b\w{{$width},}\b/", $stripped, $longMatches, PREG_OFFSET_CAPTURE);

        $wrapped = mb_wordwrap($stripped, $width, $break, $cut);

        foreach ($matches[0] as $match) {
            $offset = 0;

            if ($cut) {
                foreach ($longMatches[0] as $long) {
                    if ($long[1] < $match[1]) {
                        $offset += floor(mb_strlen($long[0]) / $width);
                    }
                }
            }

            $wrapped = substr_replace($wrapped, $match[0], $match[1] + $offset, 0);
        }

        $wrapped = explode($break, $wrapped);

        foreach ($wrapped as $i => $line) {
            if ($i === 0) {
                continue;
            }

            preg_match_all("/\e[^m]*m/", $wrapped[$i - 1], $prevLineEscapeCodes);

            $wrapped[$i] = collect($prevLineEscapeCodes[0])->implode('') . $line;
        }

        // Reset styles around line
        return collect($wrapped)->map(fn ($l) => "\e[0m{$l}\e[0m")->implode($break);
    }
}
