<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;
    protected $fillable = [
        'info',
        'openday',
        'opentime',
        'closetime',
        'title',
        'img',
        'address',
        'api',
        'totalpoint',
        'countpoint',
    ];


    public function favorite1(){
        return $this->belongsToMany(User::class, 'user_favorites', 'rid');
    }
}
