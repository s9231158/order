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
    public function orderinfo(){
        return $this->hasMany(Order_info::class,'oid');
    }
    public function user(){
        return $this->belongsTo(User::class,'id');
    }
    public function record(){
        return $this->hasMany(Wallet_Record::class,'oid');
    }
}
