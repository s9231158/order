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
    Route::post('/login', [UserController::class, 'login']);
    Route::get('/logout', [UserController::class, 'logout']);
    Route::post('/user', [UserController::class, 'createUser']);
    Route::get('/user', [UserController::class, 'getProfile'])->middleware('token');
    Route::get('/user/record', [UserController::class, 'getRecord'])->middleware('token');
    Route::post('/user/favorite', [UserController::class, 'addFavorite'])->middleware('token');
    Route::get('/user/favorite', [UserController::class, 'getFavorite'])->middleware('token');
    Route::delete('/user/favorite', [UserController::class, 'deleteFavorite'])->middleware('token');
    Route::get('/user/history', [UserController::class, 'getResaturantHistory'])->middleware('token');

    // RestaurantController routes
    Route::get('/restaurant', [RestaurantController::class, 'getRestaurant']);
    Route::post('/restaurant/comment', [RestaurantController::class, 'addComment'])->middleware('token');
    Route::get('/restaurant/comment', [RestaurantController::class, 'getComment']);
    Route::get('/menu', [RestaurantController::class, 'getMenu']);

    //PayController
    Route::post('/order', [PayController::class, 'createOrder'])->middleware('token');
    Route::post('/ecpayCallBack', [PayController::class, 'ecpayCallBack']);
    Route::get('/order', [PayController::class, 'getOrder'])->middleware('token');
    Route::get('/orderinfo', [PayController::class, 'getOrderInfo'])->middleware('token');

    //WalletController
    Route::post('/money', [Wallet::class, 'AddWalletMoney'])->middleware('token');
    Route::post('/moneycallback', [Wallet::class, 'AddWalletMoneyCallBack']);
    Route::get('/wallet', [Wallet::class, 'GetWallet'])->middleware('token');
    Route::get('/apple', [PayController::class, 'apple']);
});