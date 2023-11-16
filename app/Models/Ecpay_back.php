<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ecpay_back extends Model
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

    public function ecpay()
    {
        return $this->belongsTo(Ecpay::class, 'merchant_trade_no');
    }
    public function Record()
    {
        return $this->belongsTo(Wallet_Record::class, 'eid');
    }
}
