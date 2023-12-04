<?php
namespace App\UserRepository;

use App\Models\Restaurant;
use App\Models\User_favorite;
use App\TotalService;

include_once "/var/www/html/din-ban-doan/app/TotalService.php";

class FavoriteRepository
{
    private $TotalService;
    public function __construct(TotalService $TotalService)
    {
        $this->TotalService = $TotalService;
    }
    public function GetFavoriteCount()
    {
        try {

            $UserInfo = $this->TotalService->GetUserInfo();
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
        $UserInfo = $this->TotalService->GetUserInfo();
        $UserId = $UserInfo["id"];
        $UserFavorite = User_favorite::where("uid", "=", $UserId)->get();
        $SameRidInFavoriteCount = $UserFavorite->where('rid', '=', $Rid)->count();
        return $SameRidInFavoriteCount;
    }

    public function AddFavorite($Rid)
    {
        try {
            $UserInfo = $this->TotalService->GetUserInfo();
            $UserId = $UserInfo["id"];
            $CreateInfo = ['uid' => $UserId, 'rid' => $Rid];
            User_favorite::create($CreateInfo);
            return true;
        } catch (\Throwable $e) {
            return $e;
        }

    }

    public function GetUserFavoriteInfo($OffsetLimit)
    {
        try {
            $UserInfo = $this->TotalService->GetUserInfo();
            $limit = $OffsetLimit['limit'];
            $offset = $OffsetLimit['offset'];
            $UserFavoriteRestaurant = User_favorite::select('rid')->where('uid', '=', $UserInfo['id'])->limit($limit)->offset($offset)->get()->toArray();
            //使Array內只有rid[1,2,4]
            $rids = array_map(function ($item) {
                return $item['rid'];
            }, $UserFavoriteRestaurant);
            //抓出資料
            $UserFavoriteRestaurantInfo = Restaurant::select('id', 'totalpoint', 'countpoint', 'title', 'img')->wherein('id', $rids)->limit($limit)->offset($offset)->orderBy('created_at', 'desc')->get();
            $UserFavoriteRestaurantCount = $UserFavoriteRestaurantInfo->count();
            return response(['count' => $UserFavoriteRestaurantCount, 'data' => $UserFavoriteRestaurantInfo]);
        } catch (\Throwable $e) {
        }
    }


    public function UserFavoriteCount($Rid)
    {
        try {
            $Userinfo = $this->TotalService->GetUserInfo();
            $UserId = $Userinfo['id'];
            $UserFavorite = User_favorite::select('id')->where('uid', '=', $UserId)->where('rid', '=', $Rid)->get();
            $UserFavoriteCount = $UserFavorite->count();
            return $UserFavoriteCount;
        } catch (\Throwable $e) {
            return false;
        }

    }
    public function DeleteFavorite($Rid)
    {
        try {
            $Userinfo = $this->TotalService->GetUserInfo();
            $UserId = $Userinfo['id'];
            $UserFavorite = User_favorite::select('id')->where('uid', '=', $UserId)->where('rid', '=', $Rid)->delete();
            return true;
        } catch (\Throwable $e) {
            return false;
        }

    }


}



?>