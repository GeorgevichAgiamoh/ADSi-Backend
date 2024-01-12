<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class member_basic_data extends Model
{
    protected $table = 'member_basic_data';
    protected $primaryKey = 'memid';
    protected $fillable = [
        'memid', 'fname', 'lname','mname', 'eml', 'phn','verif'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
