<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProngGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_one',
        'player_two',
        'player_one_position',
        'player_two_position',
        'ball_x',
        'ball_y',
        'ball_direction',
        'ball_speed',
    ];

    protected $casts = [
        'player_one' => 'boolean',
        'player_two' => 'boolean',
        'player_one_position' => 'integer',
        'player_two_position' => 'integer',
        'ball_x' => 'integer',
        'ball_y' => 'integer',
    ];
}
