<?php

namespace App\Contract;

interface RestaurantInterface
{
    public function GetMenu(int $Offset, int $Limit): array;
    public function MenuEnable(array $order): bool;
    public function SendApi(array $OrderInfo, array $Order): bool;
    public function MenuCorrect(array $order): bool;

}
