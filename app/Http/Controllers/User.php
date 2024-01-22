<?php

namespace App\Http\Controllers;

use App\Services\Restaurant;
use App\Services\User as UserService;
use App\Services\UserWallet;
use App\Services\Token;
use App\Services\UserRecord;
use App\Services\UserFavorite;
use App\Services\RestaurantHistory;
use App\Services\ErrorCode;
use Exception;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class User extends Controller
{
    private $userService;
    private $err = [];
    private $keys = [];
    private $tokenService;
    public function __construct(
        Token $tokenService,
        UserService $userService,
        ErrorCode $errorCodeService,
    ) {
        $this->tokenService = $tokenService;
        $this->userService = $userService;
        $this->keys = $errorCodeService->getErrKey();
        $this->err = $errorCodeService->getErrCode();
    }
    public function createUser(Request $request)
    {
        try {
            //規則
            $ruls = [
                'name' => ['required', 'string', 'max:25', 'min:3'],
                'email' => ['required', 'string', 'min:15', 'max:50', 'email'],
                'password' => ['required', 'min:10', 'max:25', 'string'],
                'phone' => ['required', 'string', 'size:9', 'digits_between:1,9'],
                'address' => ['required', 'string', 'min:10', 'max:25'],
                'age' => ['required', 'before:' . Carbon::now()->subYears(12)->format('Y-m-d'), 'date'],
            ];
            //什麼錯誤報什麼錯誤訊息
            $rulsMessage = [
                'name.required' => '資料填寫與規格不符',
                'name.max' => '資料填寫與規格不符',
                'name.min' => '資料填寫與規格不符',
                'name.string' => '資料填寫與規格不符',
                'email.required' => '必填資料未填',
                'email.min' => '資料填寫與規格不符',
                'email.max' => '資料填寫與規格不符',
                'email.email' => '資料填寫與規格不符',
                'email.string' => '資料填寫與規格不符',
                'password.required' => '必填資料未填',
                'password.min' => '資料填寫與規格不符',
                'password.max' => '資料填寫與規格不符',
                'password.string' => '資料填寫與規格不符',
                'phone.required' => '必填資料未填',
                'phone.string' => '資料填寫與規格不符',
                'phone.size' => '資料填寫與規格不符',
                'phone.digits_between' => '資料填寫與規格不符',
                'address.required' => '必填資料未填',
                'address.min' => '資料填寫與規格不符',
                'address.string' => '資料填寫與規格不符',
                'address.max' => '資料填寫與規格不符',
                'age.required' => '必填資料未填',
                'age.before' => '資料填寫與規格不符',
                'age.date' => '資料填寫與規格不符',
            ];
            //驗證輸入
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json([
                    'err' => array_search($validator->Errors()->first(), $this->err),
                    'message' => $validator->Errors()->first()
                ]);
            }
            //檢查email是否重複
            $email = $request['email'];
            $where = ['email', '=', $email];
            $eamilRepeat = $this->userService->getList($where);
            if ($eamilRepeat) {
                return response()->json([
                    'err' => $this->keys[3],
                    'message' => $this->err[3],
                ]);
            }
            //檢查電話是否重複
            $where = ['phone', '=', $request['phone']];
            $phone = $this->userService->getList($where);
            if ($phone) {
                return response()->json([
                    'err' => $this->keys[4],
                    'message' => $this->err[4],
                ]);
            }
            // 將使用者資訊存入Users
            $userInfo = [
                'email' => $email,
                'name' => $request['name'],
                'phone' => $phone,
                'address' => $request['address'],
                'age' => $request['age']
            ];
            $userInfo['password'] = Hash::make($request['password']);
            $response = $this->userService->create($userInfo);
            if (!$response) {
                return response()->json([
                    'err' => $this->keys[26],
                    'message' => $this->err[26],
                ]);
            }
            // 將使用者資訊存入UserWallet
            $balance = 0;
            $userId = $response->id;
            $userWalletService = new UserWallet();
            $walletResponse = $userWalletService->updateOrCreate($userId, $balance);
            if (!$walletResponse) {
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

    public function login(Request $request)
    {
        try {
            //規則
            $ruls = [
                'email' => ['required', 'string', 'min:15', 'max:50', 'email'],
                'password' => ['required', 'min:10', 'max:25', 'string'],
            ];
            //什麼錯誤報什麼錯誤訊息
            $rulsMessage = [
                'email.required' => '必填資料未填',
                'email.min' => '資料填寫與規格不符',
                'email.string' => '資料填寫與規格不符',
                'email.max' => '資料填寫與規格不符',
                'email.email' => '資料填寫與規格不符',
                'password.required' => '必填資料未填',
                'password.min' => '資料填寫與規格不符',
                'password.max' => '資料填寫與規格不符',
                'password.string' => '資料填寫與規格不符',
            ];
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json([
                    'err' => array_search($validator->Errors()->first(), $this->err),
                    'message' => $validator->Errors()->first()
                ]);
            }
            //檢查是否重複登入
            $token = $request->header('Authorization');
            $email = $request['email'];
            if ($token) {
                $alreadyLogin = $this->tokenService->checkToken($email);
                if (!$alreadyLogin) {
                    return response()->json([
                        'err' => $this->keys[26],
                        'message' => $this->err[26]
                    ]);
                }
            }
            //檢查此組Key是否一定時間內登入多次            
            $ip = $request->ip();
            if (RateLimiter::tooManyAttempts(Str::lower($email) . '|' . $ip, 5)) {
                return response()->json(['err' => $this->err['7']]);
            }
            //驗證帳號密碼
            $where = ['email', '=', $email];
            $user = $this->userService->getList($where);

            if (!$user || !password_verify($request['password'], $user['password'])) {
                RateLimiter::hit(Str::lower($email) . '|' . $ip, 60);
                return response()->json([
                    'err' => $this->keys[8],
                    'message' => $this->err[8]
                ]);
            }
            //將登入記入存入資料庫
            $userId = $user['id'];
            $device = $request->header('User-Agent');
            $time = date('Y-m-d H:i:s', time());
            $recordInfo = [
                'uid' => $userId,
                'login' => $time,
                'ip' => $ip,
                'device' => $device,
                'email' => $email,
            ];
            $userRecordService = new UserRecord();
            $userRecordService->create($recordInfo);
            //製做token
            $time = Carbon::now()->addDay();
            $tokenInfo = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'time' => $time];
            $token = $this->tokenService->create($tokenInfo);
            return response()->json([
                'err' => $this->keys[0],
                'message' => $this->err[0],
                'token' => $token
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

    public function logout()
    {
        try {
            $email = $this->tokenService->getEamil();
            $logout = $this->tokenService->forget($email);
            if (!$logout) {
                return response()->json([
                    'err' => $this->keys[10],
                    'message' => $this->err[10],
                ]);
            }
            return response()->json([
                'err' => $this->keys[0],
                'message' => $this->err[0],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'err' => $this->keys[10],
                'message' => $this->err[10],
                'other_err' => $e->getMessage()
            ]);
        }
    }

    public function getProfile()
    {
        try {
            $email = $this->tokenService->getEamil();
            $where = ['email', '=', $email];
            $userInfo = $this->userService->getList($where);
            return response()->json([
                'err' => $this->keys[0],
                'message' => $this->err[0],
                'user_info' => [
                    'email' => $userInfo['email'],
                    'name' => $userInfo['name'],
                    'address' => $userInfo['address'],
                    'phone' => $userInfo['phone'],
                    'age' => $userInfo['age']
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'err' => $this->keys[10],
                'message' => $this->err[10],
                'other_err' => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'err' => $this->keys[10],
                'message' => $this->err[10],
                'other_err' => $e->getMessage()
            ]);
        }
    }

    public function getRecord(Request $request)
    {
        try {
            //規則
            $ruls = [
                'limit' => ['integer'],
                'offset' => ['integer'],
            ];
            //什麼錯誤報什麼錯誤訊息
            $rulsMessage = [
                'limit.integer' => '無效的範圍',
                'offset.integer' => '無效的範圍',
            ];
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json([
                    'err' => array_search($validator->Errors()->first(), $this->err),
                    'message' => $validator->Errors()->first()
                ]);
            }
            //取得offset&limit預設
            $offset = $request['offset'] ?? 0;
            $limit = $request['limit'] ?? 20;
            //取得登入紀錄
            $userId = $this->tokenService->getUserId();
            $userRecordService = new UserRecord();
            $where = [
                'uid',
                '=',
                $userId,
            ];
            $option = [
                'column' => ['ip', 'device', 'login'],
                'offset' => $offset,
                'limit' => $limit,
            ];
            $recordList = $userRecordService->getList($where, $option);
            $count = count($recordList);
            return response()->json([
                'err' => $this->keys[0],
                'message' => $this->err[0],
                'count' => $count,
                'record_list' => $recordList
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

    public function addFavorite(Request $request)
    {
        try {
            //規則
            $ruls = [
                'rid' => ['required', 'integer'],
            ];
            //什麼錯誤報什麼錯誤訊息
            $rulsMessage = [
                'rid.required' => '必填資料未填',
                'rid.integer' => '無效的範圍',
            ];
            //驗證輸入
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json([
                    'err' => array_search($validator->Errors()->first(), $this->err),
                    'message' => $validator->Errors()->first()
                ]);
            }
            //餐廳是否存在且啟用
            $rid = $request['rid'];
            $restaurantService = new Restaurant();
            $restaurant = $restaurantService->get($rid);
            if (!$restaurant || $restaurant['enable'] != 1) {
                return response()->json([
                    'err' => $this->keys[16],
                    'message' => $this->err[16],
                ]);
            }
            //檢查使用者我的最愛資料表內是否超過20筆
            $userId = $this->tokenService->getUserId();
            $userFavoriteService = new UserFavorite();
            $where = ['uid', '=', $userId];
            $userFavorite = $userFavoriteService->getList($where);
            $count = count($userFavorite);
            if ($count >= 20) {
                return response()->json([
                    'err' => $this->keys[28],
                    'message' => $this->err[28],
                ]);
            }
            //檢查是否重複新增我的最愛
            $favoriteRids = array_column($userFavorite, 'rid');
            if (in_array($rid, $favoriteRids)) {
                return response()->json([
                    'err' => $this->keys[15],
                    'message' => $this->err[15],
                ]);
            }
            //新增至我得最愛
            $response = $userFavoriteService->create($userId, $rid);
            if ($response) {
                return response()->json([
                    'err' => $this->keys[0],
                    'message' => $this->err[0],
                ]);
            }
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
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

    public function getFavorite()
    {
        try {
            //取的我的最愛
            $userId = $this->tokenService->getUserId();
            $userFavoriteService = new UserFavorite();
            $where = ['uid', '=', $userId];
            $userFavorite = $userFavoriteService->getList($where);
            $favoriteRids = array_column($userFavorite, 'rid');
            $reataurantService = new Restaurant();
            $userFavorite = $reataurantService->getList($favoriteRids);
            $count = count($userFavorite);
            $response = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'total_point' => $item['totalpoint'],
                    'count_point' => $item['countpoint'],
                    'title' => $item['title'],
                    'img' => $item['img']
                ];
            }, $userFavorite);
            return response()->json([
                'err' => $this->keys[0],
                'message' => $this->err[0],
                'count' => $count,
                'data' => $response
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

    public function deleteFavorite(Request $request)
    {
        try {
            //規則
            $ruls = [
                'rid' => ['required', 'integer'],
            ];
            //什麼錯誤報什麼錯誤訊息
            $rulsMessage = [
                'rid.required' => '必填資料未填',
                'rid.integer' => '無效的範圍',
            ];
            //驗證輸入
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json([
                    'err' => array_search($validator->Errors()->first(), $this->err),
                    'message' => $validator->Errors()->first()
                ]);
            }
            //檢查此餐廳是否存在我的最愛內
            $rid = $request['rid'];
            $userId = $this->tokenService->getUserId();
            $userFavoriteService = new UserFavorite();
            $where = ['uid', '=', $userId];
            $userFavorite = $userFavoriteService->getList($where);
            $favoriteRids = array_column($userFavorite, 'rid');
            if (!in_array($rid, $favoriteRids)) {
                return response()->json([
                    'err' => $this->keys[16],
                    'message' => $this->err[16]
                ]);
            }
            //將此餐廳從我的最愛內刪除
            $response = $userFavoriteService->delete($userId, $rid);
            if ($response) {
                return response()->json([
                    'err' => $this->keys[0],
                    'message' => $this->err[0]
                ]);
            }
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26]
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

    public function getResaturantHistory()
    {
        try {
            //取出歷史紀錄餐廳的資訊
            $userId = $this->tokenService->getUserId();
            $restaurantHistoryService = new RestaurantHistory();
            $restaurantHistory = $restaurantHistoryService->getList($userId);
            $rids = array_column($restaurantHistory, 'rid');
            $restaurantService = new Restaurant();
            $restaurantInfo = $restaurantService->getList($rids);
            $response = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'total_point' => $item['totalpoint'],
                    'count_point' => $item['countpoint'],
                    'title' => $item['title'],
                    'img' => $item['img']
                ];
            }, $restaurantInfo);
            $count = count($response);
            return response()->json([
                'err' => $this->keys[0],
                'message' => $this->err[0],
                'count' => $count,
                'data' => $response
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
