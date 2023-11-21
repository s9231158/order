<?php

namespace App;

class TotalService
{

    /**
     * 設定offsey&limit預設值,直接把客戶端requset丟進來
     *
     * @param [object] $request
     * @return array
     */
    public function SetOffset($request)
    {
        $offset = 0;
        $limit = 20;
        if ($request['offset'] != null) {
            $offset = $request['offset'];
        }
        if ($request['limit'] != null) {
            $limit = $request['limit'];
        }
        return array('offset'=>$offset, 'limit'=>$limit);
    }
    
}
