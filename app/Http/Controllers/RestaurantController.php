<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\User_favorite;
use Exception;
use Faker\Core\Number;
use Illuminate\Support\Facades\Cache;
use PDOException;

use function PHPSTORM_META\type;

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
        //重複新增我的最愛
        '15' => 15,
        //查無此餐廳
        '16' => 16,
        //無效的範圍
        '23' => 23,
        //系統錯誤
        '26' => 26,
        //超過最大我的最愛筆數
        '28' => 28,
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
        $a = 100/0;
        throw new \Exception('another error.');
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
        $Restaurant = Restaurant::select('id', 'title', 'img', 'totalpoint', 'countpoint')->where('openday', 'like', '%' . $daynumber . '%')->offset($offset)->limit($limit)->get();
        $count = Restaurant::select('id', 'title', 'img', 'totalpoint', 'countpoint')->where('openday', 'like', '%' . $daynumber . '%')->get()->count();
        if ($offset > $count);
        return response()->json(['err' => 87]);

        return response()->json(['err' => 0, 'count' => $count, 'data' => $Restaurant]);
    }


    public function favorite(Request $request)
    {
        try {
            $a = JWTAuth::parseToken()->authenticate();
            $email = $a->email;
            $rid = $request->rid;
            $user = User::find(Auth::id());
            $databaserestruant = Restaurant::select('rid')->where('rid', '=', $rid)->count();
            if ($databaserestruant === 0) {
                return response()->json(['err' => $this->err['16']]);
            }
            $exzest = $user->favorite()->select('rid')->where('rid', '=', $rid)->get()->count();
            $count = $user->favorite()->count();
            if ($count >= 20) {
                return response()->json(['err' => $this->err['28']]);
            }
            if (!$exzest) {
                $user->favorite()->attach($rid);
                return response()->json(['err' => 0]);
            } else {
                return response()->json(['err' => $this->err['15']]);
            }
        } catch (PDOException) {
            return response()->json(['err' => $this->err['1']]);
        } catch (Exception $e) {
            return response()->json([$e, 'err' => $this->err['26']]);
        }
    }

    public function getfavorite(Request $request)
    {
        if ($request->limit === null) {
            $limit = 20;
        } else {
            $limit = $request->limit;
        }
        if ($request->offset === null) {
            $offset = 0;
        } else {
            $offset = $request->offset;
        }
        $user = User::find(Auth::id());
        $count = $user->favorite()->count();
        $bb = $user->favorite()->limit($limit)->offset($offset)->orderBy('created_at', 'desc')->get();
        $cc = $user->favorite()->limit($limit)->offset($offset)->orderBy('created_at', 'desc')->get();

        return response()->json(['err'=>$this->err['0'],'count'=>$count,$bb]);
    }

    public function deletefavorite(Request $request)
    {
        try {
            $rid = $request->rid;
            $user = User::find(Auth::id());
            $myfavorite = $user->favorite()->where('rid', '=', $rid)->count();
            if ($myfavorite) {
                $user->favorite()->detach($rid);
                return response()->json(['err' => $this->err['0']]);
            } else {
                return response()->json(['err' => $this->err['16']]);
            }
        } catch (PDOException) {
            return response()->json(['err' => $this->err['1']]);
        } catch (Exception) {
            return response()->json(['err' => $this->err['26']]);
        }
    }
}
