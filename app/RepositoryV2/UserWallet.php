<?php

namespace App\RepositoryV2;

use App\Models\User_wallets;
use Throwable;

class UserWallet
{
    public function GetOnId($UserId)
    {
        try {
            return User_wallets::where('id', '=', $UserId)->first();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function UpdateOnId($UserId, $Money)
    {
        try {
            User_wallets::where('id', $UserId)->update(['balance' => $Money]);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function Create($UserId)
    {
        try {
            User_wallets::create(['id' => $UserId, 'balance' => 0]);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}
