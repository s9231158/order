<?php

namespace App\Contract;

interface RestaurantInterface
{
    public function getMenu(int $offset, int $limit): array;
    public function menuEnable(array $menuIds): bool;
    public function sendApi(array $orderInfo, array $order): bool;
    public function menuCorrect(array $order): bool;
}
