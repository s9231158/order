<?php
namespace App\Service;

use App\Models\Order_info;

class OrderInfoService
{
    public function AddOrderInfo($OrferInfoInfo)
    {
        Order_info::create($OrferInfoInfo);
    }




}




?>