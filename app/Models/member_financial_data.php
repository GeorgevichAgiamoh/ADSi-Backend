<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class member_financial_data extends Model
{
    protected $table = 'member_financial_data'; 
    protected $primaryKey = 'memid';
    protected $fillable = [
        'memid', 'bnk', 'anum','aname'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
