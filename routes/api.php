<?php

use App\Http\Controllers\Pay as PayController;
use App\Http\Controllers\Restaurant as RestaurantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User as UserController;
use App\Http\Controllers\Wallet;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'Maintenance'], function () {
    // UserController routes
    Route::post('/login', [UserController::class, 'Login']);
    Route::get('/logout', [UserController::class, 'LogOut']);
    Route::post('/user', [UserController::class, 'CreateUser']);
    Route::get('/user', [UserController::class, 'GetProfile'])->middleware('token');
    Route::get('/user/record', [UserController::class, 'GetRecord'])->middleware('token');
    Route::post('/user/favorite', [UserController::class, 'AddFavorite'])->middleware('token');
    Route::get('/user/favorite', [UserController::class, 'GetFavorite'])->middleware('token');
    Route::delete('/user/favorite', [UserController::class, 'DeleteFavorite'])->middleware('token');
    Route::get('/user/history', [UserController::class, 'GetHistory'])->middleware('token');

    // RestaurantController routes
    Route::get('/restaurant', [RestaurantController::class, 'GetRestaurant']);
    Route::post('/restaurant/comment', [RestaurantController::class, 'AddComment'])->middleware('token');
    Route::get('/restaurant/comment', [RestaurantController::class, 'GetComment']);
    Route::get('/menu', [RestaurantController::class, 'GetMenu']);

    //PayController
    Route::post('/order', [PayController::class, 'CreateOrder'])->middleware('token');
    Route::post('/ecpayCallBack', [PayController::class, 'EcpayCallBack']);
    Route::get('/order', [PayController::class, 'GetOrder'])->middleware('token');
    Route::get('/orderinfo', [PayController::class, 'GetOrderInfo'])->middleware('token');

    //WalletController
    Route::post('/money', [Wallet::class, 'AddWalletMoney'])->middleware('token');
    Route::post('/moneycallback', [Wallet::class, 'AddWalletMoneyCallBack']);
    Route::get('/wallet', [Wallet::class, 'GetWallet'])->middleware('token');
    Route::get('/apple', [PayController::class, 'apple']);
});