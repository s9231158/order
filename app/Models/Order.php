<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid',
        'rid',
        'ordertime',
        'taketime',
        'total',
        'phone',
        'address',
        'status',
    ];
}
