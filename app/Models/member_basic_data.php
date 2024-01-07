<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class member_basic_data extends Model
{
    //protected $table = 'member_data'; SAME AS CLASS NAME
    protected $primaryKey = 'memid';
    protected $fillable = [
        'memid', 'fname', 'lname','mname', 'eml', 'phn',
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
