<?php

namespace App\Services;

use Exception;

class CheckMacValueService
{
    protected $hashIv;
    protected $hashKey;

    public function __construct($key, $iv)
    {
        $this->setHashKey($key);
        $this->setHashIv($iv);
    }

    public function generate($source)
    {
        try {
            //如果有checkvalue 移除他
            $filtered = $this->filter($source);
            //對key進行大小寫排序
            $sorted = $this->naturalSort($filtered);
            //array to string & add hashiv,key
            $combined = $this->toEncodeSourceString($sorted);
            //進行URL encode & 轉成小寫
            $encoded = $this->ecpayUrlEncode($combined);
            //使用sha256產生雜湊
            $hash = $this->generateHash($encoded);
            //轉大寫
            $checkMacValue = strtoupper($hash);
            return $checkMacValue;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function generateHash($source)
    {
        $hash = hash('sha256', $source);
        return $hash;
    }

    public static function naturalSort($source)
    {
        uksort($source, function ($first, $second) {
            return strcasecmp($first, $second);
        });
        return $source;
    }

    public function filter($source)
    {
        if (isset($source[$this->getFieldName()])) {
            unset($source[$this->getFieldName()]);
        }
        return $source;
    }

    public function toEncodeSourceString($source)
    {
        $combined = 'hash_key=' . $this->getHashKey();
        foreach ($source as $name => $value) {
            $combined .= '&' . $name . '=' . $value;
        }
        $combined .= '&hash_iv=' . $this->getHashIv();
        return $combined;
    }

    public static function ecpayUrlEncode($source)
    {
        $encoded = urlencode($source);
        $lower = strtolower($encoded);
        $dotNetFormat = self::toDotNetUrlEncode($lower);
        return $dotNetFormat;
    }

    public static function toDotNetUrlEncode($source)
    {
        $search = [
            '%2d',
            '%5f',
            '%2e',
            '%21',
            '%2a',
            '%28',
            '%29',
        ];
        $replace = [
            '-',
            '_',
            '.',
            '!',
            '*',
            '(',
            ')',
        ];
        $replaced = str_replace($search, $replace, $source);
        return $replaced;
    }

    public function getFieldName()
    {
        return 'check_mac_value';
    }

    public function getHashIv()
    {
        return $this->hashIv;
    }

    public function getHashKey()
    {
        return $this->hashKey;
    }

    public function setHashIv($iv)
    {
        $this->hashIv = $iv;
    }

    public function setHashKey($key)
    {
        $this->hashKey = $key;
    }
}
