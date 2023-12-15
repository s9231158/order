<?php

namespace App\Contract;

interface RestaurantInterface
{
    public function Getmenu(int $Offset, int $Limit): array;
    public function Menuenable(array $order): bool;
    public function SendApi(array $OrderInfo, array $Order): bool;
    public function Menucorrect(array $order): bool;

}
