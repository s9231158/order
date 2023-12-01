<?php
namespace App\UserInterface;

interface FavoriteInterface
{
    public function CheckFavoriteTooMuch();
    public function CheckAlreadyAddFavorite($Rid);
    public function AddFavorite($Rid);
}



?>