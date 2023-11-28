<?php
namespace App\UserService;

use App\UserRepository\CreateRepository;
use App\Models\User;
use App\Models\User_wallets;
use Throwable;

class CreateService
{
    private $CreateRepository;
    public function __construct(CreateRepository $CreateRepository)
    {
        $this->CreateRepository = $CreateRepository;
    }
    public function CreateUser(array $data)
    {
        return $this->CreateRepository->CreateUser($data);
    }
    public function CreatrWallet($Email)
    {
        return $this->CreatrWallet($Email);

    }
}




?>