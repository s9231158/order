<?php

namespace App;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Date;
use App\Models\Oishii_menu;
use App\Contract\RestaurantInterface;
use Throwable;

class OSmenu implements RestaurantInterface
{
    private $GetMenuUrl = 'http://neil.xincity.xyz:9998/oishii/api/menu/all';
    private $OrderUrl = 'http://neil.xincity.xyz:9998/oishii/api/notify/order';
    private $GetMenuOnMenuIdUrl = 'http://neil.xincity.xyz:9998/oishii/api/menu/all?meal_id=';

    public function Getmenu(int $Offset, int $Limit): array
    //修改為從api取得
    {
        $Url = $this->GetMenuUrl . '?limit=' . $Limit . '&offset=' . $Offset;
        try {
            $Client = new Client();
            $Response = $Client->request('GET', $Url);
            $GoodResponse = $Response->getBody();
            $ArrayGoodResponse = json_decode($GoodResponse, true);
            $ApiMenu = $ArrayGoodResponse['menu'];
            $TargetData = [];
            foreach ($ApiMenu as $Item) {
                $Menu = [
                    'rid' => 1,
                    'id' => $Item['meal_id'],
                    'info' => $Item['meal_type'],
                    'name' => $Item['meal_name'],
                    'price' => $Item['price'],
                    'img' => ''
                ];
                $TargetData[] = $Menu;
            }
            return $TargetData;
        } catch (Throwable $e) {
            return $TargetData;
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
    public function SendApi(array $OrderInfo, array $Order): bool
    {
        try {
            $DateTime = Date::createFromFormat('Y-m-d H:i:s', $OrderInfo['taketime']);
            $Iso8601String = $DateTime->format('c');
            $TargetData = [
                'id' => $OrderInfo['uid'],
                'name' => $OrderInfo['name'],
                'phone_number' => '0' . $OrderInfo['phone'],
                'pickup_time' => $Iso8601String,
                'total_price' => $OrderInfo['total_price'],
                'orders' => [],
            ];
            //如果再Service先把description處理好 萬一某些餐廳部接收description是空值
            foreach ($Order as $Item) {
                if (isset($Item['description'])) {
                    $Orders = [
                        'meal_id' => $Item['id'],
                        'count' => $Item['quanlity'],
                        'memo' => $Item['description'],
                    ];
                } else {
                    $Orders = [
                        'meal_id' => $Item['id'],
                        'count' => $Item['quanlity'],
                    ];
                }
                $TargetData['orders'][] = $Orders;
            }
            //發送Api
            $Client = new Client();
            $Response = $Client->request('POST', $this->OrderUrl, ['json' => $TargetData]);
            $GoodResponse = $Response->getBody();
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
                //取得店家菜單
                $Client = new Client();
                $Response = $Client->request('GET', $this->GetMenuOnMenuIdUrl . $Item['id']);
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
}
