<?php
namespace App\UserService;

use App\UserInterface\CreateInrerface;
use App\UserRepository\CreateRepository;
use App\Models\User;
use App\Models\User_wallets;
use Illuminate\Support\Facades\Validator;

use Throwable;
use App\ErrorCodeService;
use Illuminate\Support\Facades\Hash;

class CreateService implements CreateInrerface
{
    private $CreateRepository;
    private $ErrorCodeService;
    private $err = [];
    private $keys = [];

    private $ruls = [];
    private $rulsMessage = [

    ];
    public function __construct(CreateRepository $CreateRepository, ErrorCodeService $ErrorCodeService)
    {
        $this->CreateRepository = $CreateRepository;
        $this->keys = $ErrorCodeService->GetErrKey();
        $this->err = $ErrorCodeService->GetErrCode();
        $this->rulsMessage = [
            'name.required' => $this->keys[1],
            'name.max' => $this->keys[1],
            'name.min' => $this->keys[1],
            'email.required' => $this->keys[2],
            'email.unique' => $this->keys[3],
            'email.min' => $this->keys[1],
            'email.max' => $this->keys[1],
            'email.regex' => $this->keys[1],
            'password.required' => $this->keys[2],
            'password.min' => $this->keys[1],
            'password.max' => $this->keys[1],
            'password.regex' => $this->keys[1],
            'phone.required' => $this->keys[2],
            'phone.string' => $this->keys[1],
            'phone.size' => $this->keys[1],
            'phone.regex' => $this->keys[1],
            'phone.unique' => $this->keys[4],
            'address.required' => $this->keys[2],
            'address.min' => $this->keys[1],
            'address.max' => $this->keys[1],
            'age.required' => $this->keys[2],
            'age.before' => $this->keys[1],
            'age.date' => $this->keys[1],
        ];
        $this->ruls = [
            'name' => ['required', 'max:25', 'min:3'],
            'email' => ['required', 'unique:users,email', 'min:15', 'max:50', 'regex:/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i'],
            'password' => ['required', 'min:10', 'max:25', 'regex:/^[A-Za-z0-9]+$/'],
            'phone' => ['required', 'string', 'size:9', 'regex:/^[0-9]+$/', 'unique:users,phone'],
            'address' => ['required', 'min:10', 'max:25'],
            'age' => ['required', 'before:2023-08-08', 'date'],
        ];
    }
    public function CreateUser(array $data)
    {
        try {
            $password = Hash::make($data['password']);
            $data['password'] = $password;
            return $this->CreateRepository->CreateUser($data);
        } catch (Throwable $e) {
            return null;
        }

    }
    public function CreatrWallet($Email)
    {
        return $this->CreateRepository->CreatrWallet($Email);
    }
    public function Validatorr($request)
    {
        $validator = Validator::make($request->all(), $this->ruls, $this->rulsMessage);
        if ($validator->fails()) {
            return response()->json(['err' => $validator->errors()->first(), 'message' => $this->err[$validator->errors()->first()]]);
        }
        return null;
    }
}
?>