<?php

namespace App\Contract;

interface RestaurantInterface
{
    public function getMenu(int $Offset, int $Limit): array;
    public function menuEnable(array $Order): bool;
    public function sendApi(array $OrderInfo, array $Order): bool;
    public function menuCorrect(array $Order): bool;
}
