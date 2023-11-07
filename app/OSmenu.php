<?php
namespace App;
use App\Models\Oishii_menu;

use App\Contract\OSmenu as ContractOSmenu;
class OSmenu implements ContractOSmenu{
    public function Getmenu($offset,$limit){
        $menu=Oishii_menu::select('rid','id','info','price','img')->get();
        // $Restaurant = Restaurant::find($rid);
        // $Restaurantinfo = $Restaurant->select('title', 'info', 'openday', 'closetime', 'img', 'address', 'totalpoint', 'countpoint')->where('id', '=', $rid)->get();
        // $menu = $Restaurant->menu()->limit($limit)->offset($offset)->get();
        return $menu;
    }
}
