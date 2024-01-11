<?php

namespace App\Services;

use App\Models\User_recode;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Exception;

class UserRecord
{
    private $info = [
        '0' => 0,
        '1' => 0,
        '2' => 0,
        '3' => 0,
        '4' => 0,
        '5' => 0,
        '6' => 0,
        '7' => 0,
        '8' => 0,
        '9' => 0,
        '10' => 0,
        '11' => 0,
        '12' => 0,
        '13' => 0,
        '14' => 0,
        '15' => 0,
        '16' => 0,
        '17' => 0,
        '18' => 0,
        '19' => 0,
        '20' => 0,
        '21' => 0,
        '22' => 0,
        '23' => 0,
        '24' => 0,
    ];
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
            $response = User_recode::create($goodInfo);
            //使用memcached來儲存
            $hour = intval(date('H', strtotime($info['login'])));
            $apple = Cache::get('login_record');
            $apple[$hour] += 1;
            Cache::put('login_record', $apple);
            return $response;
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
            //offset 
            if (isset($option['offset'])) {
                $stmt->offset($option['offset']);
            }
            return $stmt->get()->toArray();
        } catch (Throwable $e) {
            throw new Exception("user_record_service_err:" . 500);
        }
    }
}
