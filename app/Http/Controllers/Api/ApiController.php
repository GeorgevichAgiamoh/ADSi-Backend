<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class ApiController extends Controller
{
    //Register API (POST, formdata)
    public function register(Request $request){
        //Date validation
        $request->validate([
            "memid"=>"required|unique:users",
            "email"=> "email|unique:users",
            "password"=> "required|confirmed",
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
    public function login(){
        
    }

    //---Protected from here

    //Profile API (GET)
    public function profile(){
        
    }

    //Refresh Token API (GET)
    public function refreshToken(){
        
    }

    //Logout API (GET)
    public function logout(){
        
    }

}
