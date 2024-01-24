<?php

namespace App\Http\Controllers;

use App\Factorise;
use App\Services\ErrorCode;
use App\Services\Order;
use App\Services\Restaurant as RestaurantService;
use App\Services\RestaurantHistory;
use App\Services\ResturantComment;
use App\Services\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Throwable;

class Restaurant extends Controller
{
    private $err;
    private $keys;
    private $restaurantService;
    public function __construct(
        RestaurantService $restaurantService,
        ErrorCode $errorCodeService,
    ) {
        $this->restaurantService = $restaurantService;
        $this->err = $errorCodeService->getErrCode();
        $this->keys = $errorCodeService->getErrKey();
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
            $offset = $request['offset'] ?? 0;
            $limit = $request['limit'] ?? 20;
            //取得餐廳info並打亂順序
            $option = [
                'limit' => $limit,
                'offset' => $offset,
            ];
            $restaurantInfo = $this->restaurantService->getJoinList($option);
            $count = count($restaurantInfo);
            $keys = array_keys($restaurantInfo);
            shuffle($keys);
            foreach ($keys as $key) {
                $result[$key] = $restaurantInfo[$key];
            }
            $shuffle = array_values($result);
            $response = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'img' => $item['img'],
                    'total_point' => $item['totalpoint'],
                    'count_point' => $item['countpoint'],
                    'open' => $item[date('l')]
                ];
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
            $offset = $request['offset'] ?? 0;
            $limit = $request['limit'] ?? 20;
            //是否有該餐廳
            $rid = $request['rid'];
            $restaurantInfo = $this->restaurantService->get($rid);
            if (!$restaurantInfo || $restaurantInfo['enable'] != 1) {
                return response()->json([
                    'message' => $this->keys[16],
                    'err' => $this->err[16],
                ]);
            }
            //取得菜單
            $restaurant = Factorise::setMenu($rid);
            $menu = $restaurant->getMenu($offset, $limit);
            //檢查是否有登入
            $tokenService = new Token();
            $token = $request->header('Authorization');
            if ($token) {
                $email = $tokenService->getEamil();
                $userId = $tokenService->getUserId();
                $tokenCheck = $tokenService->checkToken($email);
                if ($tokenCheck !== true) {
                    return response()->json([
                        'err' => $this->keys[26],
                        'message' => $this->err[26]
                    ]);
                }
                //是否已存在資料庫,有的話更新時間,沒有則建立紀錄
                $restaurantHistoryService = new RestaurantHistory();
                $restaurantHistoryService->updateOrCreate($userId, $rid);
            }
            $response = [
                'id' => $restaurantInfo['id'],
                'total_point' => $restaurantInfo['totalpoint'],
                'count_point' => $restaurantInfo['countpoint'],
                'title' => $restaurantInfo['title'],
                'img' => $restaurantInfo['img']
            ];
            return response()->json([
                'err' => $this->keys[0],
                'message' => $this->err[0],
                'restaurant_info' => $response,
                'menu' => $menu
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
            $rid = $request['rid'];
            $restaurantInfo = $this->restaurantService->get($rid);
            if (!$restaurantInfo || $restaurantInfo['enable'] != 1) {
                return response()->json([
                    'err' => $this->keys[16],
                    'message' => $this->err[16]
                ]);
            }
            //評論者是否在此訂餐廳訂過餐且訂單狀態是成功且記錄在24小時內
            $tokenService = new Token();
            $userId = $tokenService->getUserId();
            $userName = $tokenService->getName();
            $orderData = [
                'where' => ['uid', '=', $userId],
                'option' => [
                    'column' => ['ordertime'],
                    'orderby' => ['ordertime', 'desc'],
                    'limit' => 1
                ]
            ];
            $orderService = new Order();
            $lastOrder = $orderService->getList($orderData['where'], $orderData['option']);
            $yesterday = date("Y-m-d H:i:s", strtotime('-1 day'));
            if ($lastOrder[0]['ordertime'] < $yesterday) {
                return response()->json([
                    'err' => $this->keys[12],
                    'message' => $this->err[12]
                ]);
            }
            //是否第一次評論該餐廳
            $restaurantCommentService = new ResturantComment();
            $comment = $restaurantCommentService->get($rid, $userId);
            if ($comment) {
                return response()->json([
                    'err' => $this->keys[14],
                    'message' => $this->err[14]
                ]);
            }
            //將評論存入資料庫
            $commentInfo = [
                'name' => $userName,
                'uid' => $userId,
                'rid' => $rid,
                'comment' => $request['comment'],
                'point' => $request['point']
            ];
            $response = $restaurantCommentService->create($commentInfo);
            if (!$response) {
                return response()->json([
                    'err' => $this->keys[26],
                    'message' => $this->err[26],
                ]);
            }
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
            $offset = $request['offset'] ?? 0;
            $limit = $request['limit'] ?? 20;
            //是否有該餐廳 
            $rid = $request['rid'];
            $restaurantInfo = $this->restaurantService->get($rid);
            if (!$restaurantInfo || $restaurantInfo['enable'] != 1) {
                return response()->json([
                    'err' => $this->keys[16],
                    'message' => $this->err[16]
                ]);
            }
            //取出評論
            $restaurantCommentService = new ResturantComment();
            $restaurantCommentOption = [
                'limit' => $limit,
                'offset' => $offset,
            ];
            $restaurantComment = $restaurantCommentService->getList($rid, $restaurantCommentOption);
            $restaurantComment = array_map(function ($item) {
                return [
                    'name' => $item['name'],
                    'point' => $item['point'],
                    'comment' => $item['comment'],
                    'created_at' => $item['created_at']
                ];
            }, $restaurantComment);

            $count = count($restaurantComment);
            return response()->json([
                'err' => $this->keys[0],
                'message' => $this->err[0],
                'count' => $count,
                'comment' => $restaurantComment
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
