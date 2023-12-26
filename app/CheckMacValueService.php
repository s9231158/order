<?php

namespace App;

use Exception;

class CheckMacValueService
{
    /**
     * Hash 方式
     *
     * @var string
     */
    private $Method;
    protected $HashIv;
    protected $HashKey;


    public function __construct($Key, $Iv)
    {
        $this->setHashKey($Key);
        $this->setHashIv($Iv);
    }

    public function Generate($Source)
    {
        try {
            //如果有checkvalue 移除他
            $Filtered = $this->filter($Source);
            //對key進行大小寫排序
            $Sorted = $this->NaturalSort($Filtered);
            //array to string & add hashiv,key
            $Combined = $this->ToEncodeSourceString($Sorted);
            //進行URL encode & 轉成小寫
            $Encoded = $this->EcpayUrlEncode($Combined);
            //使用sha256產生雜湊
            $Hash = $this->GenerateHash($Encoded);
            //轉大寫
            $CheckMacValue = strtoupper($Hash);
            return $CheckMacValue;
        } catch (Exception $e) {
            throw $e;
        }
    }
   
    public function GenerateHash($Source)
    {
        $Hash = hash('sha256', $Source);
        return $Hash;
    }

    public static function NaturalSort($Source)
    {
        uksort($Source, function ($First, $Second) {

            return strcasecmp($First, $Second);
        });
        return $Source;
    }

    public function Filter($Source)
    {
        if (isset($Source[$this->getFieldName()])) {
            unset($Source[$this->getFieldName()]);
        }
        return $Source;
    }
    public function ToEncodeSourceString($Source)
    {
        $Combined = 'hash_key=' . $this->GetHashKey();
        foreach ($Source as $Name => $Value) {
            $Combined .= '&' . $Name . '=' . $Value;
        }
        $Combined .= '&hash_iv=' . $this->GetHashIv();
        return $Combined;
    }


    public static function EcpayUrlEncode($Source)
    {
        $Encoded = urlencode($Source);
        $Lower = strtolower($Encoded);
        $DotNetFormat = self::ToDotNetUrlEncode($Lower);

        return $DotNetFormat;
    }

    /**
     * 轉換為 .net URL 編碼結果
     *
     * @param  string $source
     * @return string
     */
    public static function ToDotNetUrlEncode($Source)
    {
        $Search = [
            '%2d',
            '%5f',
            '%2e',
            '%21',
            '%2a',
            '%28',
            '%29',
        ];
        $Replace = [
            '-',
            '_',
            '.',
            '!',
            '*',
            '(',
            ')',
        ];
        $Replaced = str_replace($Search, $Replace, $Source);

        return $Replaced;
    }
    public function GetFieldName()
    {
        return 'check_mac_value';
    }

    public function GetHashIv()
    {
        return $this->HashIv;
    }

    /**
     * 取得 Hash Key
     *
     * @return string
     */
    public function GetHashKey()
    {
        return $this->HashKey;
    }

    /**
     * 設定 Hash IV
     *
     * @param  string $Iv
     * @return void
     */
    public function setHashIv($Iv)
    {
        $this->HashIv = $Iv;
    }

    /**
     * 設定 Hash Key
     *
     * @param  string $Key
     * @return void
     */
    public function setHashKey($Key)
    {
        $this->HashKey = $Key;
    }
}
