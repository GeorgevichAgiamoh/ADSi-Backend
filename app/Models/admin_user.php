<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class admin_user extends Model
{
    protected $table = 'admin_user'; 
    protected $primaryKey = 'memid';
    protected $fillable = [
        'memid', 'lname', 'oname','eml', 'role','pd1','pd2','pp1','pp2','pm1','pm2'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
