<?php

namespace App\RepositoryV2;

use App\Models\Ecpay;
use Throwable;

class EcpayRepositoryV2
{
    public function Create($EcpayInfo)
    {
        try {
            Ecpay::create($EcpayInfo);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}
