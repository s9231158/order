<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_wallets extends Model
{
    protected $fillable = [
        'id',
        'balance'
    ];
    use HasFactory;
}
