<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class password_reset_tokens extends Model
{
    protected $table = 'password_reset_tokens'; 
    protected $primaryKey = 'memid';
    public $incrementing = false;
    protected $fillable = [
        'memid', 'token'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
