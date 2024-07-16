<?php

namespace App\Libraries;

use CodeIgniter\HTTP\RequestInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// 線上解密：https://www.box3.cn/tools/jwt.html
class JwtUtils
{
    /*
    可以直接取用的 JwtUtils::$payload 資料
     */
    public static $payload = null;

    // 驗證加密訊息
    private static function signJti()
    {
        $payload = self::$payload;
        if (isset($payload['jti'])) {
            unset($payload['jti']);
        }
        $sign = $_ENV['JWT_SIGN'] ?? "to-be-or-not-to-be";
        $result = hash("crc32", $sign . json_encode($payload));
        // log_message('debug', "singJti：{$result}");

        return $result;
    }

    private static function getSoltCount()
    {
        $preSoltCount = abs(intval($_ENV['JWT_PRE_SOLT_COUNT'] ?? 0));
        $sufSoltCount = abs(intval($_ENV['JWT_SUF_SOLT_COUNT'] ?? 0));

        // 限制加鹽最大值，這是為了取亂數方便
        return [($preSoltCount >= 10) ? $preSoltCount % 9 + 1 : $preSoltCount, ($sufSoltCount >= 10) ? $preSoltCount % 9 + 1 : $sufSoltCount];
    }

    private static function removeSolt(string $value)
    {
        list($preSoltCount, $sufSoltCount) = self::getSoltCount();
        // log_message("debug", "** removeSolt **");
        // log_message("debug", "{$preSoltCount}, {$sufSoltCount}");
        // log_message("debug", "** removeSolt **");
        if (empty($sufSoltCount)) {
            return substr($value, $preSoltCount);
        }
        return substr($value, $preSoltCount, (-1 * $sufSoltCount));
    }

    private static function addSolt(string $value)
    {
        list($preSoltCount, $sufSoltCount) = self::getSoltCount();
        // log_message("debug", "** addSolt **");
        // log_message("debug", "{$preSoltCount}, {$sufSoltCount}");
        // log_message("debug", "Orig：" . $value);
        // log_message("debug", "** addSolt **");
        $randStr = '0123456789abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz';

        $randStr = str_shuffle($randStr);
        $pre = substr($randStr, 0, $preSoltCount);
        $suf = substr($randStr, $preSoltCount, $sufSoltCount);

        $value = $pre . $value . $suf;

        return $value;
    }

    /**
     * 檢查 payload 是否超時
     * @access public
     * @return bool
     */
    public static function isTimeout(): bool
    {
        if (!isset(self::$payload) || !isset(self::$payload['exp'])) {
            return true;
        }
        return self::$payload['exp'] - time() <= 0;
    }

    /**
     * Receives JWT authentication header and returns a string.
     * @access public
     * @param RequestInterface $request
     * @return string
     */
    public static function getToken(RequestInterface $request)
    {
        // extract the token from the header
        $header = $request->getHeader("auth") ?? "";
        if (!empty($header)) {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                return $matches[1];
            }
        } else {
            return $request->getVar('token') ?? "";
        }
        return "";
    }

    /**
     * Validates the token by decrypting
     * @access public
     * @param string $token
     * @return array
     */
    public static function validateRequest(string $token, string $privateKey = "")
    {

        try {
            self::$payload = (array) JWT::decode($token, new Key($privateKey, 'HS256'));
        } catch (\Firebase\JWT\ExpiredException $e) {
            log_message('error', "token decode error.");
            log_message('error', $e->getMessage());
            return false;
        }
        // log_message('debug', json_encode(self::$payload));

        $jti = self::removeSolt(self::$payload['jti']);

        $ckJti = self::signJti();

        if ($jti != $ckJti) {
            // 檢查簽名，如果有錯則返回 false
            log_message('error', "！！！Token Sign Error.");
            log_message('error', "removeSolt: " . $jti);
            return false;
        }

        // 解密之後可以用裡面的資料取值
        return self::$payload['data'];
    }

    /**
     * Signs a new token.
     * @access public
     * @param array $data
     * @param string $privateKey
     * @param string $tokenLifetime
     * @return string
     */
    public static function signature(array $data, string $privateKey = "", int $tokenLifetime = 720)
    {
        $time = time();
        $expiration = $time + ($tokenLifetime * 60);
        self::$payload = [
            "iss" => $_SERVER['HTTP_HOST'] ?? "John Doe",
            "aud" => $_SERVER['HTTP_REFERER'] ?? "John Doe",
            "sub" => "You Have To Let Go",
            'data' => $data,
            'iat' => $time,
            'exp' => $expiration,
        ];

        self::$payload['jti'] = self::signJti();

        self::$payload['jti'] = self::addSolt(self::$payload['jti']);

        return JWT::encode(self::$payload, $privateKey, 'HS256', null, ['jti' => self::$payload['jti']]);
    }

    // 取得 JWS 的值，這應該會常用
    public static function getData()
    {
        if (!isset(self::$payload['data'])) {
            return [];
        }
        $data = self::$payload['data'];
        if (is_object($data)) {
            return json_decode(json_encode($data), true);
        }
        return $data;
    }

    public static function getPayload()
    {
        return self::$payload;
    }
}
