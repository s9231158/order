<?php

namespace App\Http\Controllers;

use App\ErrorCodeService;
use App\Models\Restaurant;
use App\Models\Restaurant_comment;
use App\Service\RestaurantService;
use App\TotalService;
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
use App\OSmenu;
use GuzzleHttp\Client;
use App\Contract\OSmenu as apple;
use App\Factorise;
use App\TAmenu;

use function PHPSTORM_META\type;

class RestaurantController extends Controller
{
    //錯誤訊息統整
    // private $err = [
    //     '0' => 0, //成功
    //     '1' => 1, //資料填寫與規格不符
    //     '2' => 2, //必填資料未填
    //     '3' => 3, //email已註冊
    //     '4' => 4, //電話已註冊
    //     '5' => 5, //系統錯誤,請重新登入
    //     '6' => 6, //已登入
    //     '7' => 7, //短時間內登入次數過多
    //     '8' => 8, //帳號或密碼錯誤
    //     '9' => 9, //token錯誤
    //     '12' => 12, //請在訂餐後24內評論
    //     '14' => 14, //已評論過
    //     '15' => 15, //重複新增我的最愛
    //     '16' => 16, //查無此餐廳
    //     '23' => 23, //無效的範圍
    //     '26' => 26, //系統錯誤
    //     '28' => 28, //超過最大我的最愛筆數
    //     '29' => 29, //請重新登入

    // ];
    //星期幾轉阿拉伯數字
    private $traslate = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 7
    ];





    //new
    private $TotalService;
    private $ErrorCodeService;
    private $RestaurantService;
    private $err = [];
    private $keys = [];
    public function __construct(TotalService $TotalService, ErrorCodeService $ErrorCodeService, RestaurantService $RestaurantService)
    {
        $this->RestaurantService = $RestaurantService;
        $this->TotalService = $TotalService;
        $this->ErrorCodeService = $ErrorCodeService;
        $this->err = $ErrorCodeService->GetErrCode();
        $this->keys = $ErrorCodeService->GetErrKey();
    }






    public function restaurant(Request $request)
    {
        //new
        //驗證輸入參數
        try {
            $Validator = $this->TotalService->LimitOffsetValidator($request);
            if ($Validator !== true) {
                return $Validator;
            }

            //取得OffsetLimit
            $OffsetLimit = ['limit' => $request['limit'], 'offset' => $request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);

            //今天星期幾
            $Today = date('l');
            //取得餐廳info並打亂順序
            $RestaurantInfo = $this->RestaurantService->GetRestaurantInfoOffsetLimit($OffsetLimit, $Today)->shuffle();
            $RestaurantInfoCount = $RestaurantInfo->count();
            return response()->json(['message' => $this->keys[0], 'err' => $this->err['0'], 'count' => $RestaurantInfoCount, 'data' => $RestaurantInfo]);


            //old
            // //規則
            // $ruls = [
            //     'limit' => ['regex:/^[0-9]+$/'],
            //     'offset' => ['regex:/^[0-9]+$/'],
            // ];
            // //什麼錯誤報什麼錯誤訊息
            // $rulsMessage = [
            //     'limit.regex' => $this->err['23'],
            //     'offset.regex' => $this->err['23']
            // ];
            // try {
            //     //設定limit與offset預設
            //     if ($request->limit === null) {
            //         $limit = 20;
            //     } else {
            //         $limit = $request->limit;
            //     }
            //     if ($request->offset === null) {
            //         $offset = 0;
            //     } else {
            //         $offset = $request->offset;
            //     }
            //     $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            //     //驗證失敗回傳錯誤訊息
            //     if ($validator->fails()) {
            //         return response()->json(['err' => $validator->errors()->first()]);
            //     }
            //     //取得今天星期幾
            //     $day = Carbon::now()->format('l');
            //     //把英文星期幾轉換成星期幾阿拉伯數字
            //     $daynumber = $this->traslate[$day];
            //     $Restaurant = Restaurant::select('id', 'title', 'img', 'totalpoint', 'countpoint')->where('openday', 'like', '%' . $daynumber . '%')->offset($offset)->limit($limit)->get();
            //     $count = Restaurant::where('openday', 'like', '%' . $daynumber . '%')->get()->count();
            //     return response()->json(['err' => 0, 'count' => $count, 'data' => $Restaurant]);




        } catch (Exception) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
        } catch (Throwable) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
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
            'rid.regex' => $this->err['23'],
            'rid.required' => $this->err['2'],
        ];
        try {
            //預設limit&offset
            if ($request->limit == null) {
                $limit = '20';
            } else {
                $limit = $request->limit;
            }
            if ($request->offset === null) {
                $offset = '0';
            } else {
                $offset = $request->offset;
            }
            //驗證參輸入數
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }
            $rid = $request->rid;

            //是否有該餐廳
            $hasRestraunt = Restaurant::where('id', '=', $rid)->count();
            if ($hasRestraunt != 1) {
                return response()->json(['err' => $this->err['16']]);
            }

            //取得餐廳資訊&菜單
            $menufactors = Factorise::Setmenu($rid);
            $menu = $menufactors->getmenu($offset, $limit);
            $Restaurantinfo = Restaurant::select('title', 'info', 'openday', 'opentime', 'closetime', 'img', 'address', 'totalpoint', 'countpoint')->where('id', '=', $rid)->get();


            //如果有登出
            $clienttoken = $request->header('Authorization');
            if ($clienttoken) {
                $usertoken = JWTAuth::parseToken()->authenticate();
                $email = $usertoken->email;
                $redietoken = 'Bearer ' . Cache::get($email);
                //檢查session
                if (Cache::has($email) && $clienttoken !== $redietoken) {
                    Cache::forget($email);
                    return response()->json(['1', 'err' => $this->err['28']]);
                }
                if (!Cache::has($email)) {
                    return response()->json(['err' => $this->err['28']]);
                }
                //取得token使用者資料
                $usertoken = JWTAuth::parseToken()->authenticate();
                $userid = $usertoken->id;
                $user = User::find($userid);
                $now = Carbon::now();
                //存入使用者瀏覽餐廳歷史紀錄
                $already = $user->history()->where('rid', '=', $rid)->count();
                //如果沒瀏覽過就新增,否則更新創建時間
                if ($already === 0) {
                    $user->history()->attach($rid);
                } else {
                    $user->history()->select('restaurant_histories.created_at')->where('rid', '=', $rid)->update(['restaurant_histories.created_at' => $now]);
                }
            }
            return response()->json(['err' => $this->err['0'], 'data' => $Restaurantinfo[0], 'menu' => $menu]);
        } catch (TokenInvalidException $e) {
            return response()->json(['err' => $this->err['26']]);
        } catch (Throwable $e) {
            return response()->json(['err' => $this->err['26']]);
        }
    }
    public function comment(Request $request)
    {
        //規則
        $ruls = [
            'point' => ['required', 'size:1', 'regex:/^[1-5]+$/'],
            'comment' => ['required', 'min:10', 'max:25'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'point.required' => $this->err['2'],
            'point.size' => $this->err['1'],
            'point.regex' => $this->err['1'],
            'comment.required' => $this->err['2'],
            'comment.min' => $this->err['1'],
            'comment.max' => $this->err['1']
        ];
        try {
            //驗證輸入數值
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            //如果有錯回報錯誤訊息
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }
            $rid = $request->rid;
            $comment = $request->comment;
            $point = $request->point;
            $usertoken = JWTAuth::parseToken()->authenticate();
            $userid = $usertoken->id;
            $user = User::find($userid);
            $yesterday = Carbon::now()->subDay();
            //是否有該餐廳
            $hasrestaurant = Restaurant::where('id', '=', $rid)->count();
            if ($hasrestaurant === 0) {
                return response()->json(['err' => $this->err['16']]);
            }
            //評論者是否在此訂餐廳訂過餐且訂單狀態是成功且記錄在24小時內
            $hasorder = $user->order()->where('status', '=', '成功')->where('ordertime', '>', $yesterday)->count();
            if ($hasorder < 1) {
                return response()->json(['err' => $this->err['12']]);
            }
            //評論者是否第一次對該餐廳評論
            $hascomment = $user->comment()->where('rid', '=', $rid)->get()->count();
            if ($hascomment >= 1) {
                return response()->json(['err' => $this->err['14']]);
            }
            //將評論存到資料庫
            $user->comment()->attach($rid, ['comment' => $comment, 'point' => $point]);
            return response()->json(['err' => $this->err['0']]);
        } catch (Exception $e) {
            return response()->json(['' => $e->getMessage()]);
        }
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
            'rid.regex' => $this->err['23'],
            'rid.required' => $this->err['2'],
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
            //是否有該餐廳
            $hasrestaurant = Restaurant::where('id', '=', $rid)->count();
            if ($hasrestaurant === 0) {
                return response()->json(['err' => $this->err['16']]);
            }
            $comment = Restaurant_comment::select('users.name', 'restaurant_comments.point', 'restaurant_comments.comment', 'restaurant_comments.created_at')
                ->join('users', 'users.id', '=', 'restaurant_comments.uid')->where('restaurant_comments.rid', '=', $rid)
                ->offset($offset)->limit($limit)->orderBy('restaurant_comments.created_at', 'desc')->get();
            $count = Restaurant_comment::where('restaurant_comments.rid', '=', $rid)->count();
            return response()->json(['err' => $this->err['0'], 'count' => $count, 'commentdata' => $comment]);
        } catch (Exception $e) {
            return response()->json([$e, 'err' => $this->err['26']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }
}
