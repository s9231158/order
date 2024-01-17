<?php

namespace App\Services;

use App\Models\OrderInfo as OrderInfoModel;
use Exception;

class OrderInfo
{
    public function create($info)
    {
        try {
            $goodInfo = [];
            foreach ($info as $item) {
                $goodInfo[] = [
                    'description' => $item['description'],
                    'oid' => $item['oid'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quanlity' => $item['quanlity'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                ];
            }
            return OrderInfoModel::insert($goodInfo);
        } catch (Exception $e) {
            throw new Exception("order_info_service_err:" . 500 . $e);
        }
    }
    public function getList($where, $option)
    {
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = OrderInfoModel::select($option['column']);
        } else {
            $stmt = OrderInfoModel::select('*');
        }
        //where
        $chunks = array_chunk($where, 3);
        if (!empty($where)) {
            foreach ($chunks as $chunk) {
                $stmt->where($chunk[0], $chunk[1], $chunk[2]);
            }
        }
        $response = $stmt->get();
        return $response->toArray();
    }
}
