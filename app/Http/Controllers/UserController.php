<?php

namespace App\Http\Controllers;

use App\CustomerService;
use App\ErrorCodeService;
use App\TotalService;
use App\UserInterface\LoginInterface;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\User_recode;
use App\Models\User_wallets;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;
use PDOException;
use App\UserService;
use App\Models\Restaurant;
use App\UserService\CreateService;
use App\UserRepository\CreateRepository;
use App\UserInterface\CreateInrerface;


class UserController extends Controller
{
    //錯誤訊息統整
    private $err = [];
    private $keys = [];

    private $CreatrService = [];
    private $LoginService = [];
    public function __construct(ErrorCodeService $ErrorCodeService, LoginInterface $LoginService, CreateInrerface $CreatrService, )
    {
        $this->keys = $ErrorCodeService->GetErrKey();
        $this->err = $ErrorCodeService->GetErrCode();
        $this->CreatrService = $CreatrService;
        $this->LoginService = $LoginService;
    }
    public function create(Request $request)
    {
        // // 規則
        // $ruls = [
        //     'name' => ['required', 'max:25', 'min:3'],
        //     'email' => ['required', 'unique:users,email', 'min:15', 'max:50', 'regex:/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i'],
        //     'password' => ['required', 'min:10', 'max:25', 'regex:/^[A-Za-z0-9]+$/'],
        //     'phone' => ['required', 'string', 'size:9', 'regex:/^[0-9]+$/', 'unique:users,phone'],
        //     'address' => ['required', 'min:10', 'max:25'],
        //     'age' => ['required', 'before:2023-08-08', 'date'],
        // ];
        // // 什麼錯誤報什麼錯誤訊息
        // $rulsMessage = [
        //     'name.required' => $this->keys[1],
        //     'name.max' => $this->keys[1],
        //     'name.min' => $this->keys[1],
        //     'email.required' => $this->keys[2],
        //     'email.unique' => $this->keys[3],
        //     'email.min' => $this->keys[1],
        //     'email.max' => $this->keys[1],
        //     'email.regex' => $this->keys[1],
        //     'password.required' => $this->keys[2],
        //     'password.min' => $this->keys[1],
        //     'password.max' => $this->keys[1],
        //     'password.regex' => $this->keys[1],
        //     'phone.required' => $this->keys[2],
        //     'phone.string' => $this->keys[1],
        //     'phone.size' => $this->keys[1],
        //     'phone.regex' => $this->keys[1],
        //     'phone.unique' => $this->keys[4],
        //     'address.required' => $this->keys[2],
        //     'address.min' => $this->keys[1],
        //     'address.max' => $this->keys[1],
        //     'age.required' => $this->keys[2],
        //     'age.before' => $this->keys[1],
        //     'age.date' => $this->keys[1],
        // ];
        try {
            // //驗證輸入數值
            // $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            // //如果有錯回報錯誤訊息
            // if ($validator->fails()) {
            //     return response()->json(['err' => $validator->errors()->first(), 'message' => $this->err[$validator->errors()->first()]]);
            // }

            //驗證
            $validator = $this->CreatrService->CreateValidator($request);
            if ($validator != null) {
                return $validator;
            }
            //沒錯的話存入資料庫
            else {
                //Request將資料取出
                $UserInfo = [
                    'password' => $request->password,
                    'email' => $request->email,
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'age' => $request->age
                ];
                // 將使用者資訊存入Users
                if ($this->CreatrService->CreateUser($UserInfo) === null) {
                    return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
                }
                // 將使用者資訊存入UserWallet
                if ($this->CreatrService->CreateWallet($UserInfo['email']) === null) {
                    return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
                }
                return response()->json(['err' => $this->keys[0], 'message' => $this->err[0]]);
            }
        } catch (Exception $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        } catch (Throwable $e) {
            return response()->json([$e, 'err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }



    public function login(Request $request)
    {
        //規則
        $ruls = [
            'email' => ['required', 'min:15', 'max:50', 'regex:/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i'],
            'password' => ['required', 'min:10', 'max:25', 'regex:/^[A-Za-z0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'email.required' => $this->keys[2],
            'email.min' => $this->keys[1],
            'email.max' => $this->keys[1],
            'email.regex' => $this->keys[1],
            'password.required' => $this->keys[2],
            'password.min' => $this->keys[1],
            'password.max' => $this->keys[1],
            'password.regex' => $this->keys[1]
        ];
        try {
            $ip = $request->ip();
            $email = $request->email;
            $token = $request->header('Authorization');
            $MakeKeyInfo = ['ip' => $request->ip(), 'email' => $email, 'count' => 5];

            // //驗證輸入
            // $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            // //驗證失敗回傳錯誤訊息
            // if ($validator->fails()) {
            //     return response()->json(['err' => $validator->errors()->first(), 'message' => $this->err[$validator->errors()->first()]]);
            // }
            //new 驗證輸入

            $validator = $this->LoginService->LoginValidator($request);
            if ($validator !== true) {
                return $validator;
            }

            //檢查此組Key是否一定時間內登入多次
            // if (RateLimiter::tooManyAttempts($this->makekey($email, $ip), 5)) {
            //     return response()->json(['err' => $this->keys[7], 'message' => $this->err[7]]);
            // }

            //new 檢查此組Key是否一定時間內登入多次
            $CheckTooManyAttempts = $this->LoginService->LoginCheckTooManyAttempts($MakeKeyInfo);
            if ($CheckTooManyAttempts !== true) {
                return $CheckTooManyAttempts;
            }


            //檢查是否有重複登入
            // $TotalService = new TotalService;
            // $token = $request->header('Authorization');
            // $redietoken = 'Bearer ' . Cache::get($email);
            // if (Cache::has($email) && $token === $redietoken) {
            //     return response()->json(['err' => $this->err['6']]);
            // }
            //new 檢查是否有重複登入
            $TokenEmail = ['Token' => $request->header('Authorization'), 'Email' => $email];
            $CheckHasLogin = $this->LoginService->CheckHasLogin($TokenEmail);
            if ($CheckHasLogin !== true) {
                return $CheckHasLogin;
            }


            //驗證帳號密碼
            // if (Auth::attempt($request->only('email', 'password'))) {

            //new 驗證帳號密碼
            $EmailPassword = ['email' => $email, 'password' => $request->password];
            $LoginCheckAccountPassword = $this->LoginService->LoginCheckAccountPassword($EmailPassword);
            if ($LoginCheckAccountPassword !== true) {
                return $LoginCheckAccountPassword;
            }
            
            //清除該key錯誤次數
            // RateLimiter::clear($this->makekey($email, $ip));


            //取得使用者elequent
            $user = User::find(Auth::user()->id);
            //取得使用者登入資訊
            $device = $request->header('User-Agent');
            $login = date('Y-m-d H:i:s', time());
            // //將使用者登入資訊存入該使用者user_recode
            // $recode = new User_recode([
            //     'login' => $login,
            //     'ip' => $ip,
            //     'device' => $device,
            // ]);
            // $user->recode()->save($recode);


            //new 將使用者登入資訊存入該使用者user_recode
            $RocordInfo = [
                'login' => $login,
                'ip' => $ip,
                'device' => $device
            ];
            $CreatrLoginRecord = $this->LoginService->CreatrLoginRecord($RocordInfo);
            if ($CreatrLoginRecord !== true) {
                return $CreatrLoginRecord;
            }




            //取得使用者資訊製作payload
            $id = $user->id;
            $name = $user->name;
            $time = Carbon::now()->addDay();
            //payload資訊
            $userClaims = [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'exp' => $time
            ];



            //將payload 製成token
            $token = JWTAuth::claims($userClaims)->fromUser($user);
            //設定redis內存活時間
            Cache::put($email, $token, 60 * 60 * 24);



            // return response()->json(['err' => $this->err['0'], 'token' => $token]);
            // }
            // 對這個email 錯誤次數+1
            $CreateToken = $this->LoginService->CreateToken();
            if ($CreateToken === true) {
                return response()->json(['err' => $this->keys[0], 'message' => $this->err[0], 'token' => $token]);
            }

            RateLimiter::hit($this->makekey($email, $ip));
            return $CreateToken;
            // return response()->json(['err' => $this->err['8']]);
        } catch (Exception $e) {
            return response()->json([$e, 'err' => $this->keys[5], 'message' => $this->err[5]]);
        } catch (Throwable $e) {
            return response()->json([$e, 'err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }



    public function logout(Request $request)
    {

        try {
            JWTAuth::parseToken()->authenticate();
            $payload = JWTAuth::getpayload();
            $email = $payload['email'];
            $token = $request->header('Authorization');
            $redietoken = 'Bearer ' . Cache::get($email);
            if (Cache::has($email) && $token == $redietoken) {
                Cache::forget($email);
                return response()->json(['err' => $this->err['0']]);
            } else {
                Cache::forget($email);
                return response()->json(['err' => $this->err['0']]);
            }
        } catch (Exception) {
            Cache::forget($email);
            return response()->json(['err' => $this->err['0']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }

    public function profile()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $email = $user->email;
            $name = $user->name;
            $address = $user->address;
            $phone = $user->phone;
            $age = $user->age;
            $result = ['err' => $this->err['0'], 'email' => $email, 'name' => $name, 'address' => $address, 'phone' => $phone, 'age' => $age];
            return response()->json($result);
        } catch (Exception) {
            return response()->json(['err' => $this->err['5']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }

    public function record(Request $request)
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
            //驗證輸入數值
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            //驗證失敗回傳錯誤訊息
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }
            //取得使用者紀錄
            $user = User::find(Auth::id());
            $recode = $user->recode()->select('ip', 'login', 'device')->offset($offset)->limit($limit)->orderBy('login', 'desc')->get();
            $count = $user->recode()->get()->count();
            return response()->json(['err' => $this->err['0'], 'count' => $count, 'data' => $recode]);
        } catch (Exception) {
            return response()->json(['err' => $this->err['26']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }


    public function favorite(Request $request)
    {
        try {
            // $a = JWTAuth::parseToken()->authenticate();
            // $email = $a->email;
            $rid = $request->rid;
            $user = User::find(Auth::id());
            $databaserestruant = Restaurant::where('id', '=', $rid)->count();
            //餐廳資料表內是否有該rid
            if ($databaserestruant === 0) {
                return response()->json(['err' => $this->err['16']]);
            }
            $count = $user->favorite()->count();
            //該使用者我的最愛資料表內是否超過20筆
            if ($count >= 20) {
                return response()->json(['err' => $this->err['28']]);
            }
            $exzest = $user->favorite()->where('rid', '=', $rid)->get()->count();
            //我的最愛資料表內是否有該rid
            if ($exzest === 0) {
                $user->favorite()->attach($rid);
                return response()->json(['err' => $this->err['0']]);
            } else {
                return response()->json(['err' => $this->err['15']]);
            }
        } catch (PDOException $e) {
            return response()->json(['err' => $this->err['26']]);
        } catch (Exception $e) {
            return response()->json(['err' => $this->err['26']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }

    public function getfavorite(Request $request)
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

            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            //驗證失敗回傳錯誤訊息
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }
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
            $result = $user->favorite()->select('rid', 'totalpoint', 'countpoint', 'title', 'img')->limit($limit)->offset($offset)->orderBy('user_favorites.created_at', 'desc')->get()->map(function ($item) {
                unset($item->pivot);
                return $item;
            });
            return response()->json(['err' => $this->err['0'], 'count' => $count, 'data' => $result]);
        } catch (Exception $e) {
            return response()->json([$e, 'err' => $this->err['26']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
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
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }

    public function history(Request $request)
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

            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            //驗證失敗回傳錯誤訊息
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }
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
            $count = $user->history()->count();
            $result = $user->history()->select('rid', 'totalpoint', 'countpoint', 'title', 'img')->limit($limit)->offset($offset)->orderBy('restaurant_histories.updated_at', 'desc')->get()->map(function ($item) {
                unset($item->pivot);
                return $item;
            });

            return response()->json(['err' => $this->err['0'], 'count' => $count, 'data' => $result]);
        } catch (Exception $e) {
            return $e;
        } catch (Throwable $e) {
            return response()->json([$e, 'err' => $this->err['26']]);
        }
    }


    protected function makekey($email, $ip)
    {
        return Str::lower($email) . '|' . $ip;
    }
}
