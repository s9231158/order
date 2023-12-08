<?php
namespace App\UserInterface;

interface FavoriteInterface
{
    public function CheckFavoriteTooMuch();
    public function CheckAlreadyAddFavorite($Rid);
    public function AddFavorite($Rid);
    public function LimitOffsetValidator($Request);
    public function GetOffsetLimit($OffsetLimit);
    public function GetUserFavorite($OffsetLimit);
    public function DeleteFavorite($Rid);

}



?>