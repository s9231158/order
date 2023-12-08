<?php
namespace App\UserRepository;

use App\Models\User;
use App\Models\User_wallets;
use Throwable;


class CreateRepository
{
    /**
     * 註冊資訊以Array方式傳入
     * @param array $UserInfo
     */
    public function CreateUser(array $UserInfo)
    {
        try {
            User::create($UserInfo);
            return true;
        } catch (Throwable $e) {
            return null;
        }
    }
    public function CreatrWallet($Email)
    {
        try {
            $UserId = User::select('id')->where("email", '=', $Email)->get();
            User_wallets::create(['id' => $UserId[0]['id'], 'balance' => 0]);
            return true;
        } catch (Throwable $e) {
            return null;
        }

    }


}







?>