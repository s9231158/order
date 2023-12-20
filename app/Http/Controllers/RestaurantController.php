<?php

namespace App\Http\Controllers;

use App\ErrorCodeService;
use App\Service\OrderService;
use App\Service\RestaurantCommentService;
use App\Service\RestaurantHistoryService;
use App\Service\RestaurantService;
use App\ServiceV2\RestaurantServiceV2;
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
    private $Err;
    private $Keys;
    private $RestaurantServiceV2;
    public function __construct(
        RestaurantServiceV2 $RestaurantServiceV2,
        RestaurantCommentService $RestaurantCommentService,
        TotalService $TotalService,
        ErrorCodeService $ErrorCodeService,
    ) {
        $this->RestaurantServiceV2 = $RestaurantServiceV2;
        $this->TotalService = $TotalService;
        $this->RestaurantCommentService = $RestaurantCommentService;
        $this->ErrorCodeService = $ErrorCodeService;
        $this->Err = $ErrorCodeService->GetErrCode();
        $this->Keys = $ErrorCodeService->GetErrKey();
    }
    public function Restaurant(Request $Request)
    {
        //規則
        $Ruls = [
            'limit' => ['integer'],
            'offset' => ['integer']
        ];
        
        //什麼錯誤報什麼錯誤訊息
        $RulsMessage = [
            'limit.integer' => '無效的範圍',
            'offset.integer' => '無效的範圍'
        ];
        try {
            //驗證參輸入數
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json(['Err' => array_search($Validator->Errors()->first(), $this->Err), 'Message' => $Validator->Errors()->first()]);
            }

            //取得OffsetLimit
            $OffsetLimit = ['limit' => $Request['limit'], 'offset' => $Request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);

            //今天星期幾
            $Today = date('l');

            //取得餐廳info並打亂順序
            $RestaurantInfo = $this->RestaurantServiceV2->GetRestaurantOnOffsetLimit($OffsetLimit, $Today);
            $RestaurantInfoCount = $RestaurantInfo->count();
            return response()->json(['message' => $this->Keys[0], 'Err' => $this->Err['0'], 'count' => $RestaurantInfoCount, 'data' => $RestaurantInfo]);
        } catch (Exception) {
            return response()->json(['Err' => $this->Keys[26], 'Message' => $this->Err[26]]);
        } catch (Throwable) {
            return response()->json(['Err' => $this->Keys[26], 'Message' => $this->Err[26]]);
        }
    }

    public function Menu(Request $Request)
    {
        //規則
        $Ruls = [
            'limit' => ['integer'],
            'offset' => ['integer'],
            'rid' => ['required', 'integer'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $RulsMessage = [
            'limit.integer' => '無效的範圍',
            'offset.integer' => '無效的範圍',
            'rid.integer' => '無效的範圍',
            'rid.required' => '必填資料未填',
        ];
        try {
            //驗證參輸入數
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json(['Err' => array_search($Validator->Errors()->first(), $this->Err), 'Message' => $Validator->Errors()->first()]);
            }

            //取得OffsetLimit
            $OffsetLimit = ['limit' => $Request['limit'], 'offset' => $Request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);

            //是否有該餐廳
            $Rid = $Request->rid;
            $HasRestraunt = $this->RestaurantServiceV2->CheckRestaurantInDatabase($Rid);
            if (!$HasRestraunt) {
                return response()->json(['Err' => $this->Keys['16'], 'message' => $this->Err[16]]);
            }

            //取得菜單
            $Menu = $this->RestaurantServiceV2->GetMenu($Rid, $OffsetLimit);
            $Restaurantinfo = $this->RestaurantServiceV2->GetRestaurantinfo($Rid);

            //檢查是否有登入
            $Token = $Request->header('Authorization');
            if ($Token) {
                $TokenCheck = $this->TotalService->CheckToken($Token);
                //檢查是否Token正確
                if ($TokenCheck === false) {
                    return response()->json(['Err' => $this->Keys['5'], 'Message' => $this->Err[5]]);
                }
                //是否已存在資料庫,有的話更新時間,沒有則建立紀錄
                $this->RestaurantServiceV2->UpdateOrCreateHistory($Rid);
            }
            return response()->json(['Err' => $this->Keys['0'], 'Message' => $this->Err[0], 'data' => $Restaurantinfo, 'menu' => $Menu]);
        } catch (TokenInvalidException $e) {
            return response()->json(['Err' => $this->Keys['26'], 'Message' => $this->Err[26]]);
        } catch (Throwable $e) {
            return response()->json(['Err' => $this->Keys['26'], 'Message' => $this->Err[26]]);
        }
    }
    public function Comment(Request $Request)
    {
        //規則
        $Ruls = [
            'point' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:25'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $RulsMessage = [
            'point.required' => '必填資料未填',
            'point.integer' => '資料填寫與規格不符',
            'point.between' => '資料填寫與規格不符',
            'comment.required' => '必填資料未填',
            'comment.string' => '必填資料未填',
            'comment.min' => '資料填寫與規格不符',
            'comment.max' => '資料填寫與規格不符'
        ];
        try {
            //驗證輸入數值
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json(['Err' => array_search($Validator->Errors()->first(), $this->Err), 'Message' => $Validator->Errors()->first()]);
            }

            //是否有該餐廳
            $Rid = $Request->rid;
            $HasRestaurant = $this->RestaurantServiceV2->CheckRestaurantInDatabase($Rid);
            if (!$HasRestaurant) {
                return response()->json(['Err' => $this->Keys['16'], 'message' => $this->Err[16]]);
            }

            //評論者是否在此訂餐廳訂過餐且訂單狀態是成功且記錄在24小時內
            $OrderIn24Hour = $this->RestaurantServiceV2->CheckOrderIn24Hour($Rid);
            if (!$OrderIn24Hour) {
                return response()->json(['Err' => $this->Keys['12'], 'message' => $this->Err[12]]);
            }

            //是否第一次評論該餐廳
            $UserFirstComment = $this->RestaurantServiceV2->CheckUserFirstComment($Rid);
            if ($UserFirstComment) {
                return response()->json(['Err' => $this->Keys['14'], 'message' => $this->Err[14]]);
            }
            //將評論存入資料庫
            $Comment = ['rid' => $Request->rid, 'comment' => $Request->comment, 'point' => $Request->point];
            $this->RestaurantServiceV2->SaveComment($Comment);
            return response()->json(['Err' => $this->Keys['0'], 'message' => $this->Err[0]]);
        } catch (Exception $e) {
            return response()->json(['Err' => $this->Keys['26'], 'message' => $this->Err[26]]);
        } catch (Throwable $e) {
            return response()->json(['Err' => $this->Keys['26'], 'message' => $this->Err[26]]);
        }
    }
    public function GetComment(Request $Request)
    {
        //規則
        $Ruls = [
            'limit' => ['integer'],
            'offset' => ['integer'],
            'rid' => ['required', 'integer'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $RulsMessage = [
            'limit.integer' => '無效的範圍',
            'offset.integer' => '無效的範圍',
            'rid.integer' => '無效的範圍',
            'rid.required' => '必填資料未填',
        ];
        try {
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json(['Err' => array_search($Validator->Errors()->first(), $this->Err), 'Message' => $Validator->Errors()->first()]);
            }

            //取得OffsetLimit
            $OffsetLimit = ['limit' => $Request['limit'], 'offset' => $Request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);

            //是否有該餐廳 
            $Rid = $Request->rid;
            $RestaurantExist = $this->RestaurantServiceV2->CheckRestaurantInDatabase($Rid);
            if (!$RestaurantExist) {
                return response()->json(['Err' => $this->Keys[16], 'message' => $this->Err[16]]);
            }

            //取出評論
            $RestaurantComment = $this->RestaurantServiceV2->GetRestaurantComment($Rid, $OffsetLimit);

            //計算評論數量
            $RestaurantCommentCount = $RestaurantComment->count();

            //排序取出評論
            $RestaurantComment = $RestaurantComment->sortByDesc('created_at')->values()->all();
            return response()->json(['Err' => $this->Keys[0], 'message' => $this->Err[0], 'count' => $RestaurantCommentCount, 'Comment' => $RestaurantComment]);
        } catch (Exception $e) {
            return response()->json(['Err' => $this->Keys['26'], 'message' => $this->Err[26]]);
        } catch (Throwable) {
            return response()->json(['Err' => $this->Keys['26'], 'message' => $this->Err[26]]);
        }
    }
}
