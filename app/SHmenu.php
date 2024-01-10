<?php

namespace App;

use GuzzleHttp\Client;
use App\Contract\RestaurantInterface;
use App\Models\Steakhome_menu;
use Throwable;

class SHmenu implements RestaurantInterface
{
    private $getMenuUrl = 'http://neil.xincity.xyz:9998/steak_home/api/menu/ls';
    private $orderUrl = 'http://neil.xincity.xyz:9998/steak_home/api/mk/order';
    private $getMenuOnMenuIdUrl = 'http://neil.xincity.xyz:9998/steak_home/api/menu/ls?ID=';
    public function getMenu(int $offset, int $limit): array
    //修改為從api取得
    {
        $url = $this->getMenuUrl . '?LT=' . $limit . '&PG=' . $offset;
        try {
            $client = new Client();
            $response = $client->request('GET', $url);
            $goodResponse = $response->getBody();
            $arrayGoodResponse = json_decode($goodResponse, true);
            $apiMenu = $arrayGoodResponse['LS'];
            $targetData = [];
            foreach ($apiMenu as $item) {
                $menu = [
                    'rid' => 3,
                    'id' => $item['ID'],
                    'info' => '',
                    'name' => $item['NA'],
                    'price' => $item['PRC'],
                    'img' => ''
                ];
                $targetData[] = $menu;
            }
            return $targetData;
        } catch (Throwable $e) {
            return ['取得菜單錯誤:500'];
        }
    }
    public function menuEnable(array $menuIds): bool
    {
        $menu = Steakhome_menu::wherein('id', $menuIds)->get();
        $orderCount = count($menuIds);
        $notEnableCount = $menu->where('enable', '=', 1)->count();
        if ($orderCount !== $notEnableCount) {
            return false;
        }
        return true;
    }
    public function sendApi(array $orderInfo, array $order): bool
    {
        try {
            $targetData = [
                'OID' => $orderInfo['uid'],
                'NA' => $orderInfo['name'],
                'PH_NUM' => '0' . $orderInfo['phone'],
                'TOL_PRC' => $orderInfo['total_price'],
                'LS' => [],
            ];

            foreach ($order as $item) {
                if (isset($item['description'])) {
                    $ls = [
                        'ID' => $item['id'],
                        'NOTE' => $item['description'],
                    ];
                } else {
                    return false;
                }
                $targetData['LS'][] = $ls;
            }
            //發送Api
            $client = new Client();
            $response = $client->request('POST', $this->orderUrl, ['json' => $targetData]);
            $goodResponse = $response->getBody();
            $arrayGoodResponse = json_decode($goodResponse);
            //取得結果
            if ($arrayGoodResponse->ERR === 0) {
                return true;
            }
            return false;
        } catch (Throwable) {
            return false;
        }
    }
    public function menuCorrect(array $order): bool
    {
        try {
            foreach ($order as $item) {
                $client = new Client();
                $response = $client->request('GET', $this->getMenuOnMenuIdUrl . $item['id']);
                $goodResponse = $response->getBody();
                $arrayResponse = json_decode($goodResponse, true);
                if ($arrayResponse['LS'] === []) {
                    return false;
                }
                //取出Order內價格.名稱,餐點Id
                $orderName = $item['name'];
                $orderPrice = $item['price'];
                $orderId = $item['id'];
                //取出店家回傳菜單價格.名稱,餐點Id
                $responseName = $item['LS'][0]['NA'];
                $responseId = $item['LS'][0]['ID'];
                $responsePrice = $item['LS'][0]['PRC'];
                //比對是否不一致
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
            return true;
        }
    }
}
