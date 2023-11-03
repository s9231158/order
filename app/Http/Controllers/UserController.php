<?php

namespace App\Http\Controllers;

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
use PDOException;

class UserController extends Controller
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

    public function user(Request $request)
    {
        //規則
        $ruls = [
            'name' => ['required', 'max:25', 'min:3', 'regex:/^[A-Za-z0-9\s]+$/'],
            'email' => ['required', 'unique:users,email', 'min:15', 'max:50', 'regex:/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i'],
            'password' => ['required', 'min:10', 'max:25', 'regex:/^[A-Za-z0-9]+$/'],
            'phone' => ['required', 'string', 'size:9', 'regex:/^[0-9]+$/', 'unique:users,phone'],
            'address' => ['required', 'min:10', 'max:25'],
            'age' => ['required', 'before:2023-08-08', 'date'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'name.required' => $this->err['2'], 'name.max' => $this->err['1'], 'name.min' => $this->err['1'], 'name.regex' => $this->err['1'],
            'email.required' => $this->err['2'], 'email.unique' => $this->err['3'], 'email.min' => $this->err['1'], 'email.max' => $this->err['1'], 'email.regex' => $this->err['1'],
            'password.required' => $this->err['2'], 'password.min' => $this->err['1'], 'password.max' => $this->err['1'], 'password.regex' => $this->err['1'],
            'phone.required' => $this->err['2'], 'phone.string' => $this->err['1'], 'phone.size' => $this->err['1'], 'phone.regex' => $this->err['1'], 'phone.unique' => $this->err['4'],
            'address.required' => $this->err['2'], 'address.min' => $this->err['1'], 'address.max' => $this->err['1'],
            'age.required' => $this->err['2'], 'age.before' => $this->err['1'], 'age.date' => $this->err['1'],
        ];
        //驗證輸入數值
        $validator = Validator::make($request->all(), $ruls, $rulsMessage);
        //如果有錯回報錯誤訊息
        if ($validator->fails()) {
            return response()->json(['err' => $validator->errors()->first()]);
        }
        //沒錯的話存入資料庫
        else {
            $password = Hash::make($request->input('password'));
            $user = User::create([
                'email' => $request->input('email'),
                'name' => $request->input('name'),
                'password' => $password,
                'phone' => $request->input('phone'),
                'address' => $request->input('address'),
                'age' => $request->input('age'),
            ]);
            //將使用者關聯錢包初始化
            $wallet = new User_wallets();
            $wallet->balance = 0;
            $user->wallet()->save($wallet);
            return response()->json(['err' => $this->err['0']]);
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
            'email.required' => $this->err['2'], 'email.min' => $this->err['1'], 'email.max' => $this->err['1'], 'email.regex' => $this->err['1'],
            'password.required' => $this->err['2'], 'password.min' => $this->err['1'], 'password.max' => $this->err['1'], 'password.regex' => $this->err['1']
        ];
        try {
            //驗證輸入數值
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            //驗證失敗回傳錯誤訊息


            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }



            //從redis檢查key是否超過次數
            if (RateLimiter::tooManyAttempts($this->makekey($request), 5, 1)) {
                return response()->json(['err' => $this->err['7']]);
            }
            //檢查是否有該使用者且密碼符合
            if (Auth::attempt($request->only('email', 'password'))) {
                $email = $request->email;
                $token = $request->header('Authorization');
                $redistoken = Cache::get($email);
                $aa = $redistoken == $token;
                if (Cache::has($email) == true && JWTAuth::parseToken()->authenticate()) {
                    return response()->json(['err' => $this->err['6']]);
                }
                // 清除該key錯誤次數
                RateLimiter::clear($this->makekey($request));
                //取得使用者elequent
                $user = User::find(Auth::user()->id);
                //取得使用者登入資訊
                $device = $request->header('User-Agent');
                $ip = $request->ip();
                $login = date('Y-m-d H:i:s', time());
                //將使用者登入資訊存入該使用者user_recode
                $recode = new User_recode([
                    'login' => $login,
                    'ip' => $ip,
                    'device' => $device,
                ]);
                //取得使用者資訊製作payload
                $id = $user->id;
                $name = $user->name;
                $time = Carbon::now()->addDay();
                $user->recode()->save($recode);
                //payload資訊
                $userClaims = [
                    'id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'exp' => $time
                ];
                //將payload 製成token
                $token = JWTAuth::claims($userClaims)->fromUser($user);

                Cache::put($email, $token, 60*60*24);
                $value = Cache::get($email);

                return response()->json(['err' => $this->err['0'], 'token' => $token]);
            }

            RateLimiter::hit($this->makekey($request));
            return response()->json(['err' => $this->err['8']]);
        } catch (Exception $e) {
            return response()->json(['err' => $this->err['5']]);
        }
    }



    public function logout(Request $request)
    {

        try {
            JWTAuth::parseToken()->authenticate();
            $payload = JWTAuth::getpayload();
            $email = $payload['email'];
            if (Cache::has($email) == true && JWTAuth::parseToken()->authenticate()) {
                Cache::forget($email);
                return response()->json(['err' => $this->err['0']]);
            } else {
                Cache::forget($email);
                return response()->json(['err' => $this->err['0']]);
            }
        } catch (Exception) {
            Cache::forget($email);
            return response()->json(['err' => $this->err['0']]);
        }
    }

    public function profile()
    {
        try {
            $aa = JWTAuth::parseToken()->authenticate();
            $email = $aa->email;
            $name = $aa->name;
            $address = $aa->address;
            $phone = $aa->phone;
            $age = $aa->age;
            if (Cache::has($email) && JWTAuth::parseToken()->authenticate()) {
                $bb = ['err' => $this->err['0'], 'email' => $email, 'name' => $name, 'address' => $address, 'phone' => $phone, 'age' => $age];
                return response()->json($bb);
            } else { {
                    return '幹你娘去登入';
                }
            }
        } catch (Exception) {
            return response()->json(['err' => $this->err['5']]);
        }
    }

    public function recode(Request $request)
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
            //驗證jwt是否被串改
            if (JWTAuth::parseToken()->authenticate()) {
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
                if ($limit || $offset) {
                    //驗證輸入數值
                    $validator = Validator::make($request->all(), $ruls, $rulsMessage);
                    //驗證失敗回傳錯誤訊息
                    if ($validator->fails()) {
                        return response()->json(['err' => $validator->errors()->first()]);
                    }
                }
                //取得使用者紀錄
                $user = User::find(Auth::id());
                $recode = $user->recode()->select('ip', 'login', 'device')->offset($offset)->limit($limit)->orderBy('login', 'desc')->get();
                $count = $user->recode()->get()->count();

                return response()->json(['err' => $this->err['0'], 'count' => $count, 'data' => $recode]);
            }
        } catch (Exception) {
            return response()->json(['err' => $this->err['0']]);
        }
    }
     
    protected function makekey(Request $request)
    {
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }
}
