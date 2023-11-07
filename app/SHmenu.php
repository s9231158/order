<?php
namespace App;

use App\Contract\OSmenu as ContractOSmenu;
class SHmenu implements ContractOSmenu{
    public function Getmenu($offset,$limit){
        return '456';
    }
}
