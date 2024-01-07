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

// - PROTECTED

Route::group([
    'middleware'=> ['auth:api'],
], function(){
    Route::post('setMemberBasicInfo', [ApiController::class,'setMemberBasicInfo']);
    Route::post('setMemberGeneralInfo', [ApiController::class,'setMemberGeneralInfo']);
    Route::post('setMemberFinancialInfo', [ApiController::class,'setMemberFinancialInfo']);
    

    Route::get('profile', [ApiController::class,'profile']);
    Route::get('refresh', [ApiController::class,'refreshToken']);
    Route::get('logout', [ApiController::class,'logout']);
    
});
