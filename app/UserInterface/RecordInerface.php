<?php
namespace App\UserInterface;

interface RecordInerface
{
    public function Validator($Request);
    public function GetRecord($offset, $limit);
}


?>