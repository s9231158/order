<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class RestaurantController extends Controller
{
    //錯誤訊息統整
    private $err = [
        //成功
        '0' => 0,
        //資料填寫與規格不符
        '1' => 1,
        //必填資料未填
        '2' => 2,
        //email已註冊
        '3' => 3,
        //電話已註冊
        '4' => 4,
        //系統錯誤,請重新登入
        '5' => 5,
        //已登入
        '6' => 6,
        //短時間內登入次數過多
        '7' => 7,
        //帳號或密碼錯誤
        '8' => 8,
        //token錯誤
        '9' => 9,
        //無效的範圍
        '23' => 23,
        //系統錯誤,稍後在試
        '26' => 26,
    ];
    //星期幾轉數字
    private $traslate = ['Thursday' => 3];

    public function restaurant(Request $request)
    {
        //規則
        $ruls = [
            'limit' => ['regex:/^[0-9]+$/'],
            'offset' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.regex' => $this->err['23'],
            'offset.regex' => $this->err['23']
        ];
        //設定limit與offset預設
        if ($request->limit == null) {
            $limit = 20;
        } else {
            $limit = $request->limit;
        }
        if ($request->offset === null) {
            $offset = 0;
        } else {
            $offset = $request->offset;
        }

        $validator = Validator::make($request->all(), $ruls, $rulsMessage);
        //驗證失敗回傳錯誤訊息
        if ($validator->fails()) {
            return response()->json(['err' => $validator->errors()->first()]);
        }

        $day = Carbon::now()->format('l');
        $daynumber = $this->traslate[$day];
        $Restaurant = Restaurant::select('id','title','img','totalpoint','countpoint')->where('openday', 'like', '%' . $daynumber . '%')->offset($offset)->limit($limit)->get();
        $count = $Restaurant->count();
        return response()->json(['err'=>0 ,'count'=>$count,'data'=>$Restaurant]);
    }
}
