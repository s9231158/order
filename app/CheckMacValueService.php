<?php

namespace App;

use Exception;

class CheckMacValueService
{
    const METHOD_MD5 = 'md5';
    const METHOD_SHA256 = 'sha256';

    /**
     * Hash 方式
     *
     * @var string
     */
    private $method;
    protected $hash_Iv;
    protected $hash_Key;


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
    //如果有checkvalue 移除他
    // {
    //     "merchant_id": 11,
    //     "merchant_trade_no": "1111111111111111111",
    //     "merchant_trade_date": "2023/10/20 11:59:59",
    //     "payment_type": "aio",
    //     "amount": 123,
    //     "trade_desc": "購買商品",
    //     "item_name": "product#dskjf",
    //     "return_url": "http://localhost:8082/api/test",
    //     "choose_payment": "Credit",
    //     "encrypt_type": 1,
    //     "lang": "en"
    // }


    //對key進行大小寫排序
    // {
    //     "amount": 123,
    //     "choose_payment": "Credit",
    //     "encrypt_type": 1,
    //     "item_name": "product#dskjf",
    //     "lang": "en",
    //     "merchant_id": 11,
    //     "merchant_trade_date": "2023/10/20 11:59:59",
    //     "merchant_trade_no": "1111111111111111111",
    //     "payment_type": "aio",
    //     "return_url": "http://localhost:8082/api/test",
    //     "trade_desc": "購買商品"
    // }

    //array to string & add hashiv,key
    // hash_key=0dd22e31042fbbdd&amount=123&choose_payment=Credit&encrypt_type=1&item_name=product#dskjf&lang=en&merchant_id=11&merchant_trade_date=2023/10/20 11:59:59&merchant_trade_no=1111111111111111111&payment_type=aio&return_url=http://localhost:8082/api/test&trade_desc=購買商品&HashIV=e62f6e3bbd7c2e9d


    //進行URL encode & 轉成小寫
    // hashkey%3d0dd22e31042fbbdd%26amount%3d123%26choose_payment%3dcredit%26encrypt_type%3d1%26item_name%3dproduct%23dskjf%26lang%3den%26merchant_id%3d11%26merchant_trade_date%3d2023%2f10%2f20+11%3a59%3a59%26merchant_trade_no%3d1111111111111111111%26payment_type%3daio%26return_url%3dhttp%3a%2f%2flocalhost%3a8082%2fapi%2ftest%26trade_desc%3d%e8%b3%bc%e8%b2%b7%e5%95%86%e5%93%81%26hashiv%3de62f6e3bbd7c2e9d

    //使用sha256產生雜湊
    // 6b00274ba07937b2c00e1f03ba2b76c11946f1c976754578cc3dc757c6cb9a16


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

    /**
     * 轉換為 .net URL 編碼結果
     *
     * @param  string $source
     * @return string
     */
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
        return $this->hash_Iv;
    }

    /**
     * 取得 Hash Key
     *
     * @return string
     */
    public function getHashKey()
    {
        return $this->hash_Key;
    }

    /**
     * 設定 Hash IV
     *
     * @param  string $iv
     * @return void
     */
    public function setHashIv($iv)
    {
        $this->hash_Iv = $iv;
    }

    /**
     * 設定 Hash Key
     *
     * @param  string $key
     * @return void
     */
    public function setHashKey($key)
    {
        $this->hash_Key = $key;
    }
}
