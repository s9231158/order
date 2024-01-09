<?php

namespace App\Services;

use App\Models\Wallet_Record;

class WalletRecord
{
    public function update($oid, $info)
    {
        $goodIndo = array_filter([
            'oid' => $info['oid'] ?? null,
            'out' => $info['out'] ?? null,
            'eid' => $info['eid'] ?? null,
            'status' => $info['status'] ?? null,
            'pid' => $info['pid'] ?? null,
            'uid' => $info['uid'] ?? null
        ]);
        return Wallet_Record::where('oid', $oid)->update($goodIndo);
    }
    public function create($info)
    {
        $goodInfo = [
            'oid' => $info['oid'],
            'out' => $info['out'],
            'eid' => $info['eid'] ?? null,
            'status' => $info['status'],
            'pid' => $info['pid'],
            'uid' => $info['uid'],
        ];
        return Wallet_Record::create($goodInfo);
    }

    public function get($where, $option)
    {
        //select
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = Wallet_Record::select($option['column']);
        } else {
            $stmt = Wallet_Record::select('*');
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
}
