<?php

namespace App\Services;

use App\Models\EcpayBack as EcpayBackModel;

class EcpayBack
{
    public function create($info)
    {
        $goodInfo = [
            'merchant_id' => $info['merchant_id'],
            'trade_date' => $info['trade_date'],
            'check_mac_value' => $info['check_mac_value'],
            'rtn_code' => $info['rtn_code'],
            'rtn_msg' => $info['rtn_msg'],
            'amount' => $info['amount'],
            'payment_date' => $info['payment_date'],
            'merchant_trade_no' => $info['merchant_trade_no']
        ];
        return EcpayBackModel::create($goodInfo);
    }
}
