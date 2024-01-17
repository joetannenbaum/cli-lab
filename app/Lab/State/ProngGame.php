<?php

declare(strict_types=1);

namespace App\Lab\State;

use App\Models\ProngGame as ModelsProngGame;
use Exception;
use Illuminate\Support\Collection;
use SysvSharedMemory;

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

    public int $ballSpeedLevel = 1;

    public ?int $ballSpeed = 25_000;

    public ?int $winner = null;

    public bool $observer = false;

    public bool $againstComputer = false;

    public bool $everyoneReady = false;

    public int $playerNumber = 0;

    protected SysvSharedMemory $shm;

    protected string $writeFilePath;

    protected string $readFilePath;

    public function __construct(public ModelsProngGame $model)
    {
        //
    }

    public static function exists(string $id): bool
    {
        return ModelsProngGame::where('game_id', $id)->exists();
    }

    public function setPlayer()
    {
        if (!$this->model->player_one) {
            $this->model->player_one = true;
            $this->model->save();
            $this->playerNumber = 1;
        } elseif (!$this->model->player_two) {
            $this->model->player_two = true;
            $this->model->save();
            $this->playerNumber = 2;
        } else {
            // You just want to observe this game I guess
            $this->observer = true;
            $this->playerNumber = 3;
        }

        $this->writeFilePath = storage_path('prong/' . $this->model->game_id . '-' . $this->playerNumber);
        $this->readFilePath = storage_path('prong/' . $this->model->game_id . '-' . ($this->playerNumber === 1 ? 2 : 1));

        if (!file_exists($this->writeFilePath)) {
            file_put_contents($this->writeFilePath, json_encode([]));
        }

        if (!file_exists($this->readFilePath)) {
            file_put_contents($this->readFilePath, json_encode([]));
        }

        $this->update('playerTwo', true);
        $this->update('playerOne', true);
    }

    public static function create(string $id): static
    {
        return new static(
            ModelsProngGame::create([
                'game_id'   => $id,
                'shared_id' => str_replace('.', '', (string) microtime(true)) . rand(0, 1000),
            ]),
        );
    }

    public static function get(string $id): ?static
    {
        if (!static::exists($id)) {
            return null;
        }

        $self = new static(ModelsProngGame::where('game_id', $id)->first());

        $self->fresh();

        return $self;
    }

    public function update($key, $value, $log = false): void
    {
        $this->updateMany([$key => $value], $log);
    }

    public function updateMany(array $values, $log = false): void
    {
        $state = file_get_contents($this->writeFilePath);

        $state = json_decode($state, true);

        foreach ($values as $key => $value) {
            if ($value === $this->{$key}) {
                if ($log) {
                    ray('skipped', $key, $value);
                }

                // No need to update this it's already in the state
                continue;
            }

            // if ($log) {
            ray('updated', $key, $value);
            //     // ray('playerOneReady', $this->playerOneReady);
            // }


            // shm_put_var($this->shm, $this->getMemoryKey($key), $value);
            $state[$key] = $value;

            $this->{$key} = $value;
        }

        file_put_contents($this->writeFilePath, json_encode($state));
    }

    public function fresh(): void
    {
        if (!isset($this->readFilePath)) {
            return;
        }

        $state = file_get_contents($this->readFilePath);
        $state = json_decode($state, true);

        foreach ($state as $key => $value) {
            $this->{$key} = $value;
        }
        // $this->keys()->each(function ($key) {
        //     $memKey = $this->getMemoryKey($key);

        //     if (!shm_has_var($this->shm, $memKey)) {
        //         return;
        //     }

        //     $value = shm_get_var($this->shm, $memKey);

        //     if (is_bool($this->{$key})) {
        //         $this->{$key} = (bool) $value;
        //     } else {
        //         $this->{$key} = (int) $value;
        //     }
        // });
    }

    public function reset(): void
    {
        // $this->keys()->each(function ($key) {
        //     $memKey = $this->getMemoryKey($key);

        //     if (in_array($key, ['playerOneReady', 'playerTwoReady'])) {
        //         return;
        //     }

        //     if (shm_has_var($this->shm, $memKey)) {
        //         shm_remove_var($this->shm, $memKey);
        //     }

        //     if (is_bool($this->{$key})) {
        //         $this->{$key} = false;
        //     } else {
        //         $this->{$key} = null;
        //     }
        // });

        // if ($playerNumber === 1) {
        //     $this->update('playerOneReady', false);
        // } else if ($playerNumber === 2) {
        //     $this->update('playerTwoReady', false);
        // }
    }

    public function flush()
    {
        // $this->keys()
        //     ->map(fn ($key) => $this->getMemoryKey($key))
        //     ->filter(fn ($key) => shm_has_var($this->shm, $key))
        //     ->each(fn ($key) => shm_remove_var($this->shm, $key));
    }

    public function __destruct()
    {
        $this->flush();

        // try {
        //     shm_remove($this->shm);
        // } catch (Exception) {
        //     //throw $th;
        // }

        // shm_detach($this->shm);
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

    protected function getMemoryKey(string $property): int
    {
        return (int) ($this->model->shared_id . $this->keys()->search($property));
    }
}
