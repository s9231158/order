<?php

namespace App;

use App\OSmenu;
use App\TAmenu;
use App\SHmenu;
use App\Localmenu;
use App\Other;

class Factorise
{
    public static function Setmenu($rid)
    {
        return match ($rid) {
            '1' => (function () {
                return new OSmenu();
            })(),
            '2' => (function () {
                return new TAmenu();
            })(),
            '3' => (function () {
                return new SHmenu();
            })(),
            '4' => (function () {
                return new Localmenu();
            })(),
            default => (function () {
                return new Other();
            })(),
        };
    }
}
