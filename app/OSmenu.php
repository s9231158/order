<?php

namespace App;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use App\Models\Oishii_menu;
use App\Contract\RestaurantInterface;
use App\Models\Restaurant;
use GuzzleHttp\Client;
use Throwable;

class OSmenu implements RestaurantInterface
{
    public function Getmenu($offset, $limit)
    //修改為從api取得
    {
        $a = 'http://neil.xincity.xyz:9998/oishii/api/menu/all' . '?limit=' . $limit . '&offset=' . $offset;
        try {
            $client = new Client();
            $res = $client->request('GET', $a);
            $goodres = $res->getBody();
            $s = json_decode($goodres, true);
            $ss = $s['menu'];
            $targetData = [];
            foreach ($ss as $a) {
                $menu = [
                    'rid' => 1,
                    'id' => $a['meal_id'],
                    'info' => $a['meal_type'],
                    'name' => $a['meal_name'],
                    'price' => $a['price'],
                    'img' => ''
                ];
                $targetData[] = $menu;
            }
            return $targetData;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
        } catch (Throwable $e) {
            return $e;
        }
    }
    public function Menuenable(array $MenuId): bool
    {
        $Menu = Oishii_menu::wherein('id', $MenuId)->get();
        $OrderCount = count($MenuId);
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
            $realmenu += Oishii_menu::where('id', '=', $v['id'])->count();
        }
        return response([$realmenu, $ordermenu]);
    }


    // public function Change($Request, $order2)
    // {
    //     try {
    //         $uid2 = (string) Str::uuid();
    //         $targetData = [
    //             'id' => $uid2,
    //             'name' => $Request->name,
    //             'phone_number' => '0' . (string) $Request->phone,
    //             'pickup_time' => '2016-06-01T14:41:36+08:00',
    //             'total_price' => $Request->totalprice,
    //             'orders' => [],
    //         ];

    //         foreach ($order2 as $a) {
    //             if (isset($a['description'])) {
    //                 $orders = [
    //                     'meal_id' => $a['id'],
    //                     'count' => $a['quanlity'],
    //                     'memo' => $a['description'],
    //                 ];
    //             } else {
    //                 $orders = [
    //                     'meal_id' => $a['id'],
    //                     'count' => $a['quanlity'],
    //                 ];
    //             }
    //             $targetData['orders'][] = $orders;
    //         }
    //         return $targetData;
    //     } catch (Throwable $e) {
    //         return false;
    //     }

    // }
    public function Change($OrderInfo, $Order)
    {
        try {
            $dateTime = Date::createFromFormat('Y-m-d H:i:s', $OrderInfo['taketime']);
            $iso8601String = $dateTime->format('c');
            $targetData = [
                'id' => $OrderInfo['uid'],
                'name' => $OrderInfo['name'],
                'phone_number' => '0' . $OrderInfo['phone'],
                'pickup_time' => $iso8601String,
                'total_price' => $OrderInfo['totalprice'],
                'orders' => [],
            ];
            //如果再Service先把description處理好 萬一某些餐廳部接收description是空值
            foreach ($Order as $a) {
                if (isset($a['description'])) {
                    $orders = [
                        'meal_id' => $a['id'],
                        'count' => $a['quanlity'],
                        'memo' => $a['description'],
                    ];
                } else {
                    $orders = [
                        'meal_id' => $a['id'],
                        'count' => $a['quanlity'],
                    ];
                }
                $targetData['orders'][] = $orders;
            }
            //發送Api
            $Client = new Client();
            $Response = $Client->request('POST', 'http://neil.xincity.xyz:9998/oishii/api/notify/order', ['json' => $targetData]);
            $GoodResponse = $Response->getBody();
            $ArrayGoodResponse = json_decode($GoodResponse);
            //取得結果
            if ($ArrayGoodResponse->error_code === 0) {
                return true;
            }
            return false;
        } catch (Throwable $e) {
            return $e;
        }

    }

    public function Sendapi($order)
    {
        // $client = new Client();
        // $res = $client->request('POST', 'http://neil.xincity.xyz:9998/oishii/api/notify/order', ['json' => $order]);
        // $goodres = $res->getBody();
        // $s = json_decode($goodres);
        // return $s;
    }

    public function HasRestraunt($rid)
    {
        $hasRestraunt = Restaurant::where('id', '=', $rid)->count();
        return $hasRestraunt;
        if ($hasRestraunt != 1) {
            return false;
        }
    }

    public function Menucorrect(array $Order): bool
    {
        try {
            foreach ($Order as $Item) {
                //取得店家菜單
                $Client = new Client();
                $Response = $Client->request('GET', 'http://neil.xincity.xyz:9998/oishii/api/menu/all?meal_id=' . $Item['id']);
                $GoodResponse = $Response->getBody();
                $ArrayResponse = json_decode($GoodResponse, true);
                //找不到此id菜單就是錯誤
                if ($ArrayResponse['menu'] === []) {
                    return false;
                }
                //取出Order內價格.名稱,餐點Id
                $OrderName = $Item['name'];
                $OrderPrice = $Item['price'];
                $OrderId = $Item['id'];
                //取出店家回傳菜單價格.名稱,餐點Id
                $ResponseName = $ArrayResponse['menu'][0]['meal_name'];
                $ResponseId = $ArrayResponse['menu'][0]['meal_id'];
                $ResponsePrice = $ArrayResponse['menu'][0]['price'];
                //比對是否不一致
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
    public function Geterr($callbcak)
    {
        // if ($callbcak->error_code == 0) {
        //     return true;
        // }
        // return false;
    }
}
