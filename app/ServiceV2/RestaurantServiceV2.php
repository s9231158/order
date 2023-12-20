<?php

namespace App\ServiceV2;

use App\Factorise;
use App\RepositoryV2\OrderRepositoryV2;
use App\RepositoryV2\ResraurantHistoryRepositoryV2;
use App\RepositoryV2\RestaurantCommentRepositoryV2;
use App\RepositoryV2\RestaurantRepositoryV2;
use App\RepositoryV2\UserRepositoryV2;
use Illuminate\Support\Carbon;

class RestaurantServiceV2
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
    public function GetRestaurantOnOffsetLimit($Option, $Today)
    {
        return $this->RestaurantRepositoryV2->GetInRangeForDate($Option, $Today)->where('enable', '=', '1')->shuffle()->map->only(['id', 'title', 'img', 'totalpoint', 'countpoint']);
    }
    public function CheckRestaurantInDatabase(int $Rid): bool
    {
        return $this->RestaurantRepositoryV2->CheckRestaurantInDatabase($Rid);
    }
    public function GetMenu($Rid, $OffsetLimit)
    {
        $this->Restaurant = $this->Factorise->Setmenu($Rid);
        return $this->Restaurant->Getmenu($OffsetLimit['offset'], $OffsetLimit['limit']);
    }
    public function GetRestaurantinfo($Rid)
    {
        return $this->RestaurantRepositoryV2->GetById($Rid)->only(['title', 'info', 'openday', 'opentime', 'closetime', 'img', 'address', 'totalpoint', 'countpoint']);
    }
    public function UpdateOrCreateHistory($Rid)
    {
        $UserInfo = $this->UserRepositoryV2->GetUserInfo();
        $UserId = $UserInfo->id;
        $this->ResraurantHistoryRepositoryV2->UpdateOrCreate($UserId, $Rid);
    }
    public function CheckOrderIn24Hour($Rid)
    {
        $UserInfo = $this->UserRepositoryV2->GetUserInfo();
        $UserId = $UserInfo->id;
        $Time = Carbon::yesterday();
        return $this->OrderRepositoryV2->ExistByRidAndUserId($UserId, $Rid, $Time);
    }
    public function CheckUserFirstComment($Rid)
    {
        $UserInfo = $this->UserRepositoryV2->GetUserInfo();
        $UserId = $UserInfo->id;
        return $this->RestaurantCommentRepositoryV2->ExistByUidAndRid($UserId, $Rid);
    }
    public function SaveComment($Comment)
    {
        $UserInfo = $this->UserRepositoryV2->GetUserInfo();
        $UserId = $UserInfo->id;
        $Comment['uid'] = $UserId;
        $this->RestaurantCommentRepositoryV2->Create($Comment);
    }
    public function GetRestaurantComment($Rid, $Option)
    {
        return $this->RestaurantCommentRepositoryV2->GetByRid($Rid, $Option);
    }
}
