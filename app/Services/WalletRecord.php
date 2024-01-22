<?php

namespace App\Services;

use App\Models\Wallet_Record as WalletRecordModel;
use Exception;
use Throwable;

class WalletRecord
{
    public function update($where, $info)
    {
        try {
            $goodInfo = array_filter([
                'oid' => $info['oid'] ?? null,
                'out' => $info['out'] ?? null,
                'eid' => $info['eid'] ?? null,
                'status' => $info['status'] ?? null,
                'pid' => $info['pid'] ?? null,
                'uid' => $info['uid'] ?? null
            ]);
            return WalletRecordModel::where($where)->update($goodInfo);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("wallet_record_service_err:" . 500 . $e->getMessage());
        }
    }

    public function create($info)
    {
        try {
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
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("wallet_record_service_err:" . 500 . $e->getMessage());
        }
    }

    public function get($uuId)
    {
        try {
            return WalletRecordModel::find($uuId)->toArray();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("wallet_record_service_err:" . 500 . $e->getMessage());
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
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("wallet_record_service_err:" . 500 . $e->getMessage());
        }
    }
}