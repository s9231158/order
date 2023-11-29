<?php

namespace App;

class TotalService
{

    /**
     * 設定offsey&limit預設值,直接把客戶端requset丟進來
     *
     * @return array
     */
    //名稱有問題
    public function GetOffsetLimit($request)
    {
        $offset = 0;
        $limit = 20;
        if ($request['offset'] != null) {
            $offset = $request['offset'];
        }
        if ($request['limit'] != null) {
            $limit = $request['limit'];
        }
        return array('offset' => $offset, 'limit' => $limit);
    }
    public function TokenCheck($Token)
    {

    }
}
