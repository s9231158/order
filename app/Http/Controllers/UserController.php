<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\User_recode;
use App\Models\User_wallets;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UserController extends Controller
{
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
        //錯誤訊息統整
        $err = [
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
        ];
        //什麼錯誤報什麼錯誤訊息
        $customMessages = [
            'name.required' => $err['2'], 'name.max' => $err['1'], 'name.min' => $err['1'], 'name.regex' => $err['1'],
            'email.required' => $err['2'], 'email.unique' => $err['3'], 'email.min' => $err['1'], 'email.max' => $err['1'], 'email.regex' => $err['1'],
            'password.required' => $err['2'], 'password.min' => $err['1'], 'password.max' => $err['1'], 'password.regex' => $err['1'],
            'phone.required' => $err['2'], 'phone.string' => $err['1'], 'phone.size' => $err['1'], 'phone.regex' => $err['1'], 'phone.unique' => $err['4'],
            'address.required' => $err['2'], 'address.min' => $err['1'], 'address.max' => $err['1'],
            'age.required' => $err['2'], 'age.before' => $err['1'], 'age.date' => $err['1'],
        ];
        //驗證輸入數值
        $validator = Validator::make($request->all(), $ruls, $customMessages);

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
            return response()->json(['err' => 0]);
        }
    }



    public function login(Request $request)
    {
        //規則
        $ruls = [
            'email' => ['required', 'min:15', 'max:50', 'regex:/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i'],
            'password' => ['required', 'min:10', 'max:25', 'regex:/^[A-Za-z0-9]+$/'],
        ];
        //錯誤訊息統整
        $err = [
            //成功
            '0' => 0,
            //資料填寫與規格不符
            '1' => 1,
            //必填資料未填
            '2' => 2,
            //短時間內登入次數過多
            '7' => 7,
            //帳號或密碼錯誤
            '8' => 8
        ];
        //什麼錯誤報什麼錯誤訊息
        $customMessages = [
            'email.required' => $err['2'], 'email.min' => $err['1'], 'email.max' => $err['1'], 'email.regex' => $err['1'],
            'password.required' => $err['2'], 'password.min' => $err['1'], 'password.max' => $err['1'], 'password.regex' => $err['1']
        ];
        //驗證輸入數值
        $validator = Validator::make($request->all(), $ruls, $customMessages);
        //驗證失敗回傳錯誤訊息
        if ($validator->fails()) {
            return response()->json(['err' => $validator->errors()->first()]);
        }




        if (RateLimiter::tooManyAttempts($this->makekey($request), 5, 1)) {
            return response()->json(['err' => $err['7']]);
        }

        if (Auth::attempt($request->only('email', 'password'))) {
            RateLimiter::clear($this->makekey($request));
            $user = User::find(Auth::user()->id);
            $id = $user->id;
            $name = $user->name;
            $email = $request->email;
            $device = $request->header('User-Agent');
            $ip = $request->ip();
            $login = date('Y-m-d H:i:s', time());
            $recode = new User_recode([
                'login' => $login,
                'ip' => $ip,
                'device' => $device,
            ]);
            $user->recode()->save($recode);
            $userClaims = [
                'id' => $id,
                'name' => $name,
                'email' => $email,
            ];
            $time = Carbon::now()->addMinute();
            $token = JWTAuth::claims($userClaims)->fromUser($user);

            Cache::put($email, $token, 1440);
            $value = Cache::get($email);

            // return response()->json(['err' => $value]);

            return response()->json(['err' => $err['0'], $value]);
        }

        RateLimiter::hit($this->makekey($request));
        return response()->json(['err' => $err['8']]);
    }

    protected function makekey(Request $request)
    {
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }
}
