<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderInfo extends Model
{
    use HasFactory;
    public $timestamps = true;

    protected $fillable = [
        'price',
        'quanlity',
        'name',
        'oid ',
        'description',
    ];
}
