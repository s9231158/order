<?php

namespace App\RepositoryV2;

use App\Models\Ecpay as EcpayModel;
use Throwable;

class Ecpay
{
    public function Create($EcpayInfo)
    {
        try {
            EcpayModel::create($EcpayInfo);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}
