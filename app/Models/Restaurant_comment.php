<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant_comment extends Model
{
    protected $fillable = ['point', 'comment', 'uid', 'rid'];
    use HasFactory;
}
