<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaruantFavoritCount extends Model
{
    protected $fillable = ['Count', 'starttime', 'endtime', 'rid'];
    use HasFactory;
}
