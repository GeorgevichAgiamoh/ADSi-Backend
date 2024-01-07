<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class member_financial_data extends Model
{
    //protected $table = 'member_data'; SAME AS CLASS NAME
    protected $primaryKey = 'memid';
    protected $fillable = [
        'memid', 'bnk', 'anum',
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
