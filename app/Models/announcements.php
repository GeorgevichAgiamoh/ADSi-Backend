<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class announcements extends Model
{
    protected $table = 'announcements';
    protected $fillable = [
        'time', 'msg'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
