<?php

namespace App\Restaurants;

use App\Interfaces\MenuInterface;

class Restaurant1 implements MenuInterface {
    public function getMenu() {
        return 'Restaurant1...';
    }
}