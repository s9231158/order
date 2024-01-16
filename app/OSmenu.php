<?php

namespace App;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Date;
use App\Models\OishiiMenu as Oishii_menu;
use App\Contract\RestaurantInterface;
use Illuminate\Support\Facades\Redis;
use Throwable;

class OSmenu implements RestaurantInterface
{
    private $getMenuUrl = 'http://neil.xincity.xyz:9998/oishii/api/menu/all';
    private $orderUrl = 'http://neil.xincity.xyz:9998/oishii/api/notify/order';
    private $getMenuOnMenuIdUrl = 'http://neil.xincity.xyz:9998/oishii/api/menu/all?meal_id=';

    public function getMenu(int $offset, int $limit): array
    {
        try {
            /* 需要尋找的keys */$keys = range($offset + 1, $limit + $offset);
            /* redis內已有的所有keys */$redisKeys = Redis::hkeys('1menus');
            /* 扣除redis內已有的keys 還需要的keys */$needKeys = array_values(array_diff($keys, $redisKeys));
            /* 需要尋找的keys 但redis內已有的keys */$redisKeys = array_values(array_intersect($redisKeys, $keys));
            /* 如果還需要到database找資料 */$need = false;
            $response = [];
            if (empty($needKeys)) {
                foreach (Redis::hmget('1menus', $keys) as $item) {
                    $response[] = json_decode($item, true);
                }
                return $response;
            }
            if (!empty($needKeys)) {
                $need = true;
            }
            if (!empty($redisKeys)) {
                foreach (Redis::hmget('1menus', $redisKeys) as $item) {

                    $response[] = json_decode($item, true);
                }
            }
            if ($need) {
                $url = $this->getMenuUrl . '?limit=' . $limit . '&offset=' . $offset;
                $client = new Client();
                $response = $client->request('GET', $url);
                $goodResponse = $response->getBody();
                $arrayGoodResponse = json_decode($goodResponse, true);
                $apiMenu = $arrayGoodResponse['menu'];
                foreach ($apiMenu as $item) {
                    $menu = [
                        'rid' => 1,
                        'id' => $item['meal_id'],
                        'info' => $item['meal_type'],
                        'name' => $item['meal_name'],
                        'price' => $item['price'],
                        'img' => ''
                    ];
                    Redis::hset('1menus', $item['id'], json_encode($menu));
                    $response[] = $menu;
                }
            }
            return $response;
        } catch (Throwable $e) {
            return ['取得菜單錯誤:500'];
        }
    }

    public function menuEnable(array $menuIds): bool
    {
        $menu = Oishii_menu::wherein('id', $menuIds)->get();
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
                'id' => $orderInfo['uuid'],
                'name' => $orderInfo['name'],
                'phone_number' => '0' . $orderInfo['phone'],
                'pickup_time' => $iso8601String,
                'total_price' => $orderInfo['total_price'],
                'orders' => [],
            ];
            //如果再Service先把description處理好 萬一某些餐廳部接收description是空值
            foreach ($order as $item) {
                if (isset($item['description'])) {
                    $orders = [
                        'meal_id' => $item['id'],
                        'count' => $item['quanlity'],
                        'memo' => $item['description'],
                    ];
                } else {
                    $orders = [
                        'meal_id' => $item['id'],
                        'count' => $item['quanlity'],
                    ];
                }
                $targetData['orders'][] = $orders;
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
                //取得店家菜單
                $client = new Client();
                $response = $client->request('GET', $this->getMenuOnMenuIdUrl . $item['id']);
                $goodResponse = $response->getBody();
                $arrayResponse = json_decode($goodResponse, true);
                //找不到此id菜單就是錯誤
                if ($arrayResponse['menu'] === []) {
                    return false;
                }
                //取出Order內價格.名稱,餐點Id
                $orderName = $item['name'];
                $orderPrice = $item['price'];
                $orderId = $item['id'];
                //取出店家回傳菜單價格.名稱,餐點Id
                $responseName = $arrayResponse['menu'][0]['meal_name'];
                $responseId = $arrayResponse['menu'][0]['meal_id'];
                $responsePrice = $arrayResponse['menu'][0]['price'];
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
            return false;
        }
    }
}
