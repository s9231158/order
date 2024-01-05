<?php

namespace App;

use App\Contract\RestaurantInterface;
use App\Models\LocalMenu as Local_menu;
use Throwable;
use Illuminate\Support\Facades\Cache;

class Localmenu implements RestaurantInterface
{
    public function GetMenu(int $Offset, int $Limit): array
    {
        try {
            if (Cache::get('Menu_4')) {
                return Cache::get('Menu_4');
            }
            $Menu = Local_menu::select('rid', 'id', 'info', 'name', 'price', 'img')
                ->limit($Limit)
                ->offset($Offset)
                ->get()
                ->toArray();
            Cache::put('Menu_4', $Menu);
            return $Menu;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return $Menu;
        }
    }
    public function MenuEnable(array $MenuId): bool
    {
        $Menu = Local_menu::wherein('id', $MenuId)->get();
        $OrderCount = count($MenuId);
        $NotEnableCount = $Menu->where('enable', '=', 1)->count();
        if ($OrderCount !== $NotEnableCount) {
            return false;
        }
        return true;
    }
    public function SendApi(array $OrderInfo, array $Order): bool
    {
        return true;
    }
    public function MenuCorrect(array $Order): bool
    {
        try {
            foreach ($Order as $Item) {
                $Menu = Local_menu::where('id', '=', $Item['id'])->get();
                $OrderName = $Item['name'];
                $OrderPrice = $Item['price'];
                $OrderId = $Item['id'];
                $ResponseName = $Menu[0]['name'];
                $ResponseId = $Menu[0]['id'];
                $ResponsePrice = $Menu[0]['price'];
                if ($OrderName != $ResponseName) {
                    return false;
                }
                if ($OrderPrice != $ResponsePrice) {
                    return false;
                }
                if ($OrderId != $ResponseId) {
                    return false;
                }
            }
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
