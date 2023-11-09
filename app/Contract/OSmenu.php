<?php
namespace App\Contract;
interface OSmenu {
    public function Getmenu($offset,$limit);
    public function Menuenable($order);
    public function Restrauntenable($rid); 
    public function Hasmenu($order);
    public function Change($order,$order2);
    public function Sendapi($order);
}
