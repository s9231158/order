<?php

namespace App\RepositoryV2;

use App\Models\Ecpay_back;
use Throwable;

class EcpayBackRepositoryV2
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
