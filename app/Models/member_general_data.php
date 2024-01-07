<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class member_general_data extends Model
{
    //protected $table = 'member_data'; SAME AS CLASS NAME
    protected $primaryKey = 'memid';
    protected $fillable = [
        'memid', 'sex', 'marital','dob', 'nationality', 'state','lga', 'town', 'addr','job', 'nin', 'kin_fname','kin_lname','kin_mname','kin_type','kin_phn','kin_addr','kin_eml',
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
