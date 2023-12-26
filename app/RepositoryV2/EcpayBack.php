<?php

namespace App\RepositoryV2;

use App\Models\EcpayBack as Ecpay_back;
use Throwable;

class EcpayBack
{
    public function Create($EcpayBackInfo)
    {
        try {
            Ecpay_back::create($EcpayBackInfo);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}
