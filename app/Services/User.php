<?php

namespace App\Services;

use App\Models\User as UserModel;
use Throwable;
use Exception;

class User
{
    public function get($where, $option)
    {
        //select
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = UserModel::select($option['column']);
        } else {
            $stmt = UserModel::select('*');
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

    public function create($userInfo)
    {
        try {
            $needColumn = ['email', 'name', 'password', 'address', 'phone', 'age'];
            foreach ($needColumn as $colunm) {
                if (!isset($recordInfo[$colunm]) || empty($recordInfo[$colunm])) {
                    throw new Exception('資料缺失');
                }
            }
            $goodInfo = [
                'email' => $userInfo['email'],
                'name' => $userInfo['name'],
                'password' => $userInfo['password'],
                'address' => $userInfo['address'],
                'phone' => $userInfo['phone'],
                'age' => $userInfo['age'],
            ];
            return UserModel::create($goodInfo);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_record_service_err:" . 500);
        }
    }
}
