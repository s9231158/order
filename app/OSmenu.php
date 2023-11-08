<?php

namespace App;

use Illuminate\Support\Str;

use App\Models\Oishii_menu;

use App\Contract\OSmenu as ContractOSmenu;
use App\Models\Restaurant;
use Faker\Core\Uuid;
use Symfony\Component\Uid\UuidV8;

use function PHPUnit\Framework\returnSelf;

class OSmenu implements ContractOSmenu
{
    public function Getmenu($offset, $limit)
    {
        $menu = Oishii_menu::select('rid', 'id', 'info', 'price', 'img')->where('rid', '=', 1)->get();
        return $menu;
    }
    public function Menuenable($order)
    {
        $menu  = 0;
        foreach ($order as $v) {
            $menu += Oishii_menu::where('id', '=', $v['id'])->where('enable', '=', 0)->count();
        }
        return $menu;
    }
    public function Restrauntenable($rid)
    {
        $renable = Restaurant::where('id', '=', $rid)->where('enable', '=', 0)->count();
        return $renable;
    }
    public function Hasmenu($order)
    {
        $realmenu = 0;
        $ordermenu = 0;
        foreach ($order as $v) {
            $ordermenu += 1;
            $realmenu += Oishii_menu::where('id', '=', $v['id'])->count();
        }
        return response([$realmenu, $ordermenu]);
    }


    public function Change($order,$order2)
    {
        $uid = (string)Str::uuid();

        $targetData = [
            'id' => $uid,
            'name' => $order->name,
            'phone_number' => (string) $order->phone,
            'pickup_time' => now()->toIso8601String(),
            'total_price' => $order->totalprice,
            'orders' => [],
        ];

        foreach ($order2 as $a) {
            $orders = [
                'meal_id' => $a['id'],
                'count' => $a['quanlity'],
                'memo' => $a['description'],
            ];
            $targetData['orders'][] = $orders;
            // return $orders;
        }
        return $targetData;
    }
}
