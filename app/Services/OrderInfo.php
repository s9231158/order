<?php

namespace App\Services;

use App\Models\OrderInfo as OrderInfoModel;
use Exception;

class OrderInfo
{
    public function create($orderInfo)
    {
        try {
            $needColumn = [
                'oid',
                'name',
                'price',
                'quanlity',
                'created_at',
                'updated_at'
            ];
            $goodInfo = [];
            foreach ($orderInfo as $item) {
                foreach ($needColumn as $column) {
                    if (!isset($item[$column]) || empty($item[$column])) {
                        throw new Exception('資料缺失');
                    }
                }
                $info = [
                    'description' => $item['description'],
                    'oid' => $item['oid'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quanlity' => $item['quanlity'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                ];
                $goodInfo[] = $info;
            }
            return OrderInfoModel::insert($goodInfo);
        } catch (Exception $e) {
            throw new Exception("order_info_service_err:" . 500 . $e);
        }

    }
}