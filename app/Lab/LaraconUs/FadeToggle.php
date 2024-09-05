<?php

namespace App\Lab\LaraconUs;

use App\Lab\Support\Animation;
use Chewie\Art;
use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Chewie\Support\Animatable;
use Illuminate\Support\Collection;

class FadeToggle implements Loopable
{
    use Ticks;

    public Animatable $value;

    protected Collection $artOutput;

    protected Collection $artLines;

    protected Collection $mapping;

    protected $totalCharacters = 0;

    public $done = false;

    public function __construct(protected string $artName)
    {
        $art = Art::get($this->artName);
        $this->artLines = collect(explode("\n", $art))->map(fn($line) => mb_str_split($line));


        $this->totalCharacters = $this->artLines->map(fn($line) => collect($line)->filter(fn($l) => $l !== ' ')->count())->sum();

        $values = $this->getShuffledValues();

        $this->mapping = $this->artLines->map(
            fn($line) => collect($line)->map(
                function ($l) use ($values) {
                    $val = $l === ' '
                        ? Animatable::fromValue(0)->upperLimit(0)->lowerLimit(0)
                        // : Animatable::fromValue(24)->lowerLimit(0)->upperLimit(24);
                        : Animatable::fromValue($val = $values->shift())->lowerLimit(0)->upperLimit($val);

                    // $val->pauseAfter(50);

                    $val->to(0);

                    return $val;
                }
            )
        );
    }

    public function onTick(): void
    {
        $this->mapping->each(function ($line) {
            $line->each(function ($value) {
                $value->animate();
            });
        });

        $this->done = $this->mapping->every(fn($line) => $line->every(fn($value) => !$value->isAnimating()));

        // if ($this->done) {
        //     $values = $this->getShuffledValues();

        //     $this->mapping->each(function ($line, $lineIndex) use ($values) {
        //         $line->each(function ($value, $valueIndex) use ($values, $lineIndex) {
        //             if ($this->artLines[$lineIndex][$valueIndex] === ' ') {
        //                 return;
        //             }

        //             $value->delay($values->shift());
        //             $value->toggle();
        //         });
        //     });

        //     sleep(1);
        // }
    }

    protected function getShuffledValues()
    {
        $values = collect(range(23, (int) ceil($this->totalCharacters / 2) + 23));

        return $values->concat($values)->shuffle();
    }

    public function getLines()
    {
        return $this->mapping->map(function ($line, $lineIndex) {
            return $line->map(function ($value, $valueIndex) use ($lineIndex) {
                return [$value->current(), $this->artLines[$lineIndex][$valueIndex]];
            });
        });
    }
}
