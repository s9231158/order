<?php

namespace App;

use App\Contract\RestaurantInterface;
use App\Models\LocalMenu as Local_menu;
use Illuminate\Support\Facades\Redis;
use Throwable;

class Localmenu implements RestaurantInterface
{
    public function getMenu(int $offset, int $limit): array
    {
        try {
            /* 需要尋找的keys */
            $keys = range($offset + 1, $limit + $offset);
            /* redis內已有的所有keys */
            $redisKeys = Redis::hkeys('4menus');
            /* 扣除redis內已有的keys 還需要的keys */
            $needKeys = array_values(array_diff($keys, $redisKeys));
            /* 需要尋找的keys 但redis內已有的keys */
            $redisKeys = array_values(array_intersect($redisKeys, $keys));
            /* 如果還需要到database找資料 */
            $need = false;
            $response = [];
            if (empty($needKeys)) {
                foreach (Redis::hmget('4menus', $keys) as $item) {
                    $response[] = json_decode($item, true);
                }
                return $response;
            }
            if (!empty($needKeys)) {
                $need = true;
            }
            if (!empty($redisKeys)) {
                foreach (Redis::hmget('4menus', $redisKeys) as $item) {
                    $response[] = json_decode($item, true);
                }
            }
            if ($need) {
                $menu = Local_menu::select('rid', 'id', 'info', 'name', 'price', 'img')
                    ->limit($limit)
                    ->offset($offset)
                    ->get()
                    ->toArray();
                foreach ($menu as $item) {
                    Redis::hset('4menus', $item['id'], json_encode($item));
                    $response[] = $item;
                }
            }
            return $response;
        } catch (Throwable $e) {
            return ['取得菜單錯誤:500'];
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
