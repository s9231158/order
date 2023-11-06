<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oishii_menu extends Model
{
    use HasFactory;
    public function Restaurant(){
        return $this->hasMany(Restaurant::class,'rid');
    }
}
