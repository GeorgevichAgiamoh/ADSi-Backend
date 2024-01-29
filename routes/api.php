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
Route::post('paystackConf', [ApiController::class,'paystackConf']);
Route::post('sendPasswordResetEmail', [ApiController::class,'sendPasswordResetEmail']);
Route::post('resetPassword', [ApiController::class,'resetPassword']);
// - PROTECTED

Route::group([
    'middleware'=> ['auth:api'],
], function(){
    Route::post('setMemberBasicInfo', [ApiController::class,'setMemberBasicInfo']);
    Route::post('setMemberGeneralInfo', [ApiController::class,'setMemberGeneralInfo']);
    Route::post('setMemberFinancialInfo', [ApiController::class,'setMemberFinancialInfo']);
    Route::post('authAsAdmin', [ApiController::class,'authAsAdmin']);
    Route::post('uploadFile', [ApiController::class,'uploadFile']);

    Route::post('setAdminUserInfo', [ApiController::class,'setAdminUserInfo']);
    Route::post('setAnnouncements', [ApiController::class,'setAnnouncements']);
    Route::post('uploadPayment', [ApiController::class,'uploadPayment']);
    Route::post('setAdsiInfo', [ApiController::class,'setAdsiInfo']);
    Route::post('setAdmin', [ApiController::class,'setAdmin']);
    Route::post('sendMail', [ApiController::class,'sendMail']);
    
    Route::get('getMemberBasicInfo/{uid}', [ApiController::class, 'getMemberBasicInfo']);
    Route::get('getMemberGeneralInfo/{uid}', [ApiController::class, 'getMemberGeneralInfo']);
    Route::get('getMemberFinancialInfo/{uid}', [ApiController::class, 'getMemberFinancialInfo']);
    Route::get('getMemPays/{memid}', [ApiController::class, 'getMemPays']);
    Route::get('getMemDuesByYear/{memid}/{year}', [ApiController::class, 'getMemDuesByYear']);
    Route::get('getFile/{folder}/{filename}', [ApiController::class, 'getFile']);
    Route::get('fileExists/{folder}/{filename}', [ApiController::class, 'fileExists']);
    Route::get('getAnnouncements', [ApiController::class, 'getAnnouncements']);
    Route::get('getFiles/{uid}', [ApiController::class, 'getFiles']);

    Route::get('getHighlights', [ApiController::class, 'getHighlights']);
    Route::get('getVerificationStats', [ApiController::class, 'getVerificationStats']);
    Route::get('getMembersByV/{vstat}', [ApiController::class, 'getMembersByV']);
    Route::get('getPayments/{payId}', [ApiController::class, 'getPayments']);
    Route::get('getAsdiInfo', [ApiController::class, 'getAsdiInfo']);
    Route::get('getAdmins', [ApiController::class, 'getAdmins']);
    Route::get('getAdmin/{adminId}', [ApiController::class, 'getAdmin']);
    Route::get('removeAdmin/{adminId}', [ApiController::class, 'removeAdmin']);

    
    Route::get('refresh', [ApiController::class,'refreshToken']);
    Route::get('logout', [ApiController::class,'logout']);
    Route::get('checkTokenValidity', [ApiController::class,'checkTokenValidity']);
    
});
