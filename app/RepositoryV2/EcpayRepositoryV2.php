<?php

namespace App\RepositoryV2;

use App\Models\Ecpay;

class EcpayRepositoryV2
{
    public function SaveEcpay($Data)
    {
        Ecpay::create($Data);
    }
}