<?php

namespace App\RepositoryV2;

use App\Models\Ecpay_back;

class EcpayBackRepositoryV2
{
    public function SaveEcpayCallBack($EcpayBackInfo)
    {
        Ecpay_back::create($EcpayBackInfo);
    }
}