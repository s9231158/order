<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order_info extends Model
{
    use HasFactory;
    protected $fillable = [
        'price',
        'quanlity',
        'name',
        'oid ',
        'description',
    ];
    public function order()
    {
        return $this->belongsTo(Order::class, 'rid');
    }
}
