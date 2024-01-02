<?php

namespace App\ServiceV2;

use App\RepositoryV2\LoginRecord as LoginRecordRepositoryV2;
use App\RepositoryV2\ResraurantHistory as ResraurantHistoryRepositoryV2;
use App\RepositoryV2\Restaurant as RestaurantRepositoryV2;
use App\RepositoryV2\UserFavorite as UserFavoriteRepositoryV2;
use App\RepositoryV2\User as UserRepositoryV2;
use App\RepositoryV2\UserWallet as UserWalletRepositoryV2;
use App\TotalService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

class User
{
    private $UserRepositoryV2;
    private $UserWalletRepositoryV2;
    private $LoginRecordRepositoryV2;
    private $Key;
    private $TotalService;
    private $RestaurantRepositoryV2;
    private $UserFavoriteRepositoryV2;
    private $ResraurantHistoryRepositoryV2;
    public function __construct(
        ResraurantHistoryRepositoryV2 $ResraurantHistoryRepositoryV2,
        UserFavoriteRepositoryV2 $UserFavoriteRepositoryV2,
        RestaurantRepositoryV2 $RestaurantRepositoryV2,
        LoginRecordRepositoryV2 $LoginRecordRepositoryV2,
        TotalService $TotalService,
        UserWalletRepositoryV2 $UserWalletRepositoryV2,
        UserRepositoryV2 $UserRepositoryV2
    ) {
        $this->ResraurantHistoryRepositoryV2 = $ResraurantHistoryRepositoryV2;
        $this->UserFavoriteRepositoryV2 = $UserFavoriteRepositoryV2;
        $this->RestaurantRepositoryV2 = $RestaurantRepositoryV2;
        $this->LoginRecordRepositoryV2 = $LoginRecordRepositoryV2;
        $this->TotalService = $TotalService;
        $this->UserRepositoryV2 = $UserRepositoryV2;
        $this->UserWalletRepositoryV2 = $UserWalletRepositoryV2;
    }
    public function CreateUser($UserInfo)
    {
        try {
            $Password = Hash::make($UserInfo['password']);
            $UserInfo['password'] = $Password;
            $this->UserRepositoryV2->Create($UserInfo);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function CreatrWallet($Email)
    {
        try {
            $UserId = $this->UserRepositoryV2->GetInfoByEmil($Email);
            $this->UserWalletRepositoryV2->Create($UserId->id);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function LoginCheckTooManyAttempts($Ip, $Email)
    {
        try {
            $Key = $this->MakeKey($Ip, $Email);
            $this->Key = $Key;
            if (RateLimiter::tooManyAttempts($Key, 5)) {
                return true;
            }
            return false;
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function MakeKey($Email, $Ip)
    {
        try {
            return Str::lower($Email) . '|' . $Ip;
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }

    public function CheckHasLogin($Token, $Email)
    {
        try {
            return $this->TotalService->CheckHasLogin($Token, $Email);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function CheckUser($Account)
    {
        try {
            if (Auth::attempt($Account)) {
                return true;
            }
            RateLimiter::hit($this->Key);
            return false;
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }

    public function SaveLoginRecord($RocordInfo)
    {
        try {
            $UserId = $this->UserRepositoryV2->GetInfoByEmil($RocordInfo['email']);
            $RocordInfo['uid'] = $UserId->id;
            unset($RocordInfo['email']);
            $this->LoginRecordRepositoryV2->Create($RocordInfo);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function CreatToken($Email)
    {
        try {
            $UserInfo = $this->UserRepositoryV2->GetInfoByEmil($Email);
            $Id = $UserInfo->id;
            $Name = $UserInfo->name;
            $Email = $UserInfo->email;
            $Time = Carbon::now()->addDay();
            $UserClaims = [
                'id' => $Id,
                'name' => $Name,
                'email' => $Email,
                'exp' => $Time
            ];
            $Token = JWTAuth::claims($UserClaims)->fromUser($UserInfo);
            Cache::put($Email, $Token, 60 * 60 * 24);
            return $Token;
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function Logout()
    {
        try {
            $UserInfo = JWTAuth::parseToken()->authenticate();
            $Email = $UserInfo->email;
            if (Cache::has($Email)) {
                Cache::forget($Email);
                return true;
            }
            if (Cache::has($Email) === false) {
                return false;
            }
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function GetUserInfo()
    {
        try {
            return $this->TotalService->GetUserInfo()->only(['id', 'email', 'name', 'address', 'phone', 'age']);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function GetRecord($Option)
    {
        try {
            $UserInfo = $this->TotalService->GetUserInfo();
            $UserId = $UserInfo->id;
            return $this->LoginRecordRepositoryV2->GetById($UserId, $Option)->map->only(['ip', 'login', 'device']);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
        
    }
    public function CheckRestaurantInDatabase(int $Rid): bool
    {
        try {
            return $this->RestaurantRepositoryV2->CheckRestaurantInDatabase($Rid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function CheckFavoriteTooMuch()
    {
        try {
            $UserInfo = $this->TotalService->GetUserInfo();
            $UserId = $UserInfo->id;
            $UserFavorite = $this->UserFavoriteRepositoryV2->GetByUserId($UserId);
            $UserFavoriteCount = $UserFavorite->count();
            if ($UserFavoriteCount >= 20) {
                return true;
            }
            return false;
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function CreateFavorite($Rid)
    {
        try {
            $UserInfo = $this->TotalService->GetUserInfo();
            $UserId = $UserInfo->id;
            $Favorite = ['uid' => $UserId, 'rid' => $Rid];
            $this->UserFavoriteRepositoryV2->Create($Favorite);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function GetFavoriteInfo($OffsetLimit)
    {
        try {
            $UserInfo = $this->TotalService->GetUserInfo();
            $UserId = $UserInfo->id;
            $FavoriteRid = $this->UserFavoriteRepositoryV2->GetRidByUserIdOnRange($UserId, $OffsetLimit)->toArray();
            $ArrayRid = array_map(function ($Item) {
                return $Item['rid'];
            }, $FavoriteRid);
            $RestaurantInfo = $this->RestaurantRepositoryV2->GetInfoByArray($ArrayRid, $OffsetLimit);
            $RestaurantCount = $RestaurantInfo->count();
            return ['data' => $RestaurantInfo, 'count' => $RestaurantCount];
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function RidExistFavorite($Rid)
    {
        try {
            $UserInfo = $this->TotalService->GetUserInfo();
            $UserId = $UserInfo->id;
            return $this->UserFavoriteRepositoryV2->CheckRidExist($UserId, $Rid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function DeleteFavorite($Rid)
    {
        try {
            $UserInfo = $this->TotalService->GetUserInfo();
            $UserId = $UserInfo->id;
            $this->UserFavoriteRepositoryV2->Delete($UserId, $Rid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }

    public function GetFavoriteRestaurantInfo($Option)
    {
        try {
            $UserInfo = $this->TotalService->GetUserInfo();
            $UserId = $UserInfo->id;
            $RidArray = $this->ResraurantHistoryRepositoryV2->GetRidByUserIdOnOption($UserId, $Option)->pluck('rid')->toArray();
            $RestaurantInfo = $this->RestaurantRepositoryV2->GetInfoByArray($RidArray, $Option);
            $RestaurantInfoCount = $RestaurantInfo->count();
            return ['data' => $RestaurantInfo, 'count' => $RestaurantInfoCount];
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
}