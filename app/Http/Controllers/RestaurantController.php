<?php

namespace App\Http\Controllers;

use App\ErrorCodeService;
use App\Service\OrderService;
use App\Service\RestaurantCommentService;
use App\Service\RestaurantHistoryService;
use App\Service\RestaurantService;
use App\TotalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Exception;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Throwable;
use App\Factorise;

class RestaurantController extends Controller
{
    //new
    private $TotalService;
    private $ErrorCodeService;
    private $RestaurantService;
    private $err = [];
    private $keys = [];
    private $RestaurantHistoryService;
    private $OrderService;
    private $RestaurantCommentService;
    public function __construct(RestaurantCommentService $RestaurantCommentService, OrderService $OrderService, RestaurantHistoryService $RestaurantHistoryService, TotalService $TotalService, ErrorCodeService $ErrorCodeService, RestaurantService $RestaurantService)
    {
        $this->OrderService = $OrderService;
        $this->RestaurantHistoryService = $RestaurantHistoryService;
        $this->RestaurantService = $RestaurantService;
        $this->TotalService = $TotalService;
        $this->RestaurantCommentService = $RestaurantCommentService;
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
            $RestaurantInfo = $this->RestaurantService->GetRestaurantInfoOffsetLimit($OffsetLimit, $Today)->where('enable','=','1')->shuffle()->map->only(['id','title','img','totalpoint','countpoint']);
            $RestaurantInfoCount = $RestaurantInfo->count();
            return response()->json(['message' => $this->keys[0], 'err' => $this->err['0'], 'count' => $RestaurantInfoCount, 'data' => $RestaurantInfo]);

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

            //new
            //驗證參輸入數
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }

            //取得OffsetLimit
            $OffsetLimit = ['limit' => $request['limit'], 'offset' => $request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);
            $Offset = $OffsetLimit['offset'];
            $Limit = $OffsetLimit['limit'];

            $Rid = $request->rid;
            $ArrayRid[] = $request->rid;

            //是否有該餐廳
            $HasRestraunt = $this->RestaurantService->CheckRestaurantInDatabase($Rid);
            if (!$HasRestraunt) {
                return response()->json(['err' => $this->keys['16'], 'message' => $this->err[16]]);
            }

            //取得該餐廳的實例
            $Restaurant = Factorise::Setmenu($Rid);

            //使用該餐廳實例取的菜單
            $Menu = $Restaurant->getmenu($Offset, $Limit);
            $Restaurantinfo = $this->RestaurantService->GetRestaurantinfo($ArrayRid);
            $Restaurantinfo = $Restaurantinfo->map->only(['title', 'info', 'openday', 'opentime', 'closetime', 'img', 'address', 'totalpoint', 'countpoint']);

            //檢查是否有登入
            $Token = $request->header('Authorization');
            if ($Token) {
                $TokenCheck = $this->TotalService->CheckToken($Token);
            }

            //檢查是否Token正確
            if ($TokenCheck === false) {
                return response()->json(['err' => $this->keys['5'], 'message' => $this->err[5]]);
            }

            //取得使用者資料
            $UserInfo = $this->TotalService->GetUserInfo();
            $UserId = $UserInfo->id;

            //是否已存在資料庫,有的話更新時間,沒有則建立紀錄
            $this->RestaurantHistoryService->UpdateOrCreateHistory($UserId, $Rid);

            return response()->json(['err' => $this->keys['0'], 'message' => $this->err[0], 'data' => $Restaurantinfo[0], 'menu' => $Menu]);
        } catch (TokenInvalidException $e) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
        } catch (Throwable $e) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
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

            //是否有該餐廳
            $Rid = $request->rid;
            $HasRestaurant = $this->RestaurantService->CheckRestaurantInDatabase($Rid);
            if (!$HasRestaurant) {
                return response()->json(['err' => $this->keys['16'], 'message' => $this->err[16]]);
            }

            //評論者是否在此訂餐廳訂過餐且訂單狀態是成功且記錄在24小時內
            $UserInfo = $this->TotalService->GetUserInfo();
            $UserId = $UserInfo['id'];
            $Yesterday = Carbon::yesterday();
            $Order = $this->OrderService->GetOrder($UserId);
            $Has24HOrder = $Order->where('rid', '=', $Rid)->where('ordertime', '>', $Yesterday)->where('status', '=', '成功')->count();
            if ($Has24HOrder < 1) {
                return response()->json(['err' => $this->keys['12'], 'message' => $this->err[12]]);
            }

            //評論者是否第一次對該餐廳評論
            $UserComment = $this->RestaurantCommentService->GetUserComment($UserId);
            $Existed = $UserComment->count();
            if ($Existed === 1) {
                return response()->json(['err' => $this->keys['14'], 'message' => $this->err[14]]);
            }
            //將評論存到資料庫
            $Data = ['uid' => $UserId, 'rid' => $request->rid, 'comment' => $request->comment, 'point' => $request->point];
            $this->RestaurantCommentService->AddComment($Data);
            return response()->json(['err' => $this->keys['0'], 'message' => $this->err[0]]);
        } catch (Exception $e) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
        } catch (Throwable $e) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
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
            //new
            //驗證
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            //驗證失敗回傳錯誤訊息
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }

            //取得OffsetLimit
            $OffsetLimit = ['limit' => $request['limit'], 'offset' => $request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);
            $Offset = $OffsetLimit['offset'];
            $Limit = $OffsetLimit['limit'];

            //是否有該餐廳 
            $Rid = $request->rid;
            $HasRestaurant = $this->TotalService->CheckRestaurantInDatabase($Rid);
            if (!$HasRestaurant) {
                return response()->json(['err' => $this->keys[16], 'message' => $this->err[16]]);
            }

            //取出評論
            $RestaurantComment = $this->RestaurantCommentService->GetRestaurantComment($Rid, $OffsetLimit);

            //計算評論數量
            $RestaurantCommentCount = $RestaurantComment->count();

            //排序取出評論
            $RestaurantComment = $RestaurantComment->sortByDesc('created_at')->values()->all();
            return response()->json(['err' => $this->keys[0], 'message' => $this->err[0], 'count' => $RestaurantCommentCount, 'Comment' => $RestaurantComment]);

        } catch (Exception $e) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
        } catch (Throwable) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
        }
    }
}
