<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'rid',
    ];
}
