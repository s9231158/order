<?php

namespace App\RepositoryV2;

use App\Models\User_wallets;
use Cache;

class UserWalletRepositoryV2
{
    public function GetUserWallet($UserId)
    {
        return User_wallets::find($UserId);
    }
    public function UpdateUserWalletBalance($UserId, $Money)
    {
        $UserWallet = $this->GetUserWallet($UserId);
        $UserWallet[0]->update(['balance' => $Money]);
    }

}