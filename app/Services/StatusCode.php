<?php

namespace App\Services;

class StatusCode
{
    private $statusCode = [
        'sendApiSuccess' => 0,
        'success' => 1,
        'sendApiFail' => 10,
        'responseFail' => 11,
        'getMoneyFail' => 12,
        'waitPay' => 13,
        'ecpayFail'=>14,
        'walletNoMoneyFail'=>15,
        'waitEcpayReponse'=>16
    ];
    public function getStatus()
    {
        return $this->statusCode;
    }
}
