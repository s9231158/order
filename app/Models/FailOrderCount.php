<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailOrderCount extends Model
{
    protected $table = 'fail_order_counts';
    protected $fillable = [
        'failcount',
        'starttime',
        'endtime',
        'totalcount'
    ];
    use HasFactory;
}
