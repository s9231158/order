<?php

namespace App;

use App\Contract\RestaurantInterface;
use App\Models\LocalMenu as LocalMenuModel;
use Throwable;

class Localmenu implements RestaurantInterface
{
    public function getMenu(int $offset, int $limit): array
    {
        try {
            return LocalMenuModel::select('rid', 'id', 'info', 'name', 'price', 'img')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();
        } catch (Throwable $e) {
            return ['取得菜單錯誤:500'];
        }
    }

    public function menuEnable(array $menuIds): bool
    {
        $menu = LocalMenuModel::wherein('id', $menuIds)->get();
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
                $menu = LocalMenuModel::where('id', '=', $item['id'])->get();
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
