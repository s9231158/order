<?php
namespace App\RepositoryV2;

use App\Models\Restaurant;
use Throwable;

class RestaurantRepositoryV2
{
    public function GetById($Rid)
    {
        try {
            return Restaurant::find($Rid);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function CheckRestaurantOpen(int $Rid, string $Date): bool
    {
        try {
            return Restaurant::join('restaurant_open_days', 'restaurant_open_days.id', '=', 'restaurants.id')
                ->where('restaurants.id', '=', $Rid)
                ->where('restaurant_open_days.' . $Date, '=', '1')
                ->exists();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function CheckRestaurantInDatabase(int $Rid): bool
    {
        try {
            return Restaurant::where('id', '=', $Rid)->exists();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function GetInRangeForDate(array $Option, $Date)
    {
        try {
            $Offset = $Option['offset'];
            $Limit = $Option['limit'];
            $RestaurantInfo = Restaurant::select(
                'restaurants.id',
                'restaurants.title',
                'restaurants.img',
                'restaurants.totalpoint',
                'restaurants.countpoint',
                'restaurants.enable'
            )
                ->join('restaurant_open_days', 'restaurant_open_days.id', '=', 'restaurants.id')
                ->where('restaurant_open_days.' . $Date, '=', '1')
                ->limit($Limit)
                ->offset($Offset)
                ->get();
            return $RestaurantInfo;
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function GetInfoByArray($RidArray, $Option)
    {
        try {
            return Restaurant::select('id', 'totalpoint', 'countpoint', 'title', 'img')
                ->wherein('id', $RidArray)
                ->limit($Option['limit'])
                ->offset($Option['offset'])
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}
