<?php
namespace App\UserInterface;

interface CreateInrerface
{
    public function CreateUser(array $data);
    public function CreateWallet($Email);
    public function CreateValidator($request);
}
?>