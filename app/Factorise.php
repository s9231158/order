<?php

namespace App;

use App\OSmenu;
use App\TAmenu;
use App\SHmenu;
use App\Localmenu;

class Factorise
{
    public static function Setmenu(int $Rid): object
    {
        return match ($Rid) {
            1 => (function () {
                    return new OSmenu();
                })(),
            2 => (function () {
                    return new TAmenu();
                })(),
            3 => (function () {
                    return new SHmenu();
                })(),
            4 => (function () {
                    return new Localmenu();
                })(),
        };
    }
}
