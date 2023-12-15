<?php

namespace App;

use App\Contract\RestaurantInterface;
use App\Models\Tasty_menu;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Date;
use Throwable;

class TAmenu implements RestaurantInterface
{
    public function Getmenu(int $Offset, int $Limit): array
    //修改為從api取得
    {
        $Url = 'http://neil.xincity.xyz:9998/tasty/api/menu' . '?limit=' . $Limit . '&offset=' . $Offset;
        try {
            $Client = new Client();
            $Response = $Client->request('GET', $Url);
            $GoodResponse = $Response->getBody();
            $ArrayGoodResponse = json_decode($GoodResponse, true);
            $ApiMenu = $ArrayGoodResponse['data']['list'];
            $TargetData = [];
            foreach ($ApiMenu as $Item) {
                if ($Item['enable'] != '0') {
                    $Menu = [
                        'rid' => 2,
                        'id' => $Item['id'],
                        'info' => '',
                        'name' => $Item['name'],
                        'price' => $Item['price'],
                        'img' => ''
                    ];
                    $TargetData[] = $Menu;
                } else {
                    return $TargetData;
                }
            }
            return $TargetData;
        } catch (Throwable $e) {
            return $TargetData;
        }
    }
    public function Menuenable(array $MenuId): bool
    {
        $Menu = Tasty_menu::wherein('id', $MenuId)->get();
        $OrderCount = count($MenuId);
        $NotEnableCount = $Menu->where('enable', '=', 1)->count();
        if ($OrderCount !== $NotEnableCount) {
            return false;
        }
        return true;
    }
    public function SendApi(array $OrderInfo, array $Order): bool
    {
        try {
            $DateTime = Date::createFromFormat('Y-m-d H:i:s', $OrderInfo['taketime']);
            $Iso8601String = $DateTime->format('c');
            $TargetData = [
                'order_id' => $OrderInfo['uid'],
                'name' => $OrderInfo['name'],
                'phone_number' => '0' . $OrderInfo['phone'],
                'pickup_time' => "2016-06-01T14:41:36+08:00",
                'total_price' => $OrderInfo['totalprice'],
                'order' => ['list' => []],
            ];
            foreach ($Order as $Item) {
                if (isset($Item['description'])) {
                    $List = [
                        'id' => $Item['id'],
                        'count' => $Item['quanlity'],
                        'description' => $Item['description'],
                    ];
                } else {
                    $List = [
                        'id' => $Item['id'],
                        'count' => $Item['quanlity'],
                    ];
                }
                $TargetData['order']['list'][] = $List;
            }
            //發送Api
            $Client = new Client();
            $Response = $Client->request('POST', 'http://neil.xincity.xyz:9998/oishii/api/notify/order', ['json' => $TargetData]);
            $GoodResponse = $Response->getBody();
            $TargetData;
            $ArrayGoodResponse = json_decode($GoodResponse);
            //取得結果
            if ($ArrayGoodResponse->error_code === 0) {
                return true;
            }
            return false;
        } catch (Throwable $e) {
            return false;
        }
    }
    public function Menucorrect(array $Order): bool
    {
        try {
            foreach ($Order as $Item) {
                $Client = new Client();
                $Response = $Client->request('GET', 'http://neil.xincity.xyz:9998/tasty/api/menu?id=' . $Item['id']);
                $GoodResponse = $Response->getBody();
                $ArrayResponse = json_decode($GoodResponse, true);
                if ($ArrayResponse['data']['list'] === []) {
                    return false;
                }
                //取出Order內價格.名稱,餐點Id
                $OrderName = $Item['name'];
                $OrderPrice = $Item['price'];
                $OrderId = $Item['id'];
                //取出店家回傳菜單價格.名稱,餐點Id
                $ResponseName = $Item['data']['list'][0]['name'];
                $ResponseId = $Item['data']['list'][0]['id'];
                $ResponsePrice = $Item['data']['list'][0]['price'];
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
            return true;
        }
    }
}
