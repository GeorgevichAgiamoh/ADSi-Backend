<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pays9 extends Model
{
    protected $table = 'pays9'; 
    protected $fillable = [
        'memid','type','ref', 'name', 'time', 'proof','amt','meta'
    ];
}
