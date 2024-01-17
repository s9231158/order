<?php

namespace App\Services;

use App\Models\Wallet_Record as WalletRecordModel;

class WalletRecord
{
    public function update($where, $info)
    {
        $goodInfo = array_filter([
            'oid' => $info['oid'] ?? null,
            'out' => $info['out'] ?? null,
            'eid' => $info['eid'] ?? null,
            'status' => $info['status'] ?? null,
            'pid' => $info['pid'] ?? null,
            'uid' => $info['uid'] ?? null
        ]);
        return WalletRecordModel::where($where)->update($goodInfo);
    }
    public function create($info)
    {
        $goodInfo = [
            'oid' => $info['oid'] ?? null,
            'in' => $info['in'] ?? null,
            'out' => $info['out'] ?? null,
            'eid' => $info['eid'] ?? null,
            'status' => $info['status'],
            'pid' => $info['pid'],
            'uid' => $info['uid'],
        ];
        return WalletRecordModel::create($goodInfo);
    }

    public function get($where, $option)
    {
        //select
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = WalletRecordModel::select($option['column']);
        } else {
            $stmt = WalletRecordModel::select('*');
        }
        //where
        if (!empty($where)) {
            $response = $stmt->find($where);
        }
        if (!$response) {
            return $response;
        }
        return $response->toArray();
    }
    public function getList($where, $option)
    {
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = WalletRecordModel::select($option['column']);
        } else {
            $stmt = WalletRecordModel::select('*');
        }
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
        return $stmt->get()->toArray();
    }
}
