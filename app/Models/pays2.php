<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pays2 extends Model
{
    protected $table = 'pays2'; 
    protected $fillable = [
        'memid','ref', 'name', 'time', 'shares'
    ];
}
