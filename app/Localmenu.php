<?php

namespace App;

use App\Contract\RestaurantInterface;
use App\Models\Local_menu;
use App\Models\Restaurant;
use App\Models\Tasty_menu;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Throwable;

class Localmenu implements RestaurantInterface
{
    public function Getmenu($offset, $limit)
    {
        try {
            $menu = Local_menu::select('rid', 'id', 'info', 'name', 'price', 'img')->limit($limit)->offset($offset)->get();
            return $menu;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
        }
    }
    public function Menuenable($order) //修改改為傳入id陣列
    {
        $Menu = Local_menu::wherein('id', $order)->get();
        $OrderCount = count($order);
        $NotEnableCount = $Menu->where('enable', '=', 1)->count();
        if ($OrderCount !== $NotEnableCount) {
            return false;
        }
        return true;
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
            $realmenu += Tasty_menu::where('id', '=', $v['id'])->count();
        }
        return response([$realmenu, $ordermenu]);
    }


    public function SendApi($OrderInfo, $Order)
    {
    
    }



    public function HasRestraunt($rid)
    {
        $hasRestraunt = Restaurant::where('id', '=', $rid)->count();
        if ($hasRestraunt != 1) {
            return false;
        }
    }
    public function Menucorrect($order)
    {
        try {
            foreach ($order as $a) {
                $menu = Local_menu::where('id', '=', $a['id'])->get();
                $ordername = $a['name'];
                $orderprice = $a['price'];
                $orderid = $a['id'];
                $realname = $menu[0]['name'];
                $realid = $menu[0]['id'];
                $realprice = $menu[0]['price'];
                if ($ordername != $realname) {
                    return false;
                }
                if ($orderprice != $realprice) {
                    return false;
                }
                if ($orderid != $realid) {
                    return false;
                }
            }
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

}
