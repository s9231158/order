<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant_history extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid',
        'rid',
        'created_at',
        'updated_at'
    ];
}
