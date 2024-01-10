<?php

namespace App\Services;

class StatusCode
{
    private $statusCode = [
        'sendApiSuccess' => 0,
        'sendApiFail' => 10,
        'failResponse'=>11,
    ];
    public function getStatus()
    {
        return $this->statusCode;
    }
}
