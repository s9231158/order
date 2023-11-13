<?php

namespace App;

use App\OSmenu;
use App\TAmenu;
use App\SHmenu;

class Factorise
{
    public static function Setmenu($rid)
    {
        return match ($rid) {
            '1' => (function () {
                return new OSmenu();
            })(),
            '11' => (function () {
                return new TAmenu();
            })(),
            '12' => (function () {
                return new SHmenu();
            })(),
        };
    }
}
