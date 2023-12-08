<?php
namespace App\UserInterface;

interface LoginInterface
{
    public function LoginValidator($Request);
    public function MakeKey($Email, $Ip);
    public function LoginCheckTooManyAttempts($MakeKeyInfo);
    public function LoginCheckAccountPassword($Account);
    public function CreateToken($email);
    public function CheckHasLogin($TokenEmail);
    public function CreatrLoginRecord($RocordInfo);


}








?>