<?php

declare(strict_types=1);

namespace App\Lab\State;

use Carbon\CarbonInterval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProngGame
{
    public bool $playerOne = false;

    public bool $playerTwo = false;

    public ?int $playerOnePosition = null;

    public ?int $playerTwoPosition = null;

    public bool $playerOneReady = false;

    public bool $playerTwoReady = false;

    public ?int $ballPositionX = null;

    public ?int $ballPositionY = null;

    public ?int $ballDirection = null;

    public ?int $ballSpeed = null;

    public ?int $winner = null;

    public function __construct(public string $id)
    {
        //
    }

    public static function exists(string $id): bool
    {
        return Cache::has("prong:{$id}");
    }

    public static function create(string $id): static
    {
        Cache::put("prong:{$id}", true);

        return new static($id);
    }

    public static function get(string $id): ?static
    {
        if (!static::exists($id)) {
            return null;
        }

        $self = new static($id);

        $self->fresh();

        return $self;
    }

    public function update($key, $value): void
    {
        Cache::put("prong:{$this->id}:{$key}", $value, CarbonInterval::day());

        $this->{$key} = $value;
    }

    public function updateMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->update($key, $value);
        }
    }

    protected function keys(): Collection
    {
        return collect([
            'playerOne',
            'playerTwo',
            'playerOnePosition',
            'playerTwoPosition',
            'ballPositionX',
            'ballPositionY',
            'ballDirection',
            'ballSpeed',
            'winner',
            'playerOneReady',
            'playerTwoReady',
        ]);
    }

    public function fresh(): void
    {
        $this->keys()->each(function ($key) {
            $value = Cache::get("prong:{$this->id}:{$key}");

            if ($value === null) {
                return;
            }

            if (is_bool($this->{$key})) {
                $this->{$key} = (bool) $value;
            } else {
                $this->{$key} = (int) $value;
            }
        });
    }

    public function reset(): void
    {
        $this->keys()->each(function ($key) {
            Cache::forget("prong:{$this->id}:{$key}");

            if (is_bool($this->{$key})) {
                $this->{$key} = false;
            } else {
                $this->{$key} = null;
            }
        });
    }
}
