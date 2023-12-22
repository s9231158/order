<?php

namespace App\RepositoryV2;

use App\Models\Order_info;
use Throwable;

class OrderInfoRepositoryV2
{
    public function Create($OrferInfo)
    {
        try {
            return Order_info::insert($OrferInfo);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}
