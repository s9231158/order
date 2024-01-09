<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet_Record extends Model
{
    use HasFactory;
    protected $fillable = [
        'out',
        'in',
        'oid',
        'uid',
        'eid',
        'status',
        'pid',
    ];
    protected $primaryKey = 'eid';

}
