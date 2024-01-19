<?php

namespace App\Services;

use App\Models\User as UserModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PDOException;
use Throwable;
use Exception;

class User
{
    public function get($email)
    {
        try {
            return UserModel::where('email', $email)->firstorfail()->toArray();
        } catch (ModelNotFoundException $e) {
            return [];
        } catch (PDOException $e) {
            return [];
        }
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
