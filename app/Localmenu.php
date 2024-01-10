<?php

namespace App;

use App\Contract\RestaurantInterface;
use App\Models\LocalMenu as Local_menu;
use Throwable;
use Illuminate\Support\Facades\Cache;

class Localmenu implements RestaurantInterface
{
    public function getMenu(int $offset, int $limit): array
    {
        try {
            if (Cache::get('Menu_4')) {
                return Cache::get('Menu_4');
            }
            $menu = Local_menu::select('rid', 'id', 'info', 'name', 'price', 'img')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();
            Cache::put('Menu_4', $menu);
            return $menu;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return $menu;
        }
    }
    public function menuEnable(array $menuIds): bool
    {
        $menu = Local_menu::wherein('id', $menuIds)->get();
        $orderCount = count($menuIds);
        $notEnableCount = $menu->where('enable', '=', 1)->count();
        if ($orderCount !== $notEnableCount) {
            return false;
        }
        return true;
    }
    public function sendApi(array $orderInfo, array $order): bool
    {
        return true;
    }
    public function menuCorrect(array $order): bool
    {
        try {
            foreach ($order as $item) {
                $menu = Local_menu::where('id', '=', $item['id'])->get();
                $orderName = $item['name'];
                $orderPrice = $item['price'];
                $orderId = $item['id'];
                $responseName = $menu[0]['name'];
                $responseId = $menu[0]['id'];
                $responsePrice = $menu[0]['price'];
                if ($orderName != $responseName) {
                    return false;
                }
                if ($orderPrice != $responsePrice) {
                    return false;
                }
                if ($orderId != $responseId) {
                    return false;
                }
            }
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
