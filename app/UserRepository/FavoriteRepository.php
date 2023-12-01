<?php
namespace App\UserRepository;

use App\Models\User_favorite;
use App\TotalService;

include_once "/var/www/html/din-ban-doan/app/TotalService.php";

class FavoriteRepository
{
    public function GetFavoriteCount()
    {
        try {
            $UserInfo = TotalService::GetUserInfo();
            $UserId = $UserInfo["id"];
            $UserFavorite = User_favorite::where("uid", "=", $UserId)->get();
            $UserFavoriteCount = $UserFavorite->count();
            return $UserFavoriteCount;
        } catch (\Throwable $e) {
            return $e;
        }
    }
    public function CheckAlreadyAddFavorite($Rid)
    {
        $UserInfo = TotalService::GetUserInfo();
        $UserId = $UserInfo["id"];
        $UserFavorite = User_favorite::where("uid", "=", $UserId)->get();
        $SameRidInFavoriteCount = $UserFavorite->where('rid', '=', $Rid)->count();
        return $SameRidInFavoriteCount;
    }

    public function AddFavorite($Rid)
    {
        try {
            $UserInfo = TotalService::GetUserInfo();
            $UserId = $UserInfo["id"];
            $CreateInfo = ['uid' => $UserId, 'rid' => $Rid];
            User_favorite::create($CreateInfo);
            return true;
        } catch (\Throwable $e) {
            return $e;
        }

    }
}



?>