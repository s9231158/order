<?php

namespace App\ServiceV2;

use App\Factorise;
use App\RepositoryV2\Order as OrderRepositoryV2;
use App\RepositoryV2\ResraurantHistory as ResraurantHistoryRepositoryV2;
use App\RepositoryV2\RestaurantComment as RestaurantCommentRepositoryV2;
use App\RepositoryV2\Restaurant as RestaurantRepositoryV2;
use App\RepositoryV2\User as UserRepositoryV2;
use Illuminate\Support\Carbon;
use Throwable;

class Restaurant
{
    private $Factorise;
    private $RestaurantRepositoryV2;
    private $Restaurant;
    private $UserRepositoryV2;
    private $ResraurantHistoryRepositoryV2;
    private $OrderRepositoryV2;
    private $RestaurantCommentRepositoryV2;
    public function __construct(
        RestaurantCommentRepositoryV2 $RestaurantCommentRepositoryV2,
        OrderRepositoryV2 $OrderRepositoryV2,
        UserRepositoryV2 $UserRepositoryV2,
        RestaurantRepositoryV2 $RestaurantRepositoryV2,
        Factorise $Factorise,
        ResraurantHistoryRepositoryV2 $ResraurantHistoryRepositoryV2,
    ) {
        $this->RestaurantCommentRepositoryV2 = $RestaurantCommentRepositoryV2;
        $this->OrderRepositoryV2 = $OrderRepositoryV2;
        $this->ResraurantHistoryRepositoryV2 = $ResraurantHistoryRepositoryV2;
        $this->UserRepositoryV2 = $UserRepositoryV2;
        $this->Factorise = $Factorise;
        $this->RestaurantRepositoryV2 = $RestaurantRepositoryV2;
    }
    public function GetOpenRestaurantsOnOffsetLimit($Option, $Today)
    {
        try {
            return $this->RestaurantRepositoryV2->GetInRangeForDate($Option, $Today)
                ->where('enable', '=', '1')
                ->shuffle()
                ->map
                ->only(['id', 'title', 'img', 'totalpoint', 'countpoint']);
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
    public function GetMenu($Rid, $OffsetLimit)
    {
        try {
            $this->Restaurant = $this->Factorise->Setmenu($Rid);
            return $this->Restaurant->Getmenu($OffsetLimit['offset'], $OffsetLimit['limit']);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function GetRestaurantinfo($Rid)
    {
        try {
            return $this->RestaurantRepositoryV2->GetById($Rid)
                ->only([
                    'title',
                    'info',
                    'openday',
                    'opentime',
                    'closetime',
                    'img',
                    'address',
                    'totalpoint',
                    'countpoint'
                ]);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function UpdateOrCreateHistory($Rid)
    {
        try {
            $UserInfo = $this->UserRepositoryV2->GetUserInfo();
            $UserId = $UserInfo->id;
            $this->ResraurantHistoryRepositoryV2->UpdateOrCreate($UserId, $Rid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function CheckOrderIn24Hour($Rid)
    {
        try {
            $UserInfo = $this->UserRepositoryV2->GetUserInfo();
            $UserId = $UserInfo->id;
            $Time = Carbon::yesterday();
            return $this->OrderRepositoryV2->ExistByRidAndUserIdAtTime($UserId, $Rid, $Time);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function CheckUserFirstComment($Rid)
    {
        try {
            $UserInfo = $this->UserRepositoryV2->GetUserInfo();
            $UserId = $UserInfo->id;
            return $this->RestaurantCommentRepositoryV2->ExistByUidAndRid($UserId, $Rid);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function SaveComment($Comment)
    {
        try {
            $UserInfo = $this->UserRepositoryV2->GetUserInfo();
            $UserId = $UserInfo->id;
            $Comment['uid'] = $UserId;
            $this->RestaurantCommentRepositoryV2->Create($Comment);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function GetRestaurantComment($Rid, $Option)
    {
        try {
            return $this->RestaurantCommentRepositoryV2->GetByRid($Rid, $Option);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
}
