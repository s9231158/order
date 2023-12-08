<?php
namespace App;

use App\Models\User;
use App\Models\User_wallets;
use Throwable;


class CustomerService
{
    /**
     * Undocumented function
     *
     * @param array $UserInfo
     * @return [void]
     */
    public function CreateUser(array $UserInfo)
    {
        try {
            User::create($UserInfo);
        } catch (Throwable $e) {
            return $e;
        }
    }
    public function CreatrWallet($Email)
    {
        try {
            $UserId = User::select('id')->where("email", '=', $Email)->get();
            User_wallets::create(['id' => $UserId[0]['id'], 'balance' => 0]);
        } catch (Throwable $e) {
            return false;
        }

    }


}







?>