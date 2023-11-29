<?php
namespace App\UserService;

use App\ErrorCodeService;
use App\UserRepository\LoginRepository;

class LoginService
{
    private $LoginRepository;
    private $ErrorCodeService;
    public function __construct(LoginRepository $LoginRepository,ErrorCodeService $ErrorCodeService)
    {
        $this->LoginRepository = $LoginRepository;
        $this->ErrorCodeService = $ErrorCodeService;
    }
}


?>