<?php
namespace App\Service;

use App\Models\Restaurant;

class RestaurantService
{
    public function GetRestaurantInfo(array $Rid)
    {
        $RestaurantInfo = Restaurant::wherein("id", $Rid)->get();
        return $RestaurantInfo;
    }
    public function GetRestaurantInfoOffsetLimit(array $Option, $Date)
    {
        $Offset = $Option['offset'];
        $Limit = $Option['limit'];
        $RestaurantInfo = Restaurant::select('restaurants.id', 'restaurants.title', 'restaurants.img', 'restaurants.totalpoint', 'restaurants.countpoint', 'restaurants.enable')->join('restaurant_open_days', 'restaurant_open_days.id', '=', 'restaurants.id')->where('restaurant_open_days.' . $Date, '=', '1')->limit($Limit)->offset($Offset)->get();
        return $RestaurantInfo;
    }
    public function CheckRestaurantOpen($Rid, $Date)
    {
        return Restaurant::join('restaurant_open_days', 'restaurant_open_days.id', '=', 'restaurants.id')->where('restaurants.id', '=', $Rid)->where('restaurant_open_days.' . $Date, '=', '1')->exists();
    }
    public function CheckRestaurantInDatabase($rid)
    {
        return Restaurant::where('id', '=', $rid)->exists();
    }




}


?>