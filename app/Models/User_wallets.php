<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use PhpParser\Node\Stmt\Return_;

class User_wallets extends Model
{
    protected $fillable = [
        'id',
        'balance'
    ];

    use HasFactory;
    public function user():BelongsTo{
        return $this->belongsTo(User::class);
    }
}
