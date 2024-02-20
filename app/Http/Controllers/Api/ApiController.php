<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SSSMails;
use App\Models\admin_user;
use App\Models\adsi_info;
use App\Models\announcements;
use App\Models\files;
use App\Models\member_basic_data;
use App\Models\member_financial_data;
use App\Models\member_general_data;
use App\Models\password_reset_tokens;
use App\Models\payment_refs;
use App\Models\pays0;
use App\Models\pays1;
use App\Models\pays2;
use App\Models\pays9;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiController extends Controller
{
    //Register API (POST, formdata)
    public function register(Request $request){
        //Data validation
        $request->validate([
            "memid"=>"required|unique:users",
            "email"=> "required|unique:users|email",
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
            "memid"=>"required",
            "password"=> "required",
        ]);
        $mid = $request->memid;
        if(!empty($mid) || !empty($eml)){
            $pld = User::where("memid","=", $mid)->first();
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

    //Send Reset PWD mail (POST, formdata)
    public function sendPasswordResetEmail(Request $request){
        //Data validation
        $request->validate([
            "memid"=>"required",
        ]);
        $mid = $request->memid;
        $pld = User::where("memid","=", $mid)->first();
        if($pld){
            $email = $pld->email;
            $token = Str::random(60); //Random reset token
            password_reset_tokens::updateOrCreate(
                ['email' => $email],
                ['email' => $email, 'token' => $token]
            );
            $data = [
                'name' => $mid,
                'subject' => 'Reset your ADSI password',
                'body' => 'Please go to this link to reset your password. It will expire in 1 hour:',
                'link'=>'https://portal.adsicoop.com.ng/passwordreset/'.$token,
            ];
        
            Mail::to($email)->send(new SSSMails($data));
            
            return response()->json([
                "status"=> true,
                "message"=> "Password reset token sent to mail",
            ]);   
        }
        // Respond
        return response()->json([
            "status"=> false,
            "message"=> "ADSI number not found",
        ]);
    }


    //Reset Pwd API (POST, formdata)
    public function resetPassword(Request $request){
        //Data validation
        $request->validate([
            "token"=>"required",
            "pwd"=>"required",
        ]);
        $pld = password_reset_tokens::where("token","=", $request->token)->first();
        if($pld){
            $email = $pld->email;
            $usr = User::where("email","=", $email)->first();
            if($usr){
                $usr->update([
                    "password"=>bcrypt($request->pwd),
                ]);
                return response()->json([
                    "status"=> true,
                    "message"=> "Password reset successful",
                ]);   
            }
            return response()->json([
                "status"=> false,
                "message"=> "User not found",
            ]);   
        }
        return response()->json([
            "status"=> false,
            "message"=> "Denied. Invalid/Expired Token",
        ]);
    }
    
    //Paystack Webhook (POST, formdata)
    public function paystackMain(Request $request){ 
        $secret = env('PAYSTACK_SECRET', 'sss_wrong_key');
        $computedHash = hash_hmac('sha512', $request->getContent(), $secret);// Dont use json_encode($request->all()) in hashing
        if ($computedHash == $request->header('x-paystack-signature')) { //Ok, forward
            
            $payload = json_decode($request->getContent(), true);
            $ref = $payload['data']['reference'];
            $furl = null;
            if(Str::startsWith($ref,"adsi-")){ //Its for ADSI
                $furl = 'https://api.adsicoop.com.ng/api/paystackConf';
            }else if(Str::startsWith($ref,"nacdded-")){ //Its for ADSI
                $furl = 'https://api.nacdded.org.ng/api/paystackConf';
            }else if(Str::startsWith($ref,"schoolsilo-")){ //Its for ADSI
                $furl = 'https://api.schoolsilo.cloud/api/paystackConf';
            }else{
                Log::info('STR BAD '.$ref);
            }
            if($furl){
                $response = Http::post($furl, [
                    'payload' => $request->getContent(),
                ]);
    
                if ($response->successful()) {
                    return response()->json(['status' => 'success'], 200);
                } else {
                    Log::error('Failed to forward webhook to the second app.');
                    return response()->json(['status' => 'error'], 500);
                }
            }
            return response()->json(['status' => 'success'], 200);
        } else {
            Log::info('Invalid hash '.$request->header('x-paystack-signature'));
            Log::info('Computed '.$computedHash);
            // Request is invalid
            return response()->json(['status' => 'error'], 401);
        }
    }


    //Paystack Webhook (POST, formdata)
    public function paystackConf(Request $request){ 
        $payload = json_decode($request->input('payload'), true);
        if($payload['event'] == "charge.success"){
            $ref = $payload['data']['reference'];
            $pld = payment_refs::where("ref","=", $ref)->first();
            if(Str::startsWith($ref,"adsi-")){ //Its for ADSI
                if(!$pld){ // Its unique
                    $payinfo = explode('-',$ref);
                    $amt = $payinfo[2];
                    $nm = $payload['data']['metadata']['name'];
                    $tm = $payload['data']['metadata']['time'];
                    payment_refs::create([
                        "ref"=> $ref,
                        "amt"=> intval($amt),
                        "time"=> $tm,
                    ]);
                    $upl = [
                        "memid"=>$payinfo[3],
                        "ref"=> $ref,
                        "name"=> $nm,
                        "time"=> $tm,
                        "amt"=> intval($amt)
                    ];
                    if($payinfo[1]=='0'){
                        pays0::create($upl);
                        member_basic_data::where("memid", $payinfo[3])->update(['pay' => '1']);
                    }else if ($payinfo[1]=='1'){
                        $yr = $payload['data']['metadata']['year'];
                        $upl['year'] = $yr;
                        pays1::create($upl);
                    }else{ // ie 2
                        $sh = $payload['data']['metadata']['shares'];
                        $upl['shares'] = $sh;
                        pays2::create($upl);
                    }
                    Log::info('SUCCESS');
                }else{
                    Log::info('PLD EXISTS'.json_encode($pld));
                }
            }else{
                Log::info('STR BAD '.$ref);
            }
        }else{
            Log::info('EVENTS BAD '.$payload['event']);
        }
        return response()->json(['status' => 'success'], 200);
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
            "message"=> "Unauthorized"
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
            "pay"=> "required",
        ]);
        //TODO remove later
        $mid = $request->memid;
        $usr = member_basic_data::where("memid", $mid)->first();
        if(!$usr && $request->pay == '1'){ //Log pay record
            $puuid = now()->timestamp * 1000 . '';
            $nm = $request->fname .' '. $request->lname;
            $upl = [
                "memid"=>$mid,
                "ref"=> 'adsi-0-5000-'.$mid.'-'.$puuid,
                "name"=> $nm,
                "time"=> $puuid,
                "amt"=> 5000,
            ];
            pays0::create($upl);
        }
        //---
        member_basic_data::updateOrCreate(
            ["memid"=> $request->memid,],
            [
            "fname"=> $request->fname,
            "lname"=> $request->lname,
            "mname"=> $request->mname,
            "eml"=> $request->eml,
            "phn"=> $request->phn,
            "verif"=> $request->verif,
            'pay'=> $request->pay,
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
        $ok = true;
        $accts = member_financial_data::where("anum","=", $request->anum)->get();
        if($accts){
            foreach ($accts as $act) {
                if($act->memid != $request->memid){
                    $ok = false;
                    break;
                }
            }
        }
        if($ok){
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
        return response()->json([
            "status"=> false,
            "message"=> "Account Number taken by someone else"
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

    //GET
    public function getMemPays($memid,$payId){
        $start = 0;
        $count = 20;
        if(request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = null;
        if($payId == '0'){
            $pld = pays0::where('memid', $memid)
            ->skip($start)
            ->take($count)
            ->get();
        }
        if($payId == '1'){
            $pld = pays1::where('memid', $memid)
            ->skip($start)
            ->take($count)
            ->get();
        }
        if($payId == '2'){
            $pld = pays2::where('memid', $memid)
            ->skip($start)
            ->take($count)
            ->get();
        }
        return response()->json([
            "status"=> true,
            "message"=> "Success",
            "pld"=> $pld,
        ]);  
    }

    //GET 
    public function getMemPaysStat($memid,$payId){
        $total = 0;
        $count = 0;
        if($payId=='0'){
            $count = pays0::where('memid', $memid)->count();
            $total = pays0::where('memid', $memid)->sum('amt');
        }else if($payId=='1'){
            $count = pays1::where('memid', $memid)->count();
            $total = pays1::where('memid', $memid)->sum('amt');
        }else if($payId=='2'){
            $count = pays2::where('memid', $memid)->count();
            $total = pays2::where('memid', $memid)->sum('amt');
        }
        return response()->json([
            "status"=> true,
            "message"=> "Success",
            "pld"=> [
                'total'=>$total,
                'count'=>$count,
            ],
        ]);  
    }

    //GET
    public function getMemDuesByYear($memid, $year){
        $dues = pays1::where('memid', $memid)->where('year', $year)->first();
        return response()->json([
            "status"=> true,
            "message"=> "Success",
            "pld"=> $dues,
        ]);  
    }

    //Files

    //POST (FILES)
    public function uploadFile(Request $request){
        $request->validate([
            'file' => 'required', //|mimes:jpeg,png,jpg,gif,svg|max:2048
            'filename' => 'required',
            'folder' => 'required',
            'memid'=> 'required',
        ]);
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $request->filename;
            $folder = $request->folder;
            if (!Storage::disk('public')->exists($folder)) {
                // If it doesn't exist, create the directory
                Storage::disk('public')->makeDirectory($folder);
            }
            Storage::disk('public')->put($folder.'/'. $filename, file_get_contents($file));
            // Log It
            files::create([
                'memid' => $request->memid,
                'file'=> $filename,
                'folder'=> $folder,
            ]);
            return response()->json([
                "status"=> true,
                "message"=> "Success"
            ]);
        } else {
            return response()->json([
                "status"=> false,
                "message"=> "No file provided"
            ]);
        }
    }

    //GET (FILES)
    public function getFiles($uid){
        $pld = files::where('memid', $uid)->get();
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "Success",
            "pld"=> $pld,
        ]);
    }

    //GET (FILE)
    public function getFile($folder,$filename){
        if (Storage::disk('public')->exists($folder.'/'.$filename)) {
            return response()->file(Storage::disk('public')->path($folder.'/'.$filename));
        } else {
            return response()->json([
                "status" => false,
                "message" => "File not found",
            ], 404);
        }
    }

    //GET (FILE)
    public function fileExists($folder,$filename){
        if (Storage::disk('public')->exists($folder.'/'.$filename)) {
            return response()->json([
                "status" => true,
                "message" => "Yes, it does",
            ]);
        } else {
            return response()->json([
                "status" => false,
                "message" => "File not found",
            ]);
        }
    }


    //--------------- ADMIN CODES

    public function setFirstAdminUserInfo(){
        admin_user::updateOrCreate(
            ["memid"=> '55555555',],
            [
            "lname"=> 'ADSI',
            "oname"=> 'COOP ADMIN',
            "eml"=> 'info@stableshield.com',
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
        if ($this->hasRole('0')) {
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
        if ($this->hasRole('0')) {
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
        if ( $this->permOk('pd1')) { //Can read from dir
            $totalVerified = member_basic_data::where('verif', '1')->count();
            $totalUnverified = member_basic_data::where('verif', '0')->count();
            $totalDeleted = member_basic_data::where('verif', '2')->count();
            return response()->json([
                "status"=> true,
                "message"=> "Success",
                "pld"=> [
                    'totalVerified'=>$totalVerified,
                    'totalUnverified'=>$totalUnverified,
                    'totalDeleted'=>$totalDeleted
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
        $start = 0;
        $count = 20;
        if(request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        if ( $this->permOk('pd1')) { //Can read from dir
            $members = member_basic_data::where('verif', $vstat)
                ->skip($start)
                ->take($count)
                ->get();
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
                "message"=> "Retrived the first ".$count." starting at ".$start." position",
                "pld"=> $pld
            ]);   
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

    //GET
    public function searchMember(){
        if ( $this->permOk('pd1')) { //Can read from dir
            $search = null;
            if(request()->has('search')) {
                $search = request()->input('search');
            }
            if($search) {
                $members = member_basic_data::whereRaw("MATCH(memid, eml, phn, lname, fname) AGAINST(? IN BOOLEAN MODE)", [$search])
                ->orderByRaw("MATCH(memid, eml, phn, lname, fname) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                ->take(5)
                ->get();
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
                "message"=> "The Search param is required"
            ]);
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

    //---- FOR OFFLINE PAYMENTS [START]

    //POST
    //POST
    public function registerOfflinePayment(Request $request){ 
        $request->validate([
            "ref"=> "required",
            "name"=> "required",
            "time"=> "required",
            "proof"=> "required",
            "meta"=> "required",
        ]);
        $ref = $request->ref;
        $payinfo = explode('-',$ref);
        $amt = $payinfo[2];
        $nm = $request->name; 
        $tm = $request->time;
        $typ = $payinfo[1]; 
        $upl = [
            "memid"=>$payinfo[3],
            "type"=>$typ,
            "ref"=> $ref,
            "name"=> $nm,
            "time"=> $tm,
            "proof"=> $request->proof,
            "amt"=> intval($amt),
            "meta"=> $request->meta,
        ];
        pays9::create($upl);
        // Respond
        return response()->json([
            "status"=> true,
            "message"=> "Success"
        ]);
    }

    public function approveOfflinePayment(Request $request){ 
        if ( $this->permOk('pp2')) {
            $request->validate([
                "id"=> "required",
            ]);
            $pld = pays9::where('id', $request->id)->first();
            $ref = $pld->ref;
            $payinfo = explode('-',$ref);
            $amt = $payinfo[2];
            $nm = $pld->name; 
            $tm = $pld->time;
            $upl = [
                "memid"=>$payinfo[3],
                "ref"=> $ref,
                "name"=> $nm,
                "time"=> $tm,
                "amt"=> intval($amt)
            ];
            if($payinfo[1]=='0'){
                pays0::create($upl);
                member_basic_data::where("memid", $payinfo[3])->update(['pay' => '1']);
            }else if ($payinfo[1]=='1'){
                $yr = $pld->meta;
                $upl['year'] = $yr;
                pays1::create($upl);
            }else{ // ie 2
                $sh = $pld->meta;
                $upl['shares'] = $sh;
                pays2::create($upl);
            }
            Pays9::where('id', $request->id)->delete();
            // Respond
            return response()->json([
                "status"=> true,
                "message"=> "Success"
            ]);
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

    public function deleteOfflinePayment(Request $request){ 
        if ( $this->permOk('pp2')) {
            $request->validate([
                "id"=> "required",
            ]);
            $pld = pays9::where('id', $request->id)->first();
            Pays9::where('id', $request->id)->delete();
            if (Storage::disk('public')->exists('pends' . '/' . $pld->memid.'_'.$pld->time)) {
                Storage::disk('public')->delete('pends' . '/' . $pld->memid.'_'.$pld->time);
            }
            // Delete Log
            files::where('folder', 'pends')->where('file', $pld->memid.'_'.$pld->time)->delete();
            // Respond
            return response()->json([
                "status"=> true,
                "message"=> "Success"
            ]);
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }



    //---- FOR OFFLINE PAYMENTS [END]

    //POST
    public function uploadPayment(Request $request){ 
        if ( $this->permOk('pp2')) {
            $request->validate([
                "ref"=> "required",
                "name"=> "required",
                "time"=> "required",
            ]);
            $ref = $request->ref;
            $payinfo = explode('-',$ref);
            $amt = $payinfo[2];
            $nm = $request->name; 
            $tm = $request->time; 
            /*payment_refs::create([ DONT INCLUDE SINCE CUSTOM RECORDS NOT ON PAYSTACK
                "ref"=> $ref,
                "amt"=> $amt,
                "time"=> $tm,
                "amt"=> intval($amt)
            ]);*/
            $upl = [
                "memid"=>$payinfo[3],
                "ref"=> $ref,
                "name"=> $nm,
                "time"=> $tm,
                "amt"=> intval($amt)
            ];
            if($payinfo[1]=='0'){
                pays0::create($upl);
                member_basic_data::where("memid", $payinfo[3])->update(['pay' => '1']);
            }else if ($payinfo[1]=='1'){
                $yr = $request->year;
                $upl['year'] = $yr;
                pays1::create($upl);
            }else{ // ie 2
                $sh = $request->shares;
                $upl['shares'] = $sh;
                pays2::create($upl);
            }
            // Respond
            return response()->json([
                "status"=> true,
                "message"=> "Success"
            ]);
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

    //GET 
    public function getRevenue($payId){
        if ( $this->permOk('pp1')) {
            $total = 0;
            $count = 0;
            if($payId=='0'){
                $count = pays0::count();
                $total = pays0::sum('amt');
            }else if($payId=='1'){
                $count = pays1::count();
                $total = pays1::sum('amt');
            }else if($payId=='2'){
                $count = pays2::count();
                $total = pays2::sum('amt');
            }else if($payId=='9'){
                $count = pays9::count();
                $total = pays9::sum('amt');
            }
            return response()->json([
                "status"=> true,
                "message"=> "Success",
                "pld"=> [
                    'total'=>$total,
                    'count'=>$count,
                ],
            ]);   
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

    //GET 
    public function getOutstandingRegFees(){
        if ( $this->permOk('pp1')) {
            $allPlds = [];
            $mems = member_basic_data::where("pay","0")->get();
            if($mems){
                foreach ($mems as $mem) {
                    $puuid = now()->timestamp * 1000 . '';
                    $mid = $mem->memid;
                    $nm = $mem->fname .' '. $mem->lname;
                    $pr = [
                        "memid"=> $mid,
                        "ref"=> 'adsi-0-5000-'.$mid.'-'.$puuid,
                        "name"=> $nm,
                        "time"=> $puuid,
                        "amt" => 5000
                    ];
                    $allPlds[$mid] = $pr;
                }
            }
            return response()->json([
                "status"=> true,
                "message"=> "Success",
                "pld"=> $allPlds
            ]);   
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

     //GET
     public function getPayments($payId){
        if ( $this->permOk('pp1')) { //Can read from dir
            $start = 0;
            $count = 20;
            if(request()->has('start') && request()->has('count')) {
                $start = request()->input('start');
                $count = request()->input('count');
            }
            $pld = null;
            if( $payId=='0' ){
                $pld = pays0::take($count)->skip($start)->get();
            }
            if( $payId=='1' ){
                $pld = pays1::take($count)->skip($start)->get();
            }
            if( $payId=='2' ){
                $pld = pays2::take($count)->skip($start)->get();
            }
            if( $payId=='9' ){
                $pld = pays9::take($count)->skip($start)->get();
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

    //GET
    public function searchPayment($payId){
        if ( $this->permOk('pp1')) { //Can read from pay
            $search = null;
            if(request()->has('search')) {
                $search = request()->input('search');
            }
            if($search) {
                $pld = null;
                if( $payId=='0' ){
                    $pld = pays0::whereRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE)", [$search])
                    ->orderByRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                    ->take(5)
                    ->get();
                }
                if( $payId=='1' ){
                    $pld = pays1::whereRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE)", [$search])
                    ->orderByRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                    ->take(5)
                    ->get();
                }
                if( $payId=='2' ){
                    $pld = pays2::whereRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE)", [$search])
                    ->orderByRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                    ->take(5)
                    ->get();
                }
                if( $payId=='9' ){
                    $pld = pays9::whereRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE)", [$search])
                    ->orderByRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                    ->take(5)
                    ->get();
                }
                return response()->json([
                    "status"=> true,
                    "message"=> "Success",
                    "pld"=> $pld
                ]); 
            }
            return response()->json([
                "status"=> false,
                "message"=> "The Search param is required"
            ]);
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

    //GET
    public function searchMemPayment($memid,$payId){
        $search = null;
        if(request()->has('search')) {
            $search = request()->input('search');
        }
        if($search) {
            $pld = null;
            if( $payId=='0' ){
                $pld = pays0::whereRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE)", [$search])
                ->where('memid', $memid)
                ->orderByRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                ->take(5)
                ->get();
            }
            if( $payId=='1' ){
                $pld = pays1::whereRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE)", [$search])
                ->where('memid', $memid)
                ->orderByRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                ->take(5)
                ->get();
            }
            if( $payId=='2' ){
                $pld = pays2::whereRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE)", [$search])
                ->where('memid', $memid)
                ->orderByRaw("MATCH(name, ref, memid) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                ->take(5)
                ->get();
            }
            return response()->json([
                "status"=> true,
                "message"=> "Success",
                "pld"=> $pld
            ]); 
        }
        return response()->json([
            "status"=> false,
            "message"=> "The Search param is required"
        ]);
    }

    //POST
    public function setAdsiInfo(Request $request){
        if ($this->hasRole('0')) {
            $request->validate([
                "memid"=>"required",
                "cname"=>"required",
                "regno"=> "required",
                "addr"=> "required",
                "nationality"=>"required",
                "state"=> "required",
                "lga"=> "required",
                "aname"=>"required",
                "anum"=> "required",
                "bnk"=> "required",
                "pname"=>"required",
                "peml"=> "required",
                "pphn"=> "required",
                "paddr"=>"required",
                
            ]);
            adsi_info::updateOrCreate(
                ["memid"=> $request->memid,],
                [
                "cname"=> $request->cname,
                "regno"=> $request->regno,
                "addr"=> $request->addr,
                "nationality"=> $request->nationality,
                "state"=> $request->state,
                "lga"=> $request->lga,
                "aname"=> $request->aname,
                "anum"=> $request->anum,
                "bnk"=> $request->bnk,
                "pname"=> $request->pname,
                "peml"=> $request->peml,
                "pphn"=> $request->pphn,
                "paddr"=> $request->paddr,
            ]);
            // Respond
            return response()->json([
                "status"=> true,
                "message"=> "ADSI Info updated"
            ]);
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

    //GET
    public function getAsdiInfo(){
        if ($this->hasRole('0')) {
            $pld = adsi_info::where('memid', '55555555')->first();
            if($pld){
                return response()->json([
                    "status"=> true,
                    "message"=> "Success",
                    "pld"=> $pld,
                ]);
            }
            return response()->json([
                "status"=> false,
                "message"=> "No Data Yet",
            ]);
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);   
    }

     //GET
     public function getAdmins(){
        if ($this->hasRole('0')) {
            $pld = admin_user::all();
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

    //GET
    public function getAdmin($adminId){
        $role = auth()->payload()->get('role');
        if ( $role!=null) { //Granted to all admin as is needed on first page
            $pld = admin_user::where('memid', $adminId)->first();
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

    //POST
    public function setAdmin(Request $request){
        if ($this->hasRole('0')) {
            $request->validate([
                "memid"=>"required",
                "lname"=>"required",
                "oname"=> "required",
                "eml"=> "required",
                "role"=>"required",
                "pd1"=> "required",
                "pd2"=> "required",
                "pp1"=>"required",
                "pp2"=> "required",
                "pm1"=> "required",
                "pm2"=>"required",
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
                "message"=> "Admin Added"
            ]);
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

    //GET
    public function removeAdmin($adminId){
        if ($this->hasRole('0')) {
            $dels = admin_user::where('memid', $adminId)->delete();
            if($dels>0){
                return response()->json([
                    "status"=> true,
                    "message"=> "Success",
                ]);  
            }
            return response()->json([
                "status"=> false,
                "message"=> "Nothing to delete"
            ]);   
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

    //Reset Member Pwd API (POST, formdata)
    public function resetMemberPassword(Request $request){
        //Data validation
        $request->validate([
            "email"=>"required",
            "pwd"=>"required",
        ]);
        if ($this->hasRole('0')) {
            $usr = User::where("email","=", $request->email)->first();
            if($usr){
                $usr->update([
                    "password"=>bcrypt($request->pwd),
                ]);
                return response()->json([
                    "status"=> true,
                    "message"=> "Password reset successful",
                ]);   
            }
            return response()->json([
                "status"=> false,
                "message"=> "User not found",
            ]);   
        }
        return response()->json([
            "status"=> false,
            "message"=> "Access denied"
        ],401);
    }

    //POST 
    public function sendMail(Request $request){
        if ( $this->permOk('pd2')) { //Can write to dir
            $request->validate([
                "name"=>"required",
                "email"=>"required",
                "subject"=>"required",
                "body"=> "required",
                "link"=> "required",
            ]);
            $data = [
                'name' => $request->name,
                'subject' => $request->subject,
                'body' => $request->body,
                'link' => $request->link,
            ];
        
            Mail::to($request->email)->send(new SSSMails($data));
            
            return response()->json([
                "status"=> true,
                "message"=> "Mailed Successfully",
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


    //---NON ENDPOINTS

    public function permOk($pid): bool
    {
        // $pp = auth()->payload()->get($pid);
        // return $pp!=null  && $pp=='1';
        return true;
    }

    public function hasRole($rid): bool
    {
        // $role = auth()->payload()->get('role');
        // return $role!=null  && $role==$rid;
        return true;
    }

}
