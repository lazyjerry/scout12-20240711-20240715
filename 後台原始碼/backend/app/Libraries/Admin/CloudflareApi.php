<?php

namespace App\Libraries\Admin;

use App\Libraries\Api;

// for non-static version
class CloudflareApi extends Api
{
    protected $baseUrl = "";
    public $response = null;
    public $client = null;
    public $lastUrl = null;

    public function __construct()
    {
        $this->baseUrl = $_ENV['CLOUDFLARE_API_URL'];
    }

    public function getList(string $workshopUserName, string $session)
    {
        $data = [
            "token" => md5($_ENV['JWT_SECRET']),
            "key" => "admin",
            "data" => [],
            "action" => "sync-list"];
        return $this->curlPost($this->baseUrl, jsonEncodeChinese($data), []);
    }

    // 創建、更新工作坊設定
    public function registerWorkShop(string $username, string $md5_password, string $name, string $sessions)
    {

        $data = [
            "token" => md5($_ENV['JWT_SECRET']),
            "key" => "admin",
            "data" => [
                'type' => 'register',
                'name' => $name,
                'username' => $username,
                'sessions' => $sessions,
            ],
            "action" => "sync-put"];
        // 這裡需注意使用前就要編碼好
        // 通常是從資料庫撈出來的，資料庫編碼前已經是 md5 處理
        $data['data']['password'] = $md5_password;

        return $this->curlPost($this->baseUrl, jsonEncodeChinese($data), []);
    }

    // 取得簽到簽出清單
    public function getSignData(string $username, string $session)
    {
        $data = [
            "token" => md5($_ENV['JWT_SECRET']),
            "key" => "admin",
            "data" => [
                'username' => $username,
                'session' => $session,
            ],
            "action" => "sync-list"];

        return $this->curlPost($this->baseUrl, jsonEncodeChinese($data), []);
    }

    public function deleteData($target)
    {
        $data = [
            "token" => md5($_ENV['JWT_SECRET']),
            "key" => "admin",
            "data" => [
                'type' => 'delete',
                'target' => $target == 'signlog' ? 'signlog' : 'workshop',
                'really' => true,
            ],
            "action" => "sync-put"];

        return $this->curlPost($this->baseUrl, jsonEncodeChinese($data), []);
    }
}
