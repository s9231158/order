<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcpayBack extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected  $primaryKey = 'merchant_trade_no';
    protected $fillable = [
        'merchant_trade_no',
        'merchant_id',
        'trade_date',
        'rtn_code',
        'rtn_msg',
        'amount',
        'payment_date',
        'check_mac_value'
    ];
}
