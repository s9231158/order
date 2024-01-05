<?php

namespace App\Services;

use App\Models\Order as OrderModel;

class Order
{
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