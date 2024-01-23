<?php

namespace App\Services;

use App\Models\Order as OrderModel;
use Exception;
use Throwable;

class Order
{
    public function update($oid, $info)
    {
        $goodInfo = array_filter([
            'ordertime' => $info['ordertime'] ?? null,
            'taketime' => $info['taketime'] ?? null,
            'total' => $info['total'] ?? null,
            'phone' => $info['phone'] ?? null,
            'address' => $info['address'] ?? null,
            'status' => $info['status'] ?? null,
            'rid' => $info['rid'] ?? null,
            'uid' => $info['uid'] ?? null,
        ]);
        return OrderModel::find($oid)->update($goodInfo);
    }

    public function create($info)
    {
        try {
            $goodInfo = [
                'ordertime' => $info['ordertime'],
                'taketime' => $info['taketime'],
                'total' => $info['total'],
                'phone' => $info['phone'],
                'address' => $info['address'],
                'status' => $info['status'],
                'rid' => $info['rid'],
                'uid' => $info['uid'],
            ];
            return OrderModel::create($goodInfo);
        } catch (Exception $e) {
            throw new Exception("order_service_err:" . 500 . $e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("order_service_err:" . 500 . $e->getMessage());
        }
    }

    public function get($oid)
    {
        try {
            $order = OrderModel::find($oid)->first();
            $response = $order ? $order->toArray() : $order;
            return $response;
        } catch (Exception $e) {
            throw new Exception("order_service_err:" . 500 . $e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("order_service_err:" . 500 . $e->getMessage());
        }
    }

    public function getList($where, $option = null)
    {
        $limit = $option['limit'] ?? 20;
        $offset = $option['offset'] ?? 0;
        $column = $option['column'] ?? '*';
        //select
        $stmt = OrderModel::select($column);
        //where
        if (count($where) % 3 != 0) {
            throw new Exception('where參數數量除三應餘為0,where參數正確示範[0]:uid,[1]:=[3]:2');
        }
        $chunks = array_chunk($where, 3);
        if (!empty($where)) {
            foreach ($chunks as $chunk) {
                $stmt->where($chunk[0], $chunk[1], $chunk[2]);
            }
        }
        $stmt->orderBy('created_at', 'desc');
        //reage
        $stmt->limit($limit);
        $stmt->offset($offset);
        return $stmt->get()->toArray();
    }
}
