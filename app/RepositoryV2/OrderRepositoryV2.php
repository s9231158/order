<?php

namespace App\RepositoryV2;

use App\Models\Order;
use Throwable;

class OrderRepositoryV2
{
    public function Create($OrderInfo)
    {
        try {
            throw new \Exception("RepossitoryErr:" . 500);
        } catch (Throwable $e) {
        }
        return Order::create($OrderInfo)['id'];
    }
    public function UpdateFailByOid($Oid)
    {
        try {
            return Order::find($Oid)->update(['status' => 10]);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function UpdateSuccessByOid($Oid)
    {
        try {
            return Order::find($Oid)->update(['status' => 0]);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function GetByIdAndOid($UserId, $Oid)
    {
        try {
            return Order::where('uid', '=', $UserId)->where('id', '=', $Oid)->get();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function GetByUidAndOffsetLimit($UserId, $Option)
    {
        try {
            return Order::where('uid', '=', $UserId)
                ->offset($Option['offset'])
                ->limit($Option['limit'])
                ->get();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function GetByUidAndOid($UserId, $Oid)
    {
        try {
            return Order::where('orders.uid', '=', $UserId)
                ->where('orders.id', '=', $Oid)
                ->join('order_infos', 'orders.id', '=', 'order_infos.oid')
                ->get();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function ExistByRidAndUserIdAtTime($UserId, $Rid, $Time)
    {
        try {
            return Order::where('uid', '=', $UserId)
                ->where('ordertime', '>', $Time)
                ->wherein('status', [0, 9])
                ->exists();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}