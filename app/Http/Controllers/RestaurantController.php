<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Restaurant_comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\User_favorite;
use Exception;
use Faker\Core\Number;
use Illuminate\Queue\Console\RestartCommand;
use Illuminate\Support\Facades\Cache;
use PDOException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Throwable;

use function PHPSTORM_META\type;

class RestaurantController extends Controller
{
    //錯誤訊息統整
    private $err = [
        '0' => 0, //成功
        '1' => 1, //資料填寫與規格不符
        '2' => 2, //必填資料未填
        '3' => 3, //email已註冊
        '4' => 4, //電話已註冊
        '5' => 5, //系統錯誤,請重新登入
        '6' => 6, //已登入
        '7' => 7, //短時間內登入次數過多
        '8' => 8, //帳號或密碼錯誤
        '9' => 9, //token錯誤
        '15' => 15, //重複新增我的最愛
        '16' => 16, //查無此餐廳
        '23' => 23, //無效的範圍
        '26' => 26, //系統錯誤
        '28' => 28, //超過最大我的最愛筆數
        '29' => 29, //請重新登入
    ];
    //星期幾轉數字
    private $traslate = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 7
    ];

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
        try {
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
            return response()->json(['err' => 0, 'count' => $count, 'data' => $Restaurant]);
        } catch (Exception) {
            return response()->json(['err' => $this->err['26']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }




    public function menu(Request $request)
    {
        //規則
        $ruls = [
            'limit' => ['regex:/^[0-9]+$/'],
            'offset' => ['regex:/^[0-9]+$/'],
            'rid' => ['required', 'regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.regex' => $this->err['23'],
            'offset.regex' => $this->err['23'],
            'rid.regex' => $this->err['23'], 'rid.required' => $this->err['2'],
        ];
        try {
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
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }

            $clienttoken = $request->header('Authorization');
            if ($clienttoken) {
                $usertoken = JWTAuth::parseToken()->authenticate();
                $email = $usertoken->email;
                $redietoken = 'Bearer ' . Cache::get($email);
                if (Cache::has($email) && $clienttoken !== $redietoken) {
                    Cache::forget($email);
                    return response()->json(['1', 'err' => $this->err['28']]);
                }
                if (!Cache::has($email)) {
                    return response()->json(['err' => $this->err['28']]);
                }
            }


            $userid = $usertoken->id;
            $user = User::find($userid);
            $now = Carbon::now();
            $rid = $request->rid;
            $already = $user->history()->where('rid', '=', $rid)->count();
            if ($already === 0) {
                $user->history()->attach($rid);
            } else {
                $user->history()->select('restaurant_histories.updated_at')->update(['restaurant_histories.updated_at' => $now]);
            }



            //未完 有點問題
            $Restaurant = Restaurant::find($rid);
            $Restaurantinfo = $Restaurant->select('title', 'info', 'openday', 'closetime', 'img', 'address', 'totalpoint', 'countpoint')->where('id', '=', $rid)->get();
            $menu = $Restaurant->menu()->limit($limit)->offset($offset)->get();

            
            return response()->json(['err' => $this->err['0'], 'data' => $Restaurantinfo, 'menu' => $menu]);



        } catch (TokenInvalidException $e) {
            return response()->json(['err' => $this->err['29']]);
        } catch (Exception $e) {
            return response()->json(['err' => $this->err['26']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }
    public function comment(Request $request)
    {
        $rid = $request->rid;
        $comment = $request->comment;
        $point = $request->point;
        $usertoken = JWTAuth::parseToken()->authenticate();
        $userid = $usertoken->id;
        $user = User::find($userid);
        //評論者是否在此訂餐廳訂過餐且訂單狀態是成功



        //訂餐紀錄是否在24小時內



        //評論者是否第一次對該餐廳評論
        $apple =  $user->comment()->where('rid', '=', $rid)->get()->count();
        if ($apple === 0)


            //將評論存到資料庫
            $user->comment()->attach($rid, ['comment' => $comment, 'point' => $point]);
        return response(['123']);
    }



    public function getcomment(Request $request)
    {

        //規則
        $ruls = [
            'limit' => ['regex:/^[0-9]+$/'],
            'offset' => ['regex:/^[0-9]+$/'],
            'rid' => ['required', 'regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.regex' => $this->err['23'],
            'offset.regex' => $this->err['23'],
            'rid.regex' => $this->err['23'], 'rid.required' => $this->err['2'],
        ];
        try {
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
            $rid = $request->rid;
            $comment = Restaurant_comment::select('users.name', 'restaurant_comments.point', 'restaurant_comments.comment', 'restaurant_comments.created_at')
            ->join('users', 'users.id', '=', 'restaurant_comments.uid')->where('restaurant_comments.rid', '=', $rid)
            ->offset($offset)->limit($limit)->orderBy('restaurant_comments.created_at', 'desc')->get();
            return response()->json([$comment, 'err' => $this->err['0']],);
        } catch (Exception $e) {
            return response()->json([$e, 'err' => $this->err['26']]);
        }
        catch(Throwable){
            return response()->json(['err' => $this->err['26']]);

        }
    }
}
