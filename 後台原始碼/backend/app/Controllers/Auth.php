<?php

namespace App\Controllers;

use App\Controllers\ProjectController;
use App\Libraries\Admin\AdminLib;
use App\Libraries\JwtUtils;
use CodeIgniter\Config\Factories;

/**
 * 登入驗證用途，請先確認使用 session 或是 jwt 登入
 * @package Admin
 * @see https://www.binaryboxtuts.com/php-tutorials/codeigniter-4-json-web-tokenjwt-authentication/
 */
class Auth extends ProjectController
{
    /**
     * Initializer
     * @internal
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    /**
     * index
     * @internal
     */
    public function index()
    {
        return $this->respondCreated();
    }

    /**
     * 註冊
     * @api
     * @deprecated
     * @param string username 用戶帳號
     * @param string password 用戶密碼
     * @param string firstName 名字
     * @param string lastName 姓氏
     * @return string Json string 註冊成功將自動登入返回登入信息，反之提供錯誤信息
     */
    public function register()
    {
        die("deprecated");
    }

    /**
     * 登入
     * @api
     * @param string username 用戶帳號
     * @param string password 用戶密碼
     * @return string Json-string 返回登入成功或失敗信息
     */
    public function login()
    {
        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');

        $userModel = Factories::models('UserModel', ['getShared' => true]);

        $user = $userModel->where('username', $username)->first();

        if (is_null($user)) {
            $this->logfailed("查無此用戶", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
            return $this->failResponse('invalid_client', __FUNCTION__ . "001", 'Invalid username or password.');
        }

        $pwdVerify = password_verify($password, $user['password']);

        if (!$pwdVerify) {
            $this->logfailed("登入錯誤", __FUNCTION__ . "002", (string) $this->request->getUri(), 'admin');
            return $this->failResponse('invalid_client', __FUNCTION__ . "l002", 'Invalid username or password.');
        }

        $respData = [];
        $respMessage = '';
        // 印出資料
        $respData['user'] = $user;
        unset($respData['user']['id']);
        unset($respData['user']['password']);
        unset($respData['user']['created_at']);
        unset($respData['user']['updated_at']);

        $key = $_ENV['JWT_SECRET'];

        // 簽名取 user_slug
        $jwsData = ['user_slug' => $user['user_slug']];

        // 簽名 12 小時
        $token = JwtUtils::signature($jwsData, $key, 12 * 60);

        if (!$_ENV['IS_USE_JWT']) {
            $respMessage = 'Login Succesful.Please refresh page.';

            $session = session();
            $newdata = [
                'userId' => $user['id'],
                'userPayload' => JwtUtils::getPayload(),
            ];

            $session->set($newdata);

        } else {
            $respMessage = 'Login Succesful';
            $respData['token'] = $token;

        }
        $respData['config'] = [
            'allSessions' => AdminLib::getAllowSessions(),
        ];

        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
        return $this->successRespone("created", $respData, $respMessage);
    }
}
