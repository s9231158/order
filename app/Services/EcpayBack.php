<?php

namespace App\Services;

use App\Models\EcpayBack as EcpayBackModel;
use Exception;
use Throwable;

class EcpayBack
{
    public function create($info)
    {
        try {
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
        } catch (Exception $e) {
            throw new Exception("ecpay_back_service_err:" . 500 . $e);
        } catch (Throwable $e) {
            throw new Exception("ecpay_back_service_err:" . 500 . $e);
        }
    }
}
