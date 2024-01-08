<?php

namespace App\Services;

use App\Models\Order as OrderModel;
use Exception;
use Throwable;

class Order
{
    public function create($orderInfo)
    {
        try {
            $needColumn = [
                'ordertime',
                'taketime',
                'total',
                'phone',
                'status',
                'address',
                'rid',
                'uid',
            ];
            foreach ($needColumn as $column) {
                if ($column === 'status') {
                    if (!isset($orderInfo['status'])) {
                        throw new Exception('資料缺失');
                    }
                } elseif (!isset($orderInfo[$column]) || empty($orderInfo[$column])) {
                    throw new Exception('資料缺失');
                }
            }
            $goodInfo = [
                'ordertime' => $orderInfo['ordertime'],
                'taketime' => $orderInfo['taketime'],
                'total' => $orderInfo['total'],
                'phone' => $orderInfo['phone'],
                'address' => $orderInfo['address'],
                'status' => $orderInfo['status'],
                'rid' => $orderInfo['rid'],
                'uid' => $orderInfo['uid'],
            ];
            return OrderModel::create($goodInfo);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("order_service_err:" . 500 . $e);
        }
    }
    public function get($where, $option)
    {
        //select
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = OrderModel::select($option['column']);
        } else {
            $stmt = OrderModel::select('*');
        }
        //where
        $chunks = array_chunk($where, 3);
        if (!empty($where)) {
            foreach ($chunks as $chunk) {
                $stmt->where($chunk[0], $chunk[1], $chunk[2]);
            }
        }
        //orderBy
        if (isset($option['orderby'])) {
            $stmt->orderby($option['orderby'][0], $option['orderby'][1]);
        }
        //limit
        if (isset($option['limit'])) {
            $stmt->limit($option['limit']);
        }
        if (isset($option['offset'])) {
            $stmt->offset($option['offset']);
        }
        //get
        if (isset($option['get'])) {
            return $stmt->get()->toArray();
        } else {
            $response = $stmt->first();
            if (!$response) {
                return [];
            } else {
                return $response->toArray();
            }
        }
    }
}