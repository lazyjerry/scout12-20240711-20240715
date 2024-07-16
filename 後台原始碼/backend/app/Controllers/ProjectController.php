<?php

namespace App\Controllers;

use App\Libraries\Admin\AdminLib;
use App\Libraries\Admin\CloudflareApi;
use App\Libraries\JwtUtils;
use App\Libraries\Project\ProjectLib;
use App\Libraries\TimeUtils;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Config\Factories;

/**
 * 針對專案使用的 BaseController 擴展
 * 請勿在其中添加可用的 public 輸出
 * @internal
 */
class ProjectController extends BaseController
{

    /**
     * 存取管理員用途
     * @internal
     * @var App\Models\UserModel
     */
    private $userModel = null;

    /**
     * @see https://codeigniter4.github.io/userguide/outgoing/api_responses.html
     */
    use ResponseTrait;

    /**
     * overwrite from ResponseTrait
     * @var array 覆蓋原本 ResponseTrait 的 codes 參數
     * https://github.com/codeigniter4/CodeIgniter4/blob/develop/system/API/ResponseTrait.php
     */
    protected $codes = [
        'created' => 201,
        'deleted' => 200,
        'updated' => 200,
        'no_content' => 204,
        'invalid_request' => 400,
        'unsupported_response_type' => 400,
        'invalid_scope' => 400,
        'temporarily_unavailable' => 400,
        'invalid_grant' => 400,
        'invalid_credentials' => 400,
        'invalid_refresh' => 400,
        'no_data' => 400,
        'invalid_data' => 400,
        'access_denied' => 401,
        'unauthorized' => 401,
        'invalid_client' => 401,
        'forbidden' => 403,
        'resource_not_found' => 404,
        'not_acceptable' => 406,
        'resource_exists' => 409,
        'conflict' => 409,
        'resource_gone' => 410,
        'payload_too_large' => 413,
        'unsupported_media_type' => 415,
        'too_many_requests' => 429,
        'server_error' => 500,
        'unsupported_grant_type' => 501,
        'not_implemented' => 501,
    ];

    protected function successRespone(string $actionCode, array $data, string $message = '', array $messages = [])
    {
        return $this->setResponseData($actionCode, $data, '', $message, $messages);
    }

    protected function failResponse(string $actionCode, string $error, string $message = "", array $messages = [])
    {
        return $this->setResponseData($actionCode, [], $error, $message, $messages);
    }

    private function setResponseData(string $actionCode = 'updated', array $data = [], string $error = '', string $message = '', array $messages = [])
    {

        $responseData = ProjectLib::initResponseData($this->codes[$actionCode], TimeUtils::getNow(), $error, $message, $messages, $data);

        return $this->setResponseFormat('json')->respond($responseData, 200);
    }

    protected function logfailed(string $message, string $code, string $callPathOrUrl, string $category = 'admin')
    {
        return $this->log("error", $message, $code, $callPathOrUrl, $category);
    }

    // 這個紀錄讓他不顯示，未來有需要再開啓
    protected function logSuccess(string $message, string $code, string $callPathOrUrl, string $category = 'admin')
    {
        // return $this->log("success", $message, $code, $callPathOrUrl, $category);
        return true;
    }

    protected function logInfo(string $message, string $code, string $callPathOrUrl, string $category = 'admin')
    {
        return $this->log("info", $message, $code, $callPathOrUrl, $category);
    }

    private function log(string $type, string $message, string $code, string $callPathOrUrl, string $category = 'admin')
    {

        $logModel = Factories::models('LogModel', ['getShared' => true]);

        $data = [
            "type" => "error",
            "code" => $code,
            "message" => $message,
            "path" => $callPathOrUrl,
            "category" => $category,
        ];

        return $logModel->insert($data);
    }

    /**
     * Initializer
     * @internal
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {

        parent::initController($request, $response, $logger);
        TimeUtils::getNow();
    }

    /**
     * index
     * @internal
     */
    public function index()
    {
        $this->respondCreated();
    }

    // --- protected function ---

    // 取得現有的用戶資料
    protected function getCurrentUserData($arguments = [])
    {
        // 這裡會強制清除快取
        $data = JWTUtils::getData();
        $userModel = \CodeIgniter\Config\Factories::models('UserModel', ['getShared' => true]);

        $conditions = ['user_slug' => $data['user_slug']];

        $user = $userModel->getUser($conditions, $arguments);
        if (empty($user)) {
            throw new \LogicException('該用戶不存在但卻進入至該控制項中');
        }
        $GLOBALS['currentUserData'] = $user;
        return $user;
    }

    // 是否登入
    protected function isLogin()
    {
        if (!isset($GLOBALS['currentUserData'])) {
            $this->getCurrentUserData();
        }

        return (isset($GLOBALS['currentUserData']) && !empty($GLOBALS['currentUserData']));
    }

    // ------
    // 專案處理檔案
    // ------
    //

    // 取得 KV 上的資料
    protected function _getSignData(string $workshopUsername, string $session, string $flags = "")
    {

        $flags = (empty($flags)) ? __FUNCTION__ : $flags;
        if (empty($workshopUsername)) {
            $errorCode = $flags . "001";
            $errorMsg = "查無該工作坊，請確認是否有資料";
            //$this->logfailed($errorMsg, $errorCode, (string) $this->request->getUri(), 'admin');

            return $this->failResponse('invalid_data', $errorCode, $errorMsg);
        }

        $workshopModel = Factories::models('WorkshopModel', ['getShared' => true]);

        $conditions['workshop_username'] = $workshopUsername;

        $arguments = ['limit' => 1, 'offset' => 0, 'orderBy' => 'id DESC', 'select' => '*'];
        $workshopModel->preWhere($conditions);
        $workshopModel->preArg($arguments);
        $row = $workshopModel->getObject(true);
        if (!isset($row) || empty($row)) {
            $errorCode = $flags . "002";
            $errorMsg = "查無該工作坊，請確認是否有資料";
            $this->logfailed($errorMsg, $errorCode, (string) $this->request->getUri(), 'admin');

            return $this->failResponse('invalid_data', $errorCode, $errorMsg);
        }
        // 取得最近一筆 session 發送
        $session = empty($session) ? AdminLib::getClosestSession($row->sessions) : $session;

        if (empty($session)) {
            $errorCode = $flags . "003";
            $errorMsg = "查無該工作坊場次，請確認當下時間，以及是否有指定場次";
            $this->logfailed($errorMsg, $errorCode, (string) $this->request->getUri(), 'admin');

            return $this->failResponse('invalid_data', $errorCode, $errorMsg);
        }

        $signLogModel = Factories::models('SignLogModel', ['getShared' => true]);
        $respData = [];

        $cloudflareApi = new CloudflareApi();
        $syncResult = $cloudflareApi->getSignData($workshopUsername, $session);

        $arr = jsonDecode($syncResult);

        $respData = ['requestSuccess' => false, 'totalCount' => 0, 'successCount' => 0, 'failedCount' => 0, 'username' => $workshopUsername, 'session' => $session];

        if (!empty($arr['isSuccess']) && isset($arr['result']['checkSuccess']) && $arr['result']['checkSuccess']) {
            $respData['requestSuccess'] = true;
            $signLogModel = Factories::models('SignLogModel', ['getShared' => true]);

            if (!isset($arr['result']['data'][$session])) {
                $this->logSuccess("執行成功(查無場次)", $flags . "001", (string) $this->request->getUri(), 'api');
                return $this->successRespone("updated", $respData);
            }

            $userArr = $arr['result']['data'][$session];
            $dateFormated = "Y-m-d H:i:s";

            // 解析簽到簽出資料
            foreach ($userArr as $memberNum => $signTimes) {
                // 取得簽到簽出時間戳，返回字串
                $in = ('0' == $signTimes['inTime'] || empty($signTimes['inTime'])) ? "0000-00-00 00:00:00" : date($dateFormated, intval($signTimes['inTime']));
                $out = ('0' == $signTimes['outTime'] || empty($signTimes['outTime'])) ? "0000-00-00 00:00:00" : date($dateFormated, intval($signTimes['outTime']));

                $data = ['workshop_username' => $workshopUsername, 'member_num' => $memberNum, 'workshop_session' => $session, 'sign_in' => $in, 'sign_out' => $out];

                // 判斷是相同一筆簽到簽出的條件，已經有寫入一樣的()則跳過（注意 sql 要加 index
                $conditions = ['workshop_username' => $workshopUsername, 'member_num' => $memberNum, 'workshop_session' => $session];
                $isSuccess = $signLogModel->syncData($data, $conditions);

                $respData['totalCount'] += 1;
                $respData[((false === $isSuccess) ? 'failedCount' : 'successCount')] += 1;
            }
        }

        $this->logSuccess("執行成功", $flags . "001", (string) $this->request->getUri(), 'api');
        return $this->successRespone("updated", $respData);
    }
}
