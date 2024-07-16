<?php

namespace App\Libraries;

class BaseApi
{
    protected static $baseUrl = "";
    public static $response = null;
    // https://github.com/codeigniter4/CodeIgniter4/blob/develop/system/HTTP/CURLRequest.php
    public static $client = null;
    public static $lastUrl = null;

    protected static $allowStatusCodes = ['200', '201', '304'];

    private static function getNewOptions(array $headers = [], string $body = "")
    {
        // https://codeigniter4.github.io/userguide/libraries/curlrequest.html#making-requests
        $options = [
            'http_errors' => false,
            'debug' => ("production" != $_ENV['CI_ENVIRONMENT']),
            'verify' => false, // CURLOPT_SSL_VERIFYPEER = 0
            'timeout' => 5,
            'allow_redirects' => false,
            'headers' => [
                'User-Agent' => 'testing/1.0',
                'Accept' => 'application/json; charset=utf-8',
                'Content-Type' => 'application/json',
            ],
        ];

        if (!empty($headers)) {
            $options['headers'] = (!isset($options['headers'])) ? $headers : array_merge($options['headers'], $headers);
        }

        if (!empty($body)) {
            $options['body'] = $body;
        }

        return $options;
    }

    protected static function jsonEncodeChinese($array)
    {
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public static function getLastUrl()
    {
        return self::$lastUrl ?? "";
    }

    public static function getUrl(string $path)
    {
        self::$lastUrl = self::$baseUrl . $path;
        return self::$lastUrl;
    }

    // 取返回狀態碼
    public static function getStatusCode()
    {
        if (isset(self::$response)) {
            return self::$response->getStatusCode();
        }
    }

    // 取 body
    public static function getBody(bool $onlyOk = true)
    {
        if (!$onlyOk) {
            return self::$response->getBody();
        }

        $status = self::getStatusCode();
        if (in_array($status, self::$allowStatusCodes)) {
            return self::$response->getBody();
        }
        return false;
    }

    public static function curlPost(string $url, string $body = "", array $headers = [])
    {

        if (!isset(self::$client)) {
            self::$client = \Config\Services::curlrequest();
        }
        // https://codeigniter4.github.io/CodeIgniter4/libraries/curlrequest.html?highlight=curl#working-with-the-library
        // $url = self::getUrl($path);

        $options = self::getNewOptions($headers, $body);
        try {
            self::$response = self::$client->request('POST', $url, $options);
        } catch (\Exception $e) {
            log_message("error", "***");
            log_message("error", "curlPost ERROR");
            log_message("error", "url: " . $url);
            log_message("error", "options: " . jsonEncodeChinese($options));
            log_message("error", "error msg: " . $e->getMessage());
            log_message("error", "***");
            return false;
        }

        return self::getBody();
    }

    public static function curlGet(string $url, array $headers = [])
    {

        if (!isset(self::$client)) {
            self::$client = \Config\Services::curlrequest();
        }
        // https://codeigniter4.github.io/CodeIgniter4/libraries/curlrequest.html?highlight=curl#working-with-the-library

        $options = self::getNewOptions($headers);
        try {
            self::$response = self::$client->request('GET', $url, $options);

        } catch (\Exception $e) {
            log_message("error", "***");
            log_message("error", "curlGet ERROR");
            log_message("error", "url: " . $url);
            log_message("error", "options: " . jsonEncodeChinese($options));
            log_message("error", "error msg: " . $e->getMessage());
            log_message("error", "***");
            return false;
        }

        return self::getBody();
    }
}
