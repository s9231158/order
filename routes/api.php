<?php

use App\Http\Controllers\PayController;
use App\Http\Controllers\RestaurantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

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
    Route::post('/login', [UserController::class, 'login']);
    Route::get('/logout', [UserController::class, 'logout']);
    Route::post('/user', [UserController::class, 'create']);
    Route::get('/user', [UserController::class, 'profile'])->middleware('token');
    Route::get('/user/record', [UserController::class, 'record'])->middleware('token');
    Route::post('/user/favorite', [UserController::class, 'favorite'])->middleware('token');
    Route::get('/user/favorite', [UserController::class, 'getfavorite'])->middleware('token');
    Route::delete('/user/favorite', [UserController::class, 'deletefavorite'])->middleware('token');
    Route::get('/user/history', [UserController::class, 'history'])->middleware('token');

    // RestaurantController routes
    Route::get('/restaurant', [RestaurantController::class, 'Restaurant']);
    Route::post('/restaurant/comment', [RestaurantController::class, 'comment'])->middleware('token');
    Route::get('/restaurant/comment', [RestaurantController::class, 'GetComment']);
    Route::get('/menu', [RestaurantController::class, 'Menu']);

    //PayController
    Route::post('/order', [PayController::class, 'CreateOrder'])->middleware('token');
    Route::post('/ecpayCallBack', [PayController::class, 'EcpayCallBack']);
    Route::get('/order', [PayController::class, 'GetOrder'])->middleware('token');
    Route::get('/orderinfo', [PayController::class, 'GetOrderInfo'])->middleware('token');
    Route::post('/money', [PayController::class, 'AddWalletMoney']);
    Route::post('/moneycallback', [PayController::class, 'AddWalletMoneyCallBack']);
    Route::get('/wallet', [PayController::class, 'GetWallet'])->middleware('token');
    Route::get('/apple', [PayController::class, 'apple']);
});