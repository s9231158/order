<?php

namespace App\Services;

use App\Models\User as UserModel;
use Throwable;
use Exception;

class User
{
    public function getList($where)
    {
        try {
            //where
            if (count($where) % 3 != 0) {
                throw new Exception('where參數數量除三應餘為0,where參數正確示範[0]:uid,[1]:=[3]:2');
            }
            $chunks = array_chunk($where, 3);
            if (!empty($where)) {
                foreach ($chunks as $chunk) {
                    $response = UserModel::where($chunk[0], $chunk[1], $chunk[2])->get();
                }
            }
            return $response ? $response->toArray() : $response;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_service_err:" . 500);
        }
    }

    public function create($info)
    {
        try {
            $goodInfo = [
                'email' => $info['email'],
                'name' => $info['name'],
                'password' => $info['password'],
                'address' => $info['address'],
                'phone' => $info['phone'],
                'age' => $info['age'],
            ];
            return UserModel::create($goodInfo);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_service_err:" . 500);
        }
    }
}
