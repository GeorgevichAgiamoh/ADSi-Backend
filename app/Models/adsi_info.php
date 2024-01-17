<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class adsi_info extends Model
{
    protected $table = 'adsi_info'; 
    protected $primaryKey = 'memid';
    protected $fillable = [
        'memid','cname', 'regno', 'addr','nationality', 'state','lga','aname', 'anum','bnk','pname','peml','pphn','paddr'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
