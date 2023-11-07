<?php

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


Route::controller(UserController::class)->group(function () {
    Route::post('/login', 'login');
    Route::get('/logout', 'logout');
    Route::post('/user', 'create');
    Route::get('/user', 'profile')->middleware('token');
    Route::get('/user/record', 'record')->middleware('token');
    Route::post('/user/favorite', 'favorite')->middleware('token');
    Route::get('/user/favorite', 'getfavorite')->middleware('token');
    Route::delete('/user/favorite', 'deletefavorite')->middleware('token');
    Route::get('/user/history','history')->middleware('token');
});
Route::controller(RestaurantController::class)->group(function () {
    Route::get('/restaurant', 'restaurant');
    Route::post('/restaurant/comment','comment')->middleware('token');
    Route::get('/restaurant/comment','getcomment');
    Route::get('/menu', 'menu');
    Route::get('test','test');
});
