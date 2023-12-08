<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaruant_Total_Money extends Model
{
    protected $fillable = ['money', 'starttime', 'endtime','rid'];
    use HasFactory;
}
