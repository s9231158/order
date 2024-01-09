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
        if (!empty($where)) {
            $response = $stmt->find($where);
        }
        if (!$response) {
            return $response;
        }
        return $response->toArray();
    }
    public function getPhone($phone)
    {
        return UserModel::where('phone', $phone)->exists();
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
            throw new Exception("user_record_service_err:" . 500);
        }
    }
}
