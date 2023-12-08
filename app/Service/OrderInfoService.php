<?php
namespace App\Service;

use App\Models\Order_info;

class OrderInfoService
{
    public function AddOrderInfo($OrferInfoInfo)
    {
        return Order_info::insert($OrferInfoInfo);
    }




}




?>