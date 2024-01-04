<?php

namespace App\Services;

use App\Models\User_recode;
use Throwable;
use Exception;

class UserRecord
{
    public function create($recordInfo)
    {
        try {
            $needColumn = ['uid', 'login', 'ip', 'device', 'email'];
            foreach ($needColumn as $colunm) {
                if (!isset($recordInfo[$colunm]) || empty($recordInfo[$colunm])) {
                    throw new Exception('資料缺失');
                }
            }
            $goodInfo = [
                'uid' => $recordInfo['uid'],
                'login' => $recordInfo['login'],
                'ip' => $recordInfo['ip'],
                'device' => $recordInfo['device'],
                'email' => $recordInfo['email'],
            ];
            return User_recode::create($goodInfo);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_record_service_err:" . 500);
        }
    }
    public function getList($where, $option)
    {
        try {
            //select
            $stmt = null;
            if (isset($option['column'])) {
                $stmt = User_recode::select($option['column']);
            } else {
                $stmt = User_recode::select('*');
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
            return $stmt->get()->toArray();

        } catch (Throwable $e) {
            throw new Exception("user_record_service_err:" . 500);
        }
    }
}
