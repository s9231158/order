<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantComment extends Model
{
    protected $fillable = [
        'point',
        'comment',
        'uid',
        'rid',
        'name'
    ];
    use HasFactory;
}
