<?php

declare(strict_types=1);

namespace App\Lab\State;

use App\Models\ProngGame as ModelsProngGame;
use Exception;
use Illuminate\Database\Events\ModelsPruned;
use Illuminate\Support\Collection;
use SysvSharedMemory;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

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

    public int $paddleHeight = 5;

    public int $ballSpeed = 25_000;

    public ?int $winner = null;

    public bool $observer = false;

    public bool $againstComputer = false;

    public bool $everyoneReady = false;

    public int $playerNumber = 0;

    protected ModelsProngGame $originalModel;

    protected SysvSharedMemory $shm;

    public function __construct(public ModelsProngGame $model)
    {
        $this->originalModel = ModelsProngGame::find($model->id);
    }

    public static function exists(string $id): bool
    {
        return ModelsProngGame::where('game_id', $id)->first() !== null;
    }

    public static function create(string $id): static
    {
        return new static(
            ModelsProngGame::create([
                'game_id'   => $id,
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
        $this->updateMany([
            $key => $value,
        ], $log);
    }

    public function updateMany(array $values, $log = false): void
    {
        $toUpdate = [];

        foreach ($values as $key => $value) {
            if ($value === $this->{$key}) {
                if ($log) {
                    ray('skipped', $key, $value);
                }

                // No need to update this it's already in the state
                continue;
            }

            if ($log) {
                ray('updated', $key, $value);
            }

            $modelKey = Str::snake($key);

            $toUpdate[$modelKey] = $value;

            $this->{$key} = $value;
        }

        if (count($toUpdate) > 0) {
            $this->model->update($toUpdate);
        }
    }

    public function fresh(): void
    {
        $this->model = $this->model->fresh();

        $this->keys()->each(function ($modelKey, $key) {
            if ($this->model->{$modelKey} === null) {
                return;
            }

            $this->{$key} = $this->model->{$modelKey};
        });
    }

    public function reset(): void
    {
        $toUpdate = Arr::except($this->originalModel->toArray(), [
            'playerOneReady',
            'playerTwoReady',
        ]);

        $this->model->update($toUpdate);

        foreach ($toUpdate as $key => $value) {
            $this->{$key} = $value;
        }
    }

    protected function keys(): Collection
    {
        return collect([
            'playerOne' => 'player_one',
            'playerTwo' => 'player_two',
            'playerOnePosition' => 'player_one_position',
            'playerTwoPosition' => 'player_two_position',
            'ballPositionX' => 'ball_position_x',
            'ballPositionY' => 'ball_position_y',
            'ballDirection' => 'ball_direction',
            'ballSpeed' => 'ball_speed',
            'ballSpeedLevel' => 'ball_speed_level',
            'winner' => 'winner',
            'playerOneReady' => 'player_one_ready',
            'playerTwoReady' => 'player_two_ready',
            'paddleHeight' => 'paddle_height',
        ]);
    }
}
