<?php

namespace App\Services;

use App\Models\User_recode as UserRecodeModel;
use PDOException;
use Throwable;
use Exception;

class UserRecord
{
    public function create($info)
    {
        try {
            $goodInfo = [
                'uid' => $info['uid'],
                'login' => $info['login'],
                'ip' => $info['ip'],
                'device' => $info['device'],
                'email' => $info['email'],
            ];
            return UserRecodeModel::create($goodInfo);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_record_service_err:" . 500);
        }
    }

    public function getList($where, $option)
    {
        try {
            $limit = $option['limit'] ?? 20;
            $offset = $option['offset'] ?? 0;
            $column = $option['column'] ?? '*';
            //select
            $stmt = UserRecodeModel::select($column);
            //where
            if (count($where) % 3 != 0) {
                throw new Exception('where參數應為三元組的倍數,where參數正確示範[0]:uid,[1]:=[3]:2');
            }
            $chunks = array_chunk($where, 3);
            if (!empty($where)) {
                foreach ($chunks as $chunk) {
                    $stmt->where($chunk[0], $chunk[1], $chunk[2]);
                }
            }
            //orderBy 
            $stmt->orderby('login', 'desc');
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
