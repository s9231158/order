<?php

namespace App\Services;

use App\Models\Order as OrderModel;
use Exception;
use Illuminate\Support\Facades\Cache;
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
        $response = OrderModel::find($oid)->update($goodInfo);
        $hour = intval(date('H', strtotime($info['ordertime'])));
        $orderTotal = Cache::get('order_total');
        $orderTotal[$hour]['order'] += 1;
        if ($info['status'] < 10) {
            $orderTotal[$hour]['success'] += 1;
        }
        $orderTotal[$hour]['fail'] += 1;
        Cache::put('order_total', $orderTotal);
        return $response;
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
            $response = OrderModel::create($goodInfo);
            $hour = intval(date('H', strtotime($info['ordertime'])));
            //cache付款方式記錄
            $paymentTotal = Cache::get('payment_total');
            if ($info['payment'] == 'local') {
                $paymentTotal[$hour]['local'] += 1;
            }
            if ($info['payment'] == 'ecpay') {
                $paymentTotal[$hour]['ecpay'] += 1;
            }
            Cache::put('payment_total', $paymentTotal);
            //cache訂單紀錄
            $orderTotal = Cache::get('order_total');
            $orderTotal[$hour]['order'] += 1;
            if ($info['status'] >= 10 && $info['status'] < 20) {
                $orderTotal[$hour]['fail'] += 1;
            }
            Cache::put('order_total', $orderTotal);
            return $response;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("order_service_err:" . 500 . $e);
        }
    }

    public function get($where, $option)
    {
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = OrderModel::select($option['column']);
        } else {
            $stmt = OrderModel::select('*');
        }
        //where
        $chunks = array_chunk($where, 2);
        if (!empty($where)) {
            foreach ($chunks as $chunk) {
                $stmt->where($chunk[0], $chunk[1]);
            }
        }
        $response = $stmt->first();
        if (!$response) {
            return $response;
        }
        return $response->toArray();
    }

    public function getList($where, $option)
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
        return $stmt->get()->toArray();
    }
}
