<?php

namespace App\Services;

use App\Models\Wallet_Record as WalletRecordModel;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PDOException;

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

    public function get($uuId)
    {
        try {
            return WalletRecordModel::findorfail($uuId)->toArray();
        } catch (ModelNotFoundException $e) {
            return [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getList(array $where, array $option)
    {
        try {
            $limit = $option['limit'] ?? 20;
            $offset = $option['offset'] ?? 0;
            $column = $option['column'] ?? '*';
            //select
            $stmt = WalletRecordModel::select($column);
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
            //orderBy
            $stmt->orderBy('created_at', 'desc');
            //range
            $stmt->limit($limit);
            $stmt->offset($offset);
            return $stmt->get()->toArray();
        } catch (PDOException $e) {
            return [];
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
