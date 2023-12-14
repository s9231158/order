<?php

namespace App\RepositoryV2;

use App\Models\User_wallets;

class UserWalletRepositoryV2
{
    public function GetUserWallet($UserId)
    {
        return User_wallets::find($UserId);
    }
    public function UpdateUserWalletBalance($UserId, $Money)
    {
        $UserWallet = $this->GetUserWallet($UserId);
        $UserWallet->update(['balance' => $Money]);
    }
}