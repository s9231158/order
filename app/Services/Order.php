<?php

namespace App\Services;

use App\Models\Order as OrderModel;

class Order
{
    public function getLastObjByUser($userId)
    {
        return OrderModel::where('uid', '=', $userId)->orderBy('created_at', 'desc')->first();
    }
}