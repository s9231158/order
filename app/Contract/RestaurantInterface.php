<?php

namespace App\Contract;

interface RestaurantInterface
{
    public function Getmenu($offset, $limit);
    public function Menuenable(array $order);
    public function Restrauntenable($rid);
    public function Hasmenu($order);
    public function Change($order, $order2);
    public function Sendapi($order);
    public function HasRestraunt($order);
    public function Menucorrect(array $order);
    public function Geterr($callback);

}
