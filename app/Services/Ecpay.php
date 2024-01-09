<?php

namespace App\Services;

use App\Models\Ecpay as EcpayModel;

class Ecpay
{
    public function create($info)
    {
        $goodInfo = [
            "merchant_id" => $info['merchant_id'],
            "merchant_trade_no" => $info['merchant_trade_no'],
            "merchant_trade_date" => $info['merchant_trade_date'],
            "payment_type" => $info['payment_type'],
            "amount" => $info['amount'],
            "trade_desc" => $info['trade_desc'],
            "item_name" => $info['item_name'],
            "return_url" => $info['return_url'],
            "choose_payment" => $info['choose_payment'],
            "check_mac_value" => $info['check_mac_value'],
            "encrypt_type" => $info['encrypt_type'],
            "lang" => $info['lang']
        ];
        return EcpayModel::create($goodInfo);
    }
}