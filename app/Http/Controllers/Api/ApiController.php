<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\member_basic_data;
use App\Models\member_financial_data;
use App\Models\member_general_data;
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
        $token = JWTAuth::attempt([
            "memid"=> $request->memid,
            "password"=> $request->password,
        ]);
        if(!empty($token)){
            return response()->json([
                "status"=> true,
                "message"=> "User created successfully",
                "token"=> $token
            ]);
        }
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "User created successfully",
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
            $pld = User::where(!empty($mid)?"memid":"email","=", !empty($mid)?$mid:$eml)->first();
            //JWT Auth
            $token = JWTAuth::attempt([
                "memid"=> $pld->memid,
                "password"=> $request->password,
            ]);
            if(!empty($token)){
                return response()->json([
                    "status"=> true,
                    "message"=> "User login successfully",
                    "token"=> $token,
                    "pld"=> $pld,
                ]);
            }
        }
        
        // Respond
        return response()->json([
            "status"=> false,
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
        member_basic_data::updateOrCreate(
            ["memid"=> $request->memid,],
            [
            "fname"=> $request->fname,
            "lname"=> $request->lname,
            "mname"=> $request->mname,
            "eml"=> $request->eml,
            "phn"=> $request->phn,
        ]);
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "Member Basic Info updated"
        ]);
    }


    public function getMemberBasicInfo($uid){
        
        $pld = member_basic_data::where("memid", $uid)->first();
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "Member Basic Info retrieved",
            "pld"=> $pld,
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
            "kin_mname"=> "nullable",
            "kin_type"=> "required",
            "kin_phn"=> "required",
            "kin_addr"=> "required",
            "kin_eml"=> "required",
        ]);
        member_general_data::updateOrCreate(
            ["memid"=> $request->memid,],
            [
            "sex"=> $request->sex,
            "marital"=> $request->marital,
            "dob"=> $request->dob,
            "nationality"=> $request->nationality,
            "state"=> $request->state,
            "lga"=> $request->lga,
            "town"=> $request->town,
            "addr"=> $request->addr,
            "job"=> $request->job,
            "nin"=> $request->nin,
            "kin_fname"=> $request->kin_fname,
            "kin_lname"=> $request->kin_lname,
            "kin_mname"=> $request->kin_mname,
            "kin_type"=> $request->kin_type,
            "kin_phn"=> $request->kin_phn,
            "kin_addr"=> $request->kin_addr,
            "kin_eml"=> $request->kin_eml,
        ]);
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "Membert General Info updated"
        ]);
    }

    public function getMemberGeneralInfo($uid){
        $pld = member_general_data::where("memid","=", $uid)->first();
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "Membert General Info retrieved",
            "pld"=> $pld,
        ]);
    }
    
    //Profile API (POST)
    public function setMemberFinancialInfo(Request $request){
        $request->validate([
            "memid"=>"required",
            "bnk"=> "required",
            "anum"=> "required",
        ]);
        member_financial_data::updateOrCreate(
            ["memid"=> $request->memid,],
            [
            "bnk"=> $request->bnk,
            "anum"=> $request->anum,
        ]);
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "Membert Financial Info updated"
        ]);
    }

    public function getMemberFinancialInfo($uid){
        $pld = member_financial_data::where("memid","=", $uid)->first();
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "Membert Financial Info retrieved",
            "pld"=> $pld,
        ]);
    }

    //Refresh Token API (GET)
    public function refreshToken(){
        $newToken = auth()->refresh();
        return response()->json([
            "status"=> true,
            "message"=> "New token generated",
            "token"=> $newToken,
        ]);
    }

    //IF reached, token is still valid!, GET
    public function checkTokenValidity(Request $request)
    {
        return response()->json([
            "status"=> true,
            "message"=> "Token OK",
        ]);
    }

    //Logout API (GET)
    public function logout(){
        auth()->logout();
        return response()->json([
            "status"=> true,
            "message"=> "Logout successful",
        ]);
    }

}
