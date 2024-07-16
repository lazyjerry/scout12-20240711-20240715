<?php

namespace App\Libraries;

class EncryptUtils
{

    // 亂數字串
    public static function randomString($length = 6, $characters = false)
    {
        $str = "";
        if (!$characters) {
            $characters = array_merge(range('a', 'z'), range('0', '9'));
        }

        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }
        return $str;
    }

// --- base64 encoding/decoding compatible with url START ---
    public static function base64UrlEncode(string $data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

// --- base64 encoding/decoding compatible with url END ---

// --- AES 256 ECB START ---

// 加密 \\
    public static function encrypt(string $plaintext, string $key)
    {
        return openssl_encrypt($plaintext, "aes-256-ecb", $key, OPENSSL_RAW_DATA);
    }

// 解密
    public static function decrypt(string $ciphertext, string $key)
    {
        return openssl_decrypt($ciphertext, "aes-256-ecb", $key, OPENSSL_RAW_DATA);
    }
// --- AES 256 ECB END ---

// ----  二進制轉十六進制 START ----
    // 字符串轉十六進制，加鹽
    public static function bin2hex(string $string, int $preSoltCount = 5, int $sufSoltCount = 7)
    {
        $string = bin2hex($string);

        $soltRange = array_merge(range('a', 'z'), range('0', '9'));
        $solt = self::randomString($preSoltCount, $soltRange);
        $string = $solt . $string;
        $solt = self::randomString($sufSoltCount, $soltRange);

        $string = $string . $solt;

        return bin2hex($string);
    }

    // 十六進制轉字符串，加鹽
    public static function hex2bin(string $hex, int $preSoltCount = 5, int $sufSoltCount = 7)
    {
        $hex = hex2bin($hex);
        if (empty($sufSoltCount)) {
            $hex = substr($hex, $preSoltCount);
        } else {
            $hex = substr($hex, $preSoltCount, -1 * $sufSoltCount);
        }

        return hex2bin($hex);
    }
// ---- 二進制轉十六進制 END ----

}
