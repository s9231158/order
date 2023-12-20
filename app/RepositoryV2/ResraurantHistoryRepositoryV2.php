<?php

namespace App\RepositoryV2;

use App\Models\Restaurant_history;

class ResraurantHistoryRepositoryV2
{
    public function UpdateOrCreate($UserId, $Rid)
    {
        Restaurant_history::updateOrCreate(
            ['uid' => $UserId, 'rid' => $Rid],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }
}