<?php

namespace App\Libraries;

// for non-static version
class Api
{
    protected $baseUrl = "";
    public $response = null;
    public $client = null;
    public $lastUrl = null;

    protected $allowStatusCodes = ['200', '201', '304'];

    private function getNewOptions(array $headers = [], string $body = "")
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

    protected function jsonEncodeChinese($array)
    {
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getLastUrl()
    {
        return $this->lastUrl ?? "";
    }

    public function getUrl(string $path)
    {
        $this->lastUrl = $this->baseUrl . $path;
        return $this->lastUrl;
    }

    // 取返回狀態碼
    public function getStatusCode()
    {
        if (isset($this->response)) {
            return $this->response->getStatusCode();
        }
    }

    // 取 body
    public function getBody(bool $onlyOk = true)
    {
        if (!$onlyOk) {
            return $this->response->getBody();
        }

        $status = $this->getStatusCode();
        if (in_array($status, $this->allowStatusCodes)) {
            return $this->response->getBody();
        }
        return false;
    }

    public function curlPost(string $url, string $body = "", array $headers = [])
    {

        if (!isset($this->client)) {
            $this->client = \Config\Services::curlrequest();
        }
        // https://codeigniter4.github.io/CodeIgniter4/libraries/curlrequest.html?highlight=curl#working-with-the-library
        // $url = $this->getUrl($path);

        $options = $this->getNewOptions($headers, $body);
        try {
            $this->response = $this->client->request('POST', $url, $options);

        } catch (\Exception $e) {
            log_message("error", "***");
            log_message("error", "curlPost ERROR");
            log_message("error", "url: " . $url);
            log_message("error", "options: " . jsonEncodeChinese($options));
            log_message("error", "error msg: " . $e->getMessage());
            log_message("error", "***");
            return false;
        }

        return $this->getBody();
    }

    public function curlGet(string $url, array $headers = [])
    {

        if (!isset($this->client)) {
            $this->client = \Config\Services::curlrequest();
        }
        // https://codeigniter4.github.io/CodeIgniter4/libraries/curlrequest.html?highlight=curl#working-with-the-library

        $options = $this->getNewOptions($headers);
        try {
            $this->response = $this->client->request('GET', $url, $options);

        } catch (\Exception $e) {
            log_message("error", "***");
            log_message("error", "curlGet ERROR");
            log_message("error", "url: " . $url);
            log_message("error", "options: " . jsonEncodeChinese($options));
            log_message("error", "error msg: " . $e->getMessage());
            log_message("error", "***");
            return false;
        }

        return $this->getBody();
    }
}
