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
        return Order::find($Oid)->update(['status' => 10]);
    }
    public function FindAndUpdatesuccessRecord($Oid)
    {
        return Order::find($Oid)->update(['status' => 0]);
    }
    public function GetOrder($UserId, $Oid)
    {
        return Order::where('uid', '=', $UserId)->where('id', '=', $Oid)->get();
    }
    public function GetOrdersByOffsetLimit($UserId, $Option)
    {
        return Order::where('uid', '=', $UserId)->offset($Option['offset'])->limit($Option['limit'])->get();
    }
    public function GetOrderInfoJoinOrder($UserId, $Oid)
    {
        return Order::where('orders.uid', '=', $UserId)->where('orders.id', '=', $Oid)->join('order_infos', 'orders.id', '=', 'order_infos.oid')->get();
    }
}