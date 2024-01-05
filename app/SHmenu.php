<?php

namespace App;

use GuzzleHttp\Client;
use App\Contract\RestaurantInterface;
use App\Models\Steakhome_menu;
use Throwable;

class SHmenu implements RestaurantInterface
{
    private $GetMenuUrl = 'http://neil.xincity.xyz:9998/steak_home/api/menu/ls';
    private $OrderUrl = 'http://neil.xincity.xyz:9998/steak_home/api/mk/order';
    private $GetMenuOnMenuIdUrl = 'http://neil.xincity.xyz:9998/steak_home/api/menu/ls?ID=';
    public function GetMenu(int $Offset, int $Limit): array
    //修改為從api取得
    {
        $Url = $this->GetMenuUrl . '?LT=' . $Limit . '&PG=' . $Offset;
        try {
            $Client = new Client();
            $Response = $Client->request('GET', $Url);
            $GoodResponse = $Response->getBody();
            $ArrayGoodResponse = json_decode($GoodResponse, true);
            $ApiMenu = $ArrayGoodResponse['LS'];
            $TargetData = [];
            foreach ($ApiMenu as $Item) {
                $Menu = [
                    'rid' => 3,
                    'id' => $Item['ID'],
                    'info' => '',
                    'name' => $Item['NA'],
                    'price' => $Item['PRC'],
                    'img' => ''
                ];
                $TargetData[] = $Menu;
            }
            return $TargetData;
        } catch (Throwable $e) {
            return ['取得菜單錯誤:500'];
        }
    }
    public function MenuEnable(array $MenuId): bool
    {
        $Menu = Steakhome_menu::wherein('id', $MenuId)->get();
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
            $TargetData = [
                'OID' => $OrderInfo['uid'],
                'NA' => $OrderInfo['name'],
                'PH_NUM' => '0' . $OrderInfo['phone'],
                'TOL_PRC' => $OrderInfo['total_price'],
                'LS' => [],
            ];

            foreach ($Order as $Item) {
                if (isset($Item['description'])) {
                    $LS = [
                        'ID' => $Item['id'],
                        'NOTE' => $Item['description'],
                    ];
                } else {
                    return false;
                }
                $TargetData['LS'][] = $LS;
            }
            //發送Api
            $Client = new Client();
            $Response = $Client->request('POST', $this->OrderUrl, ['json' => $TargetData]);
            $GoodResponse = $Response->getBody();
            $ArrayGoodResponse = json_decode($GoodResponse);
            //取得結果
            if ($ArrayGoodResponse->ERR === 0) {
                return true;
            }
            return false;
        } catch (Throwable) {
            return false;
        }
    }
    public function MenuCorrect(array $Order): bool
    {
        try {
            foreach ($Order as $Item) {
                $Client = new Client();
                $Response = $Client->request('GET', $this->GetMenuOnMenuIdUrl . $Item['id']);
                $GoodResponse = $Response->getBody();
                $ArrayResponse = json_decode($GoodResponse, true);
                if ($ArrayResponse['LS'] === []) {
                    return false;
                }
                //取出Order內價格.名稱,餐點Id
                $OrderName = $Item['name'];
                $OrderPrice = $Item['price'];
                $OrderId = $Item['id'];
                //取出店家回傳菜單價格.名稱,餐點Id
                $ResponseName = $Item['LS'][0]['NA'];
                $ResponseId = $Item['LS'][0]['ID'];
                $ResponsePrice = $Item['LS'][0]['PRC'];
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
