<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fail_Order_Count extends Model
{
    protected $table = 'fail_order_counts';

    protected $fillable = ['count', 'starttime', 'endtime'];
    use HasFactory;
}
