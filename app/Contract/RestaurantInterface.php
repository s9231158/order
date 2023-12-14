<?php

namespace App\Contract;

interface RestaurantInterface
{
    public function Getmenu($Offset, $Limit);
    public function Menuenable(array $order);
    public function Restrauntenable($rid);
    public function Hasmenu($order);
    public function SendApi($OrderInfo, $Order);
    public function HasRestraunt($order);
    public function Menucorrect(array $order);

}
