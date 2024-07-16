<?php

namespace App\Filters;

use App\Libraries\JwtUtils;
use App\Libraries\Project\ProjectLib;
use App\Libraries\TimeUtils;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {

        // --- DEMO START ---
        if ("production" != $_ENV['CI_ENVIRONMENT']) {
            $authKey = $request->getHeaderLine('auth') ?? "";

            if (empty($authKey)) {
                $authKey = $request->getVar('token') ?? "";
            }

            if (!empty($_ENV['DEV_AUTH_KEY']) && $_ENV['DEV_AUTH_KEY'] == $authKey) {

                $userModel = \CodeIgniter\Config\Factories::models('UserModel', ['getShared' => true]);

                $user = $userModel->getDemoUser();
                $key = $_ENV['JWT_SECRET'];
                $jwsData = ['user_slug' => $user['user_slug']];
                // 簽名 12 小時
                $token = JwtUtils::signature($jwsData, $key, 12 * 60);

                if (!$_ENV['IS_USE_JWT']) {
                    $session = session();
                    $newdata = [
                        'userId' => $user['id'],
                        'userPayload' => JwtUtils::getPayload(),
                    ];

                    $session->set($newdata);
                    //NOTE 使用 session 全域方法，不用擔心同次 request 會讀不到寫入的資料
                } else {

                    // log_message("debug", "Sign demo user by authKey: " . json_encode(JwtUtils::getPayload(), JSON_UNESCAPED_UNICODE));
                    // log_message("debug", "Sign demo token by authKey: " . $token);
                    log_message("debug", "Sign by demo user");
                }

                return;
            }
        }
        // --- DEMO END ---

        if (!$_ENV['IS_USE_JWT']) {

            $session = session();
            $userId = $session->get('userId');
            if (is_null($userId) || empty($userId)) {
                return $this->response("filiter001", '尚未登入');
            }

        } else {

            $token = JwtUtils::getToken($request);

            // check if token is null or empty
            if (is_null($token) || empty($token)) {

                return $this->response("filiter002", '尚未登入，請重新登入');
            }

            try {
                $key = getenv('JWT_SECRET');
                $decoded = JwtUtils::validateRequest($token, $key);

                if (is_null($decoded) || empty($decoded)) {
                    return $this->response("filiter003", '尚未登入，請重新登入');
                }

                $isTimeout = JwtUtils::isTimeout();
                if ($isTimeout) {
                    return $this->response("filiter004", '尚未登入，請重新登入');
                }

            } catch (Exception $ex) {

                return $this->response("filiter005", '系統錯誤，請檢查登入信息');
            }
        }
    }

    private function response($error, $message = "Unauthorized")
    {
        $response = service('response');

        $response->setStatusCode(200);

        $responseData = ProjectLib::initResponseData(401, TimeUtils::getNow(), $error, $message, [], []);

        $response->setJSON($responseData);

        return $response;
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
