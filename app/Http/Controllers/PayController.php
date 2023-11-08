<?php

namespace App\Http\Controllers;

use App\Factorise;
use App\Models\Restaurant;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use function PHPSTORM_META\map;

class PayController extends Controller
{
    private $traslate = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 7
    ];
    public function otherpay(Request $request)
    {
        $name = $request->name;
        $address = $request->address;
        $phone = $request->phone;
        $totalprice = $request->totalprice;
        $orders1 = $request->orders;
        $oname = $orders1[0]['name'];
        $rid = $orders1[0]['rid'];
        $ridString = strval($rid);

        try {

            //訂單總金額是否正確
            $realtotalprice = 0;
            foreach ($orders1 as $a) {
                $realtotalprice += $a['price'] * $a['quanlity'];
                //訂單是否都來自同一間餐廳
                if ($a['rid'] != $rid) {
                    return '幹你娘點同一間好嗎';
                }
            }
            if ($realtotalprice != $totalprice) {
                return response()->json(['err' => '價格錯誤']);
            }
            //錢包餘額是否大於totoprice
            $usertoken = JWTAuth::parseToken()->authenticate();
            $userid = $usertoken->id;
            $user = User::find($userid);
            $wallet = $user->wallet()->get();
            $balance =  $wallet[0]['balance'];
            if ($balance < $realtotalprice) {
                return '錢不夠';
            }
            //餐點餐廳今天是否有營業
            $day = Carbon::now()->format('l');
            $daynumber = $this->traslate[$day];
            $Restaurantopen = Restaurant::where('id', '=', $rid)->where('openday', 'like', '%' . $daynumber . '%')->count();
            if ($Restaurantopen == 0) {
                return '幹你娘就沒開';
            }
            $Factorise = Factorise::Setmenu($ridString);
            // 餐點是否停用
            $Menuenable = $Factorise->Menuenable($orders1);
            if ($Menuenable != 0) {
                return '幹你娘就停用';
            }
            //餐廳是否停用
            $Restrauntenable = $Factorise->Restrauntenable($ridString);
            if ($Restrauntenable != 0) {
                return '幹你娘餐廳就停用';
            }

            //是否有該餐點
            $Hasmenu = $Factorise->Hasmenu($orders1);
            $realmenu = $Hasmenu->original[0];
            $ordermenu = $Hasmenu->original[1];
            if ($realmenu != $ordermenu) {
                return '幹你娘不要點菜單沒有的';
            }

            //轉換店家要求api格式
            $changedata = $Factorise->Change($request,$orders1);
            return $changedata;

        } catch (Exception $e) {
            return $e;
        }
    }
}
