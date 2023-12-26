<?php

namespace App\RepositoryV2;

use App\Models\OrderInfo as Order_info;
use Throwable;

class OrderInfo
{
    public function Create($OrderInfo)
    {
        try {
            return Order_info::insert($OrderInfo);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}
