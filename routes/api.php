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

// Route::controller(UserController::class)->group(function () {
//     Route::post('/login', 'login');
//     Route::get('/logout', 'logout');
//     Route::post('/user', 'create');
//     Route::get('/user', 'profile')->middleware('token');
//     Route::get('/user/record', 'record')->middleware('token');
//     Route::post('/user/favorite', 'favorite')->middleware('token');
//     Route::get('/user/favorite', 'getfavorite')->middleware('token');
//     Route::delete('/user/favorite', 'deletefavorite')->middleware('token');
//     Route::get('/user/history', 'history')->middleware('token');
// })->middleware('Maintenance');

// Route::controller(RestaurantController::class)->group(function () {
//     Route::get('/restaurant', 'restaurant');
//     Route::post('/restaurant/comment', 'comment')->middleware('token');
//     Route::get('/restaurant/comment', 'getcomment');
//     Route::get('/menu', 'menu');
//     Route::get('test', 'test');
// })->middleware('Maintenance');
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
    Route::get('/restaurant', [RestaurantController::class, 'restaurant']);
    Route::post('/restaurant/comment', [RestaurantController::class, 'comment'])->middleware('token');
    Route::get('/restaurant/comment', [RestaurantController::class, 'getcomment']);
    Route::get('/menu', [RestaurantController::class, 'menu']);
    Route::get('test', [RestaurantController::class, 'test']);


    //PayController
    Route::post('/otherpay', [PayController::class, 'otherpay'])->middleware('token');
    Route::get('/tt', [PayController::class, 'tt']);
    Route::post('/ecpayCallBack', [PayController::class, 'EcpayCallBack']);
    Route::get('order', [PayController::class, 'order'])->middleware('token');
    Route::get('orderinfo', [PayController::class, 'orderinfo'])->middleware('token');
    Route::post('money', [PayController::class, 'AddWalletMoney']);
    Route::post('/moneycallback', [PayController::class, 'moneycallback']);
    Route::get('wallet', [PayController::class, 'wallet'])->middleware('token');
    Route::get('apple', [PayController::class, 'apple']);

});




// Route::group(['middleware' => 'Maintenance'], function () {
//     Route::post('/otherpay', [PayController::class, 'otherpay'])->middleware('token');
//     Route::get('/tt', [PayController::class, 'tt']);
//     Route::post('/qwe', [PayController::class, 'qwe']);
//     Route::get('order', [PayController::class, 'order'])->middleware('token');
//     Route::get('orderinfo', [PayController::class, 'orderinfo'])->middleware('token');
//     Route::post('money', [PayController::class, 'AddWalletMoney']);
//     Route::post('/moneycallback', [PayController::class, 'moneycallback']);
//     Route::get('wallet', [PayController::class, 'wallet'])->middleware('token');
//     Route::get('apple', [PayController::class, 'apple']);
// });
