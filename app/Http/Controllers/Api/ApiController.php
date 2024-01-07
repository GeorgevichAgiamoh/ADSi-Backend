<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiController extends Controller
{
    //Register API (POST, formdata)
    public function register(Request $request){
        //Data validation
        $request->validate([
            "memid"=>"required|unique:users",
            "email"=> "nullable|email|unique:users",
            "password"=> "required",
        ]);
        //Save Data to DB
        User::create([
            "memid"=> $request->memid,
            "email"=> $request->email,
            "password"=> bcrypt($request->password),
        ]);
        // Respond
        return response()->json([
            "status"=> "success",
            "message"=> "User created successfully"
        ]);
    }

    //Login API (POST, formdata)
    public function login(Request $request){
        //Data validation
        $request->validate([
            "memid"=>"nullable",
            "email"=> "nullable|email",
            "password"=> "required",
        ]);
        $mid = $request->memid;
        $eml = $request->email;
        if(!empty($mid) || !empty($eml)){
            //JWT Auth
            $token = !empty($mid)? JWTAuth::attempt([
                "memid"=> $mid,
                "password"=> $request->password,
            ]):JWTAuth::attempt([
                "email"=> $eml,
                "password"=> $request->password,
            ]);
            if(!empty($token)){
                return response()->json([
                    "status"=> "success",
                    "message"=> "User login successfully",
                    "token"=> $token,
                ]);
            }
        }
        
        // Respond
        return response()->json([
            "status"=> "failed",
            "message"=> "Invalid login details",
        ]);
    }

    //---Protected from here

    //Profile API (POST)
    public function setMemberBasicInfo(Request $request){
        $request->validate([
            "memid"=>"required",
            "fname"=> "required",
            "lname"=> "required",
            "mname"=> "nullable",
            "eml"=> "nullable|email",
            "phn"=> "required",
        ]);
    }

    //Profile API (POST)
    public function setMemberGeneralInfo(Request $request){
        $request->validate([
            "memid"=>"required",
            "sex"=> "required",
            "marital"=> "required",
            "dob"=> "required",
            "nationality"=> "required",
            "state"=> "required",
            "lga"=> "required",
            "town"=> "required",
            "addr"=> "required",
            "job"=> "required",
            "nin"=> "required",
            "kin_fname"=> "required",
            "kin_lname"=> "required",
            "kin_mname"=> "required",
            "kin_type"=> "required",
            "kin_phn"=> "required",
            "kin_addr"=> "required",
            "kin_eml"=> "required",
        ]);
    }
    
    //Profile API (POST)
    public function setMemberFinancialInfo(Request $request){
        $request->validate([
            "memid"=>"required",
            "bnk"=> "required",
            "anum"=> "required",
        ]);
    }

    //Profile API (GET)
    public function profile(){
        
    }

    //Refresh Token API (GET)
    public function refreshToken(){
        try {
            $newToken = JWTAuth::refresh();
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            // Handle the invalid token exception
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            // Handle other JWT exceptions
        }
    }

    //Logout API (GET)
    public function logout(){
        
    }

}
