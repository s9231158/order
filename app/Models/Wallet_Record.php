<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet_Record extends Model
{
    use HasFactory;
    protected $fillable = [
        'out',
        'in',
        'oid',
        'uid',
        'eid',
        'status',
        'pid',
    ];

    public function ecpay(){
        return $this->belongsTo(Ecpay::class,'merchant_trade_no');
    }
    // public function ecpay(){
    //     return $this->hasOne(Ecpay::class,'merchant_trade_no');
    // }
    public function order(){
        return $this->belongsTo(Order::class,'id');
    }

}
