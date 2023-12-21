<?php
namespace App\RepositoryV2;

use App\Models\Restaurant;

class RestaurantRepositoryV2
{
    public function GetById($Rid)
    {
        return Restaurant::find($Rid);
    }
    // public function GetRestaurantInfoOffsetLimit(array $Option, $Date)
    // {
    //     $Offset = $Option['offset'];
    //     $Limit = $Option['limit'];
    //     $RestaurantInfo = Restaurant::select('restaurants.id', 'restaurants.title', 'restaurants.img', 'restaurants.totalpoint', 'restaurants.countpoint', 'restaurants.enable')->join('restaurant_open_days', 'restaurant_open_days.id', '=', 'restaurants.id')->where('restaurant_open_days.' . $Date, '=', '1')->limit($Limit)->offset($Offset)->get();
    //     return $RestaurantInfo;
    // }
    public function CheckRestaurantOpen(int $Rid, string $Date): bool
    {
        return Restaurant::join('restaurant_open_days', 'restaurant_open_days.id', '=', 'restaurants.id')->where('restaurants.id', '=', $Rid)->where('restaurant_open_days.' . $Date, '=', '1')->exists();
    }
    public function CheckRestaurantInDatabase(int $Rid): bool
    {
        return Restaurant::where('id', '=', $Rid)->exists();
    }
    public function GetInRangeForDate(array $Option, $Date)
    {
        $Offset = $Option['offset'];
        $Limit = $Option['limit'];
        $RestaurantInfo = Restaurant::select('restaurants.id', 'restaurants.title', 'restaurants.img', 'restaurants.totalpoint', 'restaurants.countpoint', 'restaurants.enable')->join('restaurant_open_days', 'restaurant_open_days.id', '=', 'restaurants.id')->where('restaurant_open_days.' . $Date, '=', '1')->limit($Limit)->offset($Offset)->get();
        return $RestaurantInfo;
    }
    public function GetInfoByArray($RidArray, $Option)
    {
        return Restaurant::select('id', 'totalpoint', 'countpoint', 'title', 'img')->wherein('id', $RidArray)->limit($Option['limit'])->offset($Option['limit'])->orderBy('created_at', 'desc')->get();
    }

}