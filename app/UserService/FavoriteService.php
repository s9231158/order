<?php
namespace App\UserService;

use App\ErrorCodeService;
use App\TotalService;
use App\UserInterface\FavoriteInterface;
use App\UserRepository\FavoriteRepository;


class FavoriteService implements FavoriteInterface
{

    private $FavoriteRepository;
    private $ErrorCodeService;
    private $err;
    private $keys;
    public function __construct(FavoriteRepository $FavoriteRepository, ErrorCodeService $ErrorCodeService)
    {
        $this->FavoriteRepository = $FavoriteRepository;
        $this->ErrorCodeService = $ErrorCodeService;
        $this->err = $this->ErrorCodeService->GetErrCode();
        $this->keys = $this->ErrorCodeService->GetErrKey();
    }
    public function CheckFavoriteTooMuch()
    {
        try {
            $FavoriteCount = $this->FavoriteRepository->GetFavoriteCount();
            if ($FavoriteCount < 20) {
                return true;
            }
            return response()->json(['err' => $this->keys[28], 'message' => $this->err[28]]);
        } catch (\Throwable $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }
    public function CheckAlreadyAddFavorite($Rid)
    {
        try {
            $AlreadyAddFavorite = $this->FavoriteRepository->CheckAlreadyAddFavorite($Rid);
            if ($AlreadyAddFavorite === 0) {
                return true;
            }
            return response()->json(['err' => $this->keys[15], 'message' => $this->err[15]]);
        } catch (\Throwable $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }

    public function AddFavorite($Rid)
    {
        try {
            $AddFavorite = $this->FavoriteRepository->AddFavorite($Rid);
            if ($AddFavorite !== true) {
                return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
            }
            return true;
        } catch (\Throwable $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }

    }
}



?>