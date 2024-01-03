<?php

namespace App\Http\Controllers;

use App\Services\ErrorCodeService;
use App\Services\Restaurant as RestaurantService;



// use App\ErrorCodeService;
use App\ServiceV2\Restaurant as RestaurantServiceV2;
use App\TotalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Throwable;

class Restaurant extends Controller
{
    //new
    private $err;
    private $keys;
    private $restaurantService;
    //new




    private $TotalService;
    private $RestaurantServiceV2;
    public function __construct(
        RestaurantServiceV2 $RestaurantServiceV2,
        TotalService $TotalService,


        //new
        RestaurantService $restaurantService,
        ErrorCodeService $errorCodeService,
        //new
    ) {
        $this->RestaurantServiceV2 = $RestaurantServiceV2;
        $this->TotalService = $TotalService;

        //new
        $this->restaurantService = $restaurantService;
        $this->err = $errorCodeService->getErrCode();
        $this->keys = $errorCodeService->getErrKey();
        //new
    }
    public function getRestaurant(Request $request)
    {
        //規則
        $ruls = [
            'limit' => ['integer'],
            'offset' => ['integer']
        ];

        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.integer' => '無效的範圍',
            'offset.integer' => '無效的範圍'
        ];
        try {
            //驗證參輸入數
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json([
                    'err' => array_search($validator->Errors()->first(), $this->err),
                    'message' => $validator->Errors()->first()
                ]);
            }
            //取得OffsetLimit            
            $option['offset'] = $request['offset'] === null ? 0 : $request['offset'];
            $option['limit'] = $request['limit'] === null ? 20 : $request['limit'];
            //取得餐廳info並打亂順序
            $restaurantInfo = $this->restaurantService->getListByRange($option);
            $count = count($restaurantInfo);
            $keys = array_keys($restaurantInfo);
            shuffle($keys);
            foreach ($keys as $key) {
                $new[$key] = $restaurantInfo[$key];
            }
            $shuffle = array_values($new);
            $response = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'img' => $item['img'],
                    'total_point' => $item['totalpoint'],
                    'count_point' => $item['countpoint']];
            }, $shuffle);
            return response()->json([
                'message' => $this->keys[0],
                'err' => $this->err[0],
                'count' => $count,
                'restaurant_list' => $response
            ]);
        } catch (Exception $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        }
    }

    public function getMenu(Request $request)
    {
        //規則
        $ruls = [
            'limit' => ['integer'],
            'offset' => ['integer'],
            'rid' => ['required', 'integer'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.integer' => '無效的範圍',
            'offset.integer' => '無效的範圍',
            'rid.integer' => '無效的範圍',
            'rid.required' => '必填資料未填',
        ];
        try {
            //驗證參輸入數
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json([
                    'err' => array_search($validator->Errors()->first(), $this->err),
                    'message' => $validator->Errors()->first()
                ]);
            }

            //取得OffsetLimit
            $OffsetLimit = ['limit' => $request['limit'], 'offset' => $request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);

            //是否有該餐廳
            $Rid = $request['rid'];
            $HasRestraunt = $this->RestaurantServiceV2->CheckRestaurantInDatabase($Rid);
            if (!$HasRestraunt) {
                return response()->json([
                    'err' => $this->keys[16],
                    'message' => $this->err[16]
                ]);
            }

            //取得菜單
            $Menu = $this->RestaurantServiceV2->GetMenu($Rid, $OffsetLimit);
            $RestaurantInfo = $this->RestaurantServiceV2->GetRestaurantinfo($Rid);

            //檢查是否有登入
            $Token = $request->header('Authorization');
            if ($Token) {
                $TokenCheck = $this->TotalService->CheckToken($Token);
                //檢查是否Token正確
                if ($TokenCheck === false) {
                    return response()->json([
                        'err' => $this->keys[5],
                        'message' => $this->err[5]
                    ]);
                }
                //是否已存在資料庫,有的話更新時間,沒有則建立紀錄
                $this->RestaurantServiceV2->UpdateOrCreateHistory($Rid);
            }
            return response()->json([
                'err' => $this->keys[0],
                'message' => $this->err[0],
                'data' => $RestaurantInfo,
                'menu' => $Menu
            ]);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        }
    }
    public function addComment(Request $request)
    {
        //規則
        $ruls = [
            'point' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:25'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
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
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json([
                    'err' => array_search($validator->Errors()->first(), $this->err),
                    'message' => $validator->Errors()->first()
                ]);
            }

            //是否有該餐廳
            $Rid = $request['rid'];
            $HasRestaurant = $this->RestaurantServiceV2->CheckRestaurantInDatabase($Rid);
            if (!$HasRestaurant) {
                return response()->json([
                    'err' => $this->keys[16],
                    'message' => $this->err[16]
                ]);
            }

            //評論者是否在此訂餐廳訂過餐且訂單狀態是成功且記錄在24小時內
            $OrderIn24Hour = $this->RestaurantServiceV2->CheckOrderIn24Hour($Rid);
            if (!$OrderIn24Hour) {
                return response()->json([
                    'err' => $this->keys[12],
                    'message' => $this->err[12]
                ]);
            }

            //是否第一次評論該餐廳
            $UserFirstComment = $this->RestaurantServiceV2->CheckUserFirstComment($Rid);
            if ($UserFirstComment) {
                return response()->json([
                    'err' => $this->keys[14],
                    'message' => $this->err[14]
                ]);
            }
            //將評論存入資料庫
            $Comment = ['rid' => $request['rid'], 'comment' => $request['comment'], 'point' => $request['point']];
            $this->RestaurantServiceV2->SaveComment($Comment);
            return response()->json([
                'err' => $this->keys[0],
                'message' => $this->err[0]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        }
    }
    public function getComment(Request $request)
    {
        //規則
        $ruls = [
            'limit' => ['integer'],
            'offset' => ['integer'],
            'rid' => ['required', 'integer'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.integer' => '無效的範圍',
            'offset.integer' => '無效的範圍',
            'rid.integer' => '無效的範圍',
            'rid.required' => '必填資料未填',
        ];
        try {
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json([
                    'err' => array_search($validator->Errors()->first(), $this->err),
                    'message' => $validator->Errors()->first()
                ]);
            }

            //取得OffsetLimit
            $OffsetLimit = ['limit' => $request['limit'], 'offset' => $request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);

            //是否有該餐廳 
            $Rid = $request['rid'];
            $RestaurantExist = $this->RestaurantServiceV2->CheckRestaurantInDatabase($Rid);
            if (!$RestaurantExist) {
                return response()->json([
                    'err' => $this->keys[16],
                    'message' => $this->err[16]
                ]);
            }

            //取出評論
            $RestaurantComment = $this->RestaurantServiceV2->GetRestaurantComment($Rid, $OffsetLimit);

            //計算評論數量
            $RestaurantCommentCount = $RestaurantComment->count();

            //排序取出評論
            $RestaurantComment = $RestaurantComment->sortByDesc('created_at')->values()->all();
            return response()->json([
                'err' => $this->keys[0],
                'message' => $this->err[0],
                'count' => $RestaurantCommentCount,
                'Comment' => $RestaurantComment
            ]);
        } catch (Exception $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        }
    }
}