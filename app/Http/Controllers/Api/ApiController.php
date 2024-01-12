<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\admin_user;
use App\Models\announcements;
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
            "phn"=> "nullable|unique:users",
            "password"=> "required",
        ]);
        //Save Data to DB
        User::create([
            "memid"=> $request->memid,
            "phn"=> $request->phn,
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
            "phn"=> "nullable",
            "password"=> "required",
        ]);
        $mid = $request->memid;
        $phn = $request->phn;
        if(!empty($mid) || !empty($eml)){
            $pld = User::where(!empty($mid)?"memid":"phn","=", !empty($mid)?$mid:$phn)->first();
            if($pld){
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
        }
        // Respond
        return response()->json([
            "status"=> false,
            "message"=> "Invalid login details",
        ]);
    }

    //---Protected from here

    public function authAsAdmin(){
        $user = auth()->user();
        $apld = admin_user::where("memid","=", $user->memid)->first();
        if($apld){
            $customClaims = [
                'role'=>$apld->role,
                'pd1' => $apld->pd1, 
                'pd2' => $apld->pd2, 
                'pp1' => $apld->pp1, 
                'pp2' => $apld->pp2, 
                'pm1' => $apld->pm1, 
                'pm2' => $apld->pm2, 
            ];
            $token = JWTAuth::customClaims($customClaims)->fromUser(auth()->user());
            return response()->json([
                "status"=> true,
                "message"=> "Admin authorization granted",
                "token"=> $token,
            ]);
        }
        // Respond
        return response()->json([
            "status"=> false,
            "message"=> "Failed"
        ]);
    }

    //Profile API (POST)
    public function setMemberBasicInfo(Request $request){
        $request->validate([
            "memid"=>"required",
            "fname"=> "required",
            "lname"=> "required",
            "mname"=> "nullable",
            "eml"=> "nullable|email",
            "phn"=> "required",
            "verif"=> "required",
        ]);
        member_basic_data::updateOrCreate(
            ["memid"=> $request->memid,],
            [
            "fname"=> $request->fname,
            "lname"=> $request->lname,
            "mname"=> $request->mname,
            "eml"=> $request->eml,
            "phn"=> $request->phn,
            "verif"=> $request->verif,
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
            "kin_eml"=> $request->kin_eml
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
    

    public function setMemberFinancialInfo(Request $request){
        $request->validate([
            "memid"=>"required",
            "bnk"=> "required",
            "anum"=> "required",
            "aname"=> "required",
        ]);
        member_financial_data::updateOrCreate(
            ["memid"=> $request->memid,],
            [
            "bnk"=> $request->bnk,
            "anum"=> $request->anum,
            "aname"=> $request->aname,
        ]);
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "Success"
        ]);
    }

    public function getMemberFinancialInfo($uid){
        $pld = member_financial_data::where("memid","=", $uid)->first();
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "Success",
            "pld"=> $pld,
        ]);
    }

    //GET
    public function getAnnouncements(){
        $pld = announcements::all();
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "Success",
            "pld"=> $pld,
        ]);
    }


    //--------------- ADMIN CODES

    public function setFirstAdminUserInfo(){
        admin_user::updateOrCreate(
            ["memid"=> '11111111',],
            [
            "lname"=> 'ADSI',
            "oname"=> 'Stable Shield',
            "eml"=> 'admin@adsicoop.com.ng',
            "role"=> '0',
            "pd1"=> '1',
            "pd2"=> '1',
            "pp1"=> '1',
            "pp2"=> '1',
            "pm1"=> '1',
            "pm2"=> '1',
            
        ]);
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "First Admin User Created"
        ]);
    }


    //POST
    public function setAdminUserInfo(Request $request){
        $request->validate([
            "memid"=>"required",
            "lname"=> "required",
            "oname"=> "required",
            "eml"=> "required|email",
            "role"=> "required",
            "pd1"=> "required",
            "pd2"=> "required",
            "pp1"=> "required",
            "pp2"=> "required",
            "pm1"=> "required",
            "pm2"=> "required",
        ]);
        admin_user::updateOrCreate(
            ["memid"=> $request->memid,],
            [
            "lname"=> $request->lname,
            "oname"=> $request->oname,
            "eml"=> $request->eml,
            "role"=> $request->role,
            "pd1"=> $request->pd1,
            "pd2"=> $request->pd2,
            "pp1"=> $request->pp1,
            "pp2"=> $request->pp2,
            "pm1"=> $request->pm1,
            "pm2"=> $request->pm2,
            
        ]);
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "Admin User Info updated"
        ]);
    }

    //GET 
    public function getHighlights(){
        $role = auth()->payload()->get('role');
        if ( $role!=null  && $role=='0') {
            $totalUsers = User::count();
            $totalMales = member_general_data::where('sex', 'M')->count();
            $totalFemales = member_general_data::where('sex', 'F')->count();
            return response()->json([
                "status"=> true,
                "message"=> "Success",
                "pld"=> [
                    'totalUsers'=>$totalUsers,
                    'totalMales'=>$totalMales,
                    'totalFemales'=> $totalFemales
                ],
            ]);   
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

    //POST
    public function setAnnouncements(Request $request){
        $role = auth()->payload()->get('role');
        if ( $role!=null  && $role=='0') {
              $request->validate([
                "title"=>"required",
                "msg"=> "required",
                "time"=> "required",
            ]);
            announcements::create([
                "title"=> $request->title,
                "msg"=> $request->msg,
                "time"=> $request->time,
            ]);
            // Respond
            return response()->json([
                "status"=> true,
                "message"=> "Announcement Added"
            ]);
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

    //GET 
    public function getVerificationStats(){
        $pd1 = auth()->payload()->get('pd1');
        if ( $pd1!=null  && $pd1=='1') { //Can read from dir
            $totalVerified = member_basic_data::where('verif', '1')->count();
            $totalUnverified = member_basic_data::where('verif', '0')->count();
            return response()->json([
                "status"=> true,
                "message"=> "Success",
                "pld"=> [
                    'totalVerified'=>$totalVerified,
                    'totalUnverified'=>$totalUnverified
                ],
            ]);   
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

    //GET
    public function getMembersByV($vstat){
        $pd1 = auth()->payload()->get('pd1');
        if ( $pd1!=null  && $pd1=='1') { //Can read from dir
            $members = member_basic_data::where('verif', $vstat)->get();
            $pld = [];
            foreach ($members as $member) {
                $memid = $member->memid;
                $genData = member_general_data::where('memid', $memid)->first();
                $pld[] = [
                    'b'=> $member,
                    'g'=> $genData,
                ];
            }
            return response()->json([
                "status"=> true,
                "message"=> "Success",
                "pld"=> $pld
            ]);   
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }


    




    //------------------------------------

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
