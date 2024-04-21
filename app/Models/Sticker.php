<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sticker extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'verification_url',
    ];
}
