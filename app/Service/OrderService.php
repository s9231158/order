<?php
namespace App\Service;

use App\Models\Order;

class OrderService
{
    public function GetSomeTimeOrder($Start, $End)
    {
        $Order = Order::select('status', 'created_at')->whereBetween('created_at', [$Start, $End])->get();
        return $Order;
    }
    public function GetOrder($Uid)
    {
        return Order::where('uid', '=', $Uid)->get();
    }
    public function AddOrder($OrderInfo)
    {
        return Order::create($OrderInfo)['id'];
    }
}
