<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ecpay extends Model
{
    use HasFactory;
    protected $fillable = [
        'merchant_id',
        'merchant_trade_no',
        'merchant_trade_date',
        'payment_type',
        'amount',
        'trade_desc',
        'item_name',
        'return_url',
        'choose_payment',
        'check_mac_value',
        'encrypt_type',
        'lang',
    ];
    // public function Record(){
    //     return $this->belongsTo(Wallet_Record::class,'eid');
    // }
    public function Record()
    {
        return $this->hasOne(Wallet_Record::class, 'eid');
    }
}
