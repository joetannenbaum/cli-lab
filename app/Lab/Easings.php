<?php

namespace App\Lab;

class Easings
{
    public static function easeInSine($x)
    {
        return 1 - cos(($x * pi()) / 2);
    }

    public static function easeOutSine($x)
    {
        return sin(($x * pi()) / 2);
    }


    public static function easeInOutSine($x)
    {
        return - (cos(pi() * $x) - 1) / 2;
    }

    public static function easeInQuad($x)
    {
        return $x * $x;
    }

    public static function easeOutQuad($x)
    {
        return 1 - (1 - $x) * (1 - $x);
    }

    public static function easeInOutQuad($x)
    {
        return $x < 0.5 ? 2 * $x * $x : 1 - pow(-2 * $x + 2, 2) / 2;
    }

    public static function easeInCubic($x)
    {
        return $x * $x * $x;
    }

    public static function easeOutCubic($x)
    {
        return 1 - pow(1 - $x, 3);
    }

    public static function easeInOutCubic($x)
    {
        return $x < 0.5 ? 4 * $x * $x * $x : 1 - pow(-2 * $x + 2, 3) / 2;
    }

    public static function easeInQuart($x)
    {
        return $x * $x * $x * $x;
    }

    public static function easeOutQuart($x)
    {
        return 1 - pow(1 - $x, 4);
    }

    public static function easeInOutQuart($x)
    {
        return $x < 0.5 ? 8 * $x * $x * $x * $x : 1 - pow(-2 * $x + 2, 4) / 2;
    }

    public static function easeInQuint($x)
    {
        return $x * $x * $x * $x * $x;
    }

    public static function easeOutQuint($x)
    {
        return 1 - pow(1 - $x, 5);
    }

    public static function easeInOutQuint($x)
    {
        return $x < 0.5 ? 16 * $x * $x * $x * $x * $x : 1 - pow(-2 * $x + 2, 5) / 2;
    }

    public static function easeInExpo($x)
    {
        return $x === 0 ? 0 : pow(2, 10 * $x - 10);
    }

    public static function easeOutExpo($x)
    {
        return $x === 1 ? 1 : 1 - pow(2, -10 * $x);
    }

    public static function easeInOutExpo($x)
    {
        if ($x === 0 || $x === 1) {
            return $x;
        }

        return $x < 0.5
            ? pow(2, 20 * $x - 10) / 2
            : (2 - pow(2, -20 * $x + 10)) / 2;
    }

    public static function easeInCirc($x)
    {
        return 1 - sqrt(1 - pow($x, 2));
    }

    public static function easeOutCirc($x)
    {
        return sqrt(1 - pow($x - 1, 2));
    }

    public static function easeInOutCirc($x)
    {
        return $x < 0.5
            ? (1 - sqrt(1 - pow(2 * $x, 2))) / 2
            : (sqrt(1 - pow(-2 * $x + 2, 2)) + 1) / 2;
    }

    public static function easeInBack($x)
    {
        $c1 = 1.70158;
        $c3 = $c1 + 1;

        return $c3 * $x * $x * $x - $c1 * $x * $x;
    }

    public static function easeOutBack($x)
    {
        $c1 = 1.70158;
        $c3 = $c1 + 1;

        return 1 + $c3 * pow($x - 1, 3) + $c1 * pow($x - 1, 2);
    }

    public static function easeInOutBack($x)
    {
        $c1 = 1.70158;
        $c2 = $c1 * 1.525;

        return $x < 0.5
            ? (pow(2 * $x, 2) * (($c2 + 1) * 2 * $x - $c2)) / 2
            : (pow(2 * $x - 2, 2) * (($c2 + 1) * ($x * 2 - 2) + $c2) + 2) / 2;
    }

    public static function easeInElastic($x)
    {
        $c4 = (2 * pi()) / 3;

        if ($x === 0 || $x === 1) {
            return $x;
        }

        return -pow(2, 10 * $x - 10) * sin(($x * 10 - 10.75) * $c4);
    }

    public static function easeOutElastic($x)
    {
        $c4 = (2 * pi()) / 3;

        if ($x === 0 || $x === 1) {
            return $x;
        }

        return pow(2, -10 * $x) * sin(($x * 10 - 0.75) * $c4) + 1;
    }

    public static function easeInOutElastic($x)
    {
        $c5 = (2 * pi()) / 4.5;

        if ($x === 0 || $x === 1) {
            return $x;
        }

        return $x < 0.5
            ? - (pow(2, 20 * $x - 10) * sin((20 * $x - 11.125) * $c5)) / 2
            : (pow(2, -20 * $x + 10) * sin((20 * $x - 11.125) * $c5)) / 2 + 1;
    }

    public static function easeInBounce($x)
    {
        return 1 - static::easeOutBounce(1 - $x);
    }

    public static function easeOutBounce($x)
    {
        $n1 = 7.5625;
        $d1 = 2.75;

        if ($x < 1 / $d1) {
            return $n1 * $x * $x;
        }

        if ($x < 2 / $d1) {
            return $n1 * ($x -= 1.5 / $d1) * $x + 0.75;
        }

        if ($x < 2.5 / $d1) {
            return $n1 * ($x -= 2.25 / $d1) * $x + 0.9375;
        }

        return $n1 * ($x -= 2.625 / $d1) * $x + 0.984375;
    }

    public static function easeInOutBounce($x)
    {
        return $x < 0.5
            ? (1 - static::easeOutBounce(1 - 2 * $x)) / 2
            : (1 + static::easeOutBounce(2 * $x - 1)) / 2;
    }
}
