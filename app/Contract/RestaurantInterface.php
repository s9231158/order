<?php

namespace App\Contract;

interface RestaurantInterface
{
    public function GetMenu(int $Offset, int $Limit): array;
    public function MenuEnable(array $Order): bool;
    public function SendApi(array $OrderInfo, array $Order): bool;
    public function MenuCorrect(array $Order): bool;
}
