<?php

namespace App;

use App\Contract\RestaurantInterface;
use App\Models\Tasty_menu;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Date;
use Throwable;

class TAmenu implements RestaurantInterface
{
    private $getMenuUrl = 'http://neil.xincity.xyz:9998/tasty/api/menu';
    private $orderUrl = 'http://neil.xincity.xyz:9998/tasty/api/order';
    private $getMenuOnMenuIdUrl = 'http://neil.xincity.xyz:9998/tasty/api/menu?id=';
    public function getMenu(int $offset, int $limit): array
    //修改為從api取得
    {
        $url = $this->getMenuUrl . '?limit=' . $limit . '&offset=' . $offset;
        try {
            $client = new Client();
            $response = $client->request('GET', $url);
            $goodResponse = $response->getBody();
            $arrayGoodResponse = json_decode($goodResponse, true);
            $apiMenu = $arrayGoodResponse['data']['list'];
            $targetData = [];
            foreach ($apiMenu as $item) {
                if ($item['enable'] != '0') {
                    $menu = [
                        'rid' => 2,
                        'id' => $item['id'],
                        'info' => '',
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'img' => ''
                    ];
                    $targetData[] = $menu;
                } else {
                    return $targetData;
                }
            }
            return $targetData;
        } catch (Throwable $e) {
            return ['取得菜單錯誤:500'];
        }
    }
    public function menuEnable(array $menuIds): bool
    {
        $menu = Tasty_menu::wherein('id', $menuIds)->get();
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
            $dateTime = Date::createFromFormat('Y-m-d H:i:s', $orderInfo['taketime']);
            $iso8601String = $dateTime->format('c');
            $targetData = [
                'order_id' => $orderInfo['uid'],
                'name' => $orderInfo['name'],
                'phone_number' => '0' . $orderInfo['phone'],
                'pickup_time' => '2016-06-01T14:41:36+08:00',
                'total_price' => $orderInfo['total_price'],
                'order' => ['list' => []],
            ];
            foreach ($order as $item) {
                if (isset($item['description'])) {
                    $list = [
                        'id' => $item['id'],
                        'count' => $item['quanlity'],
                        'description' => $item['description'],
                    ];
                } else {
                    $list = [
                        'id' => $item['id'],
                        'count' => $item['quanlity'],
                    ];
                }
                $targetData['order']['list'][] = $list;
            }
            //發送Api
            $client = new Client();
            $response = $client->request('POST', $this->orderUrl, ['json' => $targetData]);
            $goodResponse = $response->getBody();
            $arrayGoodResponse = json_decode($goodResponse);
            //取得結果
            if ($arrayGoodResponse->error_code === 0) {
                return true;
            }
            return false;
        } catch (Throwable $e) {
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
                if ($arrayResponse['data']['list'] === []) {
                    return false;
                }
                //取出Order內價格.名稱,餐點Id
                $orderName = $item['name'];
                $orderPrice = $item['price'];
                $orderId = $item['id'];
                //取出店家回傳菜單價格.名稱,餐點Id
                $responseName = $item['data']['list'][0]['name'];
                $responseId = $item['data']['list'][0]['id'];
                $responsePrice = $item['data']['list'][0]['price'];
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
