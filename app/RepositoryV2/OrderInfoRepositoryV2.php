<?php

namespace App\RepositoryV2;

use App\Models\Order_info;

class OrderInfoRepositoryV2
{
    public function SaveOrderInfo($OrferInfo)
    {
        return Order_info::insert($OrferInfo);
    }
}