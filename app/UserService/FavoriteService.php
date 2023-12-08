<?php
namespace App\UserService;

use App\ErrorCodeService;
use App\Models\Restaurant;
use App\Models\User_favorite;
use App\TotalService;
use App\UserInterface\FavoriteInterface;
use App\UserRepository\FavoriteRepository;


class FavoriteService implements FavoriteInterface
{

    private $FavoriteRepository;
    private $ErrorCodeService;
    private $TotalService;
    private $err;
    private $keys;
    public function __construct(FavoriteRepository $FavoriteRepository, ErrorCodeService $ErrorCodeService, TotalService $TotalService)
    {
        $this->FavoriteRepository = $FavoriteRepository;
        $this->ErrorCodeService = $ErrorCodeService;
        $this->TotalService = $TotalService;
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
    public function LimitOffsetValidator($Request)
    {
        try {
            $LimitOffsetValidator = $this->TotalService->LimitOffsetValidator($Request);
            return $LimitOffsetValidator;
        } catch (\Throwable $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }
    public function GetOffsetLimit($OffsetLimit)
    {
        try {
            $GetOffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);
            return $GetOffsetLimit;
        } catch (\Throwable $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }

    public function GetUserFavorite($OffsetLimit)
    {
        try {
            $GetUserFavorite = $this->FavoriteRepository->GetUserFavoriteInfo($OffsetLimit);
            return $GetUserFavorite;

        } catch (\Throwable $e) {
            return response()->json([$e, 'err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }


    public function DeleteFavorite($Rid)
    {
        $DeleteFavoriteCount = $this->FavoriteRepository->UserFavoriteCount($Rid);
        if ($DeleteFavoriteCount === 0) {
            return response()->json(['err' => $this->keys[16], 'message' => $this->err[16]]);
        }
        if ($DeleteFavoriteCount === 1) {
            $DeleteFavorite = $this->FavoriteRepository->DeleteFavorite($Rid);
            return response()->json(['err' => $this->keys[0], 'message' => $this->err[0]]);
        }
        return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);


    }

}



?>