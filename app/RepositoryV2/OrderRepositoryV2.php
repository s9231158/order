<?php

namespace App\RepositoryV2;

use App\Models\Order;

class OrderRepositoryV2
{
    public function SaveOrder($OrderInfo)
    {
        return Order::create($OrderInfo)['id'];
    }
    public function FindAndUpdateFailRecord($Oid)
    {
        return Order::find($Oid)->update(['status' => 0]);
    }
    public function FindAndUpdatesuccessRecord($Oid)
    {
        return Order::find($Oid)->update(['status' => 1]);
    }
}