<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API Routes PREFIX = api

// -- OPEN
Route::post('register', [ApiController::class,'register']);
Route::post('login', [ApiController::class,'login']);
Route::post('setFirstAdminUserInfo', [ApiController::class,'setFirstAdminUserInfo']); // For first Admin (call on postman)

// - PROTECTED

Route::group([
    'middleware'=> ['auth:api'],
], function(){
    Route::post('setMemberBasicInfo', [ApiController::class,'setMemberBasicInfo']);
    Route::post('setMemberGeneralInfo', [ApiController::class,'setMemberGeneralInfo']);
    Route::post('setMemberFinancialInfo', [ApiController::class,'setMemberFinancialInfo']);

    Route::post('setAdminUserInfo', [ApiController::class,'setAdminUserInfo']);
    
    Route::get('getMemberBasicInfo/{uid}', [ApiController::class, 'getMemberBasicInfo']);
    Route::get('getMemberGeneralInfo/{uid}', [ApiController::class, 'getMemberGeneralInfo']);
    Route::get('getMemberFinancialInfo/{uid}', [ApiController::class, 'getMemberFinancialInfo']);
    Route::get('getAnnouncements', [ApiController::class, 'getAnnouncements']);

    Route::get('getHighlights', [ApiController::class, 'getHighlights']);

    
    Route::get('refresh', [ApiController::class,'refreshToken']);
    Route::get('logout', [ApiController::class,'logout']);
    Route::get('checkTokenValidity', [ApiController::class,'checkTokenValidity']);
    
});
