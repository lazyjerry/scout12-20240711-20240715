<?php

namespace App\Controllers;

use App\Controllers\ProjectController;
use App\Libraries\Admin\AdminLib;
use App\Libraries\Admin\CloudflareApi;
use CodeIgniter\Config\Factories;

/**
 * 與前台溝通 API 於此呼叫，該類別中無須登入
 * @package API
 */
class Api extends ProjectController
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
        $respData = ['ver' => PROJECT_VERSION];
        return $this->successRespone("updated", $respData);
    }

    private function getWorkshopObject(&$workshopModel, $workshopUsername)
    {
        $conditions = ['workshop_username' => $workshopUsername];
        $arguments = ['limit' => 1, 'offset' => 0, 'orderBy' => 'id DESC', 'select' => '*'];
        $workshopModel->preWhere($conditions);
        $workshopModel->preArg($arguments);
        return $workshopModel->getObject(true);
    }

    // 同步資料到 cloudflare
    private function syncWorkshopRemote($workshopRow)
    {
        $cloudflareApi = new CloudflareApi();
        $syncResult = $cloudflareApi->registerWorkShop($workshopRow->workshop_username, $workshopRow->workshop_password, $workshopRow->workshop_name, $workshopRow->workshop_sessions);
        return $syncResult;
    }

    private function checkToken()
    {
        if (md5($_ENV['JWT_SECRET']) != ($this->request->getHeader('auth') ?? "")) {
            $errorCode = __FUNCTION__ . "000";
            $errorMsg = "參數錯誤";
            //$this->logfailed($errorMsg, $errorCode, (string) $this->request->getUri(), 'api');

            return $this->failResponse('invalid_data', $errorCode, $errorMsg);
        }
    }

    /**
     * 取得當下天氣
     * @api
     * @path /api/getWeater
     * @return string 返回 Json-string 格式。返回前台的資料： weater=天氣資訊、temp=體感溫度、feels_like= 體感溫度
     */
    public function getWeater()
    {
        $this->checkToken();

        $weeaterKey = $_ENV['WEATER_KEY'];
        $url = "https://api.openweathermap.org/data/2.5/weather?units=metric&lat=23.132670729170986&lon=120.42738273684154&appid={$weeaterKey}&lang=zh_TW";
        $result = file_get_contents($url);

        if (!isJson($result)) {
            $errorCode = __FUNCTION__ . "001";
            $errorMsg = "天氣取得失敗";
            this->logfailed($errorMsg, $errorCode, (string) $this->request->getUri(), 'api');

            return $this->failResponse('invalid_data', $errorCode, $errorMsg);
        }

        $respData = [];

        $arr = jsonDecode($result);

        $respData["date"] = date("Y-m-d H:i:s");

        $respData["weater"] = $arr['weather'];
        $respData["refWeater"] = "https://openweathermap.org/weather-conditions";

        $respData["temp"] = $arr['main']['temp'];
        $respData["feels_like"] = $arr['main']['feels_like'];
        $respData["ref"] = "https://openweathermap.org/current#fields_json";

        $respData["source"] = $arr;

        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'api');
        return $this->successRespone("updated", $respData);

        // https://home.openweathermap.org/

    }

    /**
     * 取得工作坊的簽到資料.
     * @api
     * @path /api/getWorkShops
     * @return string 返回 Json-string 格式。返回前台的設定資料
     */
    public function getWorkShops()
    {
        $this->checkToken();

        $timeStr = $this->request->getVar('time') ?? date("Y-m-d H:i:s");

        $workshopModel = Factories::models('WorkshopModel', ['getShared' => true]);
        $signLogModel = Factories::models('SignLogModel', ['getShared' => true]);

        $arguments = ['orderBy' => 'id DESC', 'select' => 'id,workshop_name,workshop_username,workshop_sessions,workshop_area'];
        $workshopModel->preArg($arguments);
        $respData = [];

        $mapOfSessions = AdminLib::getMapOfSessions();
        // $sessions = ["071101", "071104", "071201", "071202", "071203", "071204", "071401", "071402", "071403", "071404", "071501", "071502", "071503", "071504"];
        $sessions = AdminLib::getAllowSessions();
        $time = strtotime($timeStr);

        $respData["colsestSessionGlobal"] = AdminLib::getTheClosestSession($sessions, $mapOfSessions, $time);
        $workshops = $workshopModel->getArray();

        $respData["workshops"] = [];
        foreach ($workshops as $workshop) {
            // 如果當下是確定有場次的話
            if (!empty($respData["colsestSessionGlobal"])) {

                $colsestSession = AdminLib::getClosestSession($workshop['workshop_sessions'], $time);

                // $conditions = ['workshop_username' => $workshop['workshop_username'], 'workshop_session' => $colsestSession, 'sign_in !=' => '0000-00-00 00:00:00'];
                // 改成有紀錄就好
                $conditions = ['workshop_username' => $workshop['workshop_username'], 'workshop_session' => $colsestSession];
                $arguments = ['limit' => 1, 'offset' => 0, 'orderBy' => 'id DESC', 'select' => 'count(id) as count'];
                $signLogModel->preWhere($conditions);

                $signLogModel->preArg($arguments);
                $rowArr = $signLogModel->getArray(true);
                $workshop['count'] = intval($rowArr['count']);
                $workshop['colsestSession'] = $colsestSession;

            } else {
                $workshop['count'] = 0;
            }
            unset($workshop['workshop_sessions']);

            $respData["workshops"][$workshop['workshop_username']] = $workshop;
        }

        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'api');
        return $this->successRespone("updated", $respData);
    }

    /**
     * 取得前台設定資料. 取得工作坊的資料
     * @api
     * @path /api/getConfig
     * @return string 返回 Json-string 格式。返回前台的設定資料
     */
    public function getConfig()
    {
        $this->checkToken();

        $workshopModel = Factories::models('WorkshopModel', ['getShared' => true]);

        $arguments = ['orderBy' => 'id DESC', 'select' => 'id,workshop_name,workshop_username,workshop_sessions,workshop_area'];
        $workshopModel->preArg($arguments);
        $respData = [];

        $mapOfSessions = AdminLib::getMapOfSessions();
        // $sessions = ["071101", "071104", "071201", "071202", "071203", "071204", "071401", "071402", "071403", "071404", "071501", "071502", "071503", "071504"];
        $sessions = AdminLib::getAllowSessions();
        $timeStr = $this->request->getVar('time') ?? date("Y-m-d H:i:s");
        $timeStr = empty($timeStr) ? date("Y-m-d H:i:s") : $timeStr;
        $time = strtotime($timeStr);
        $respData["colsestSession"] = AdminLib::getTheClosestSession($sessions, $mapOfSessions, $time);
        $workshops = $workshopModel->getArray();

        $respData["workshops"] = array_column($workshops, null, 'workshop_username');

        $sessions = AdminLib::getAllowSessions();
        $respData['sessions'] = $sessions;

        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'api');
        return $this->successRespone("updated", $respData);
    }

    /**
     * 從 kv 取得簽到簽出資料，同步至資料庫.
     * @api
     * @path /api/syncSignData
     * @uses string workshop 工作坊編號
     * @uses string session  工作坊場次可以指定也可以為空不傳送
     * 依照參數取得簽到簽出資料
     * @return string 返回 Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function syncSignData()
    {
        $this->checkToken();

        $workshopUsername = $this->request->getVar('workshop') ?? "";
        $session = $this->request->getVar('session') ?? "";
        return $this->_getSignData($workshopUsername, $session, __FUNCTION__);
    }

    /**
     * 返回所有 sessions.
     *
     * 取得所有 session 資料
     * @api
     * @path /api/getAllSessions
     * @return string 返回 Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function getAllSessions()
    {
        // ignore_user_abort(true);
        // set_time_limit(0);

        $this->checkToken();
        $sessions = AdminLib::getAllowSessions();
        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'api');
        return $this->successRespone("updated", $sessions);
    }

    /**
     * 同步工作坊帳號.
     *
     * 匯出工作坊帳號至 kv
     * @api
     * @path /api/syncWorkshopAccount
     * @uses string workshop 工作坊編號
     * @uses string session  工作坊場次
     * @return string 返回 Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function syncWorkshopAccount()
    {
        $this->checkToken();
        $workshopUsername = $this->request->getVar('workshopUsername') ?? "";
        if (empty($workshopUsername)) {
            $errorCode = __FUNCTION__ . "001";
            $errorMsg = "參數錯誤";
            this->logfailed($errorMsg, $errorCode, (string) $this->request->getUri(), 'api');

            return $this->failResponse('invalid_data', $errorCode, $errorMsg);
        }

        $respData = [];

        $workshopModel = Factories::models('WorkshopModel', ['getShared' => true]);

        $row = $this->getWorkshopObject($workshopModel, $workshopUsername);
        if (!isset($row) || empty($row)) {
            $errorCode = __FUNCTION__ . "002";
            $errorMsg = "無法同步，查無該工作坊，請確認是否有資料";
            this->logfailed($errorMsg, $errorCode, (string) $this->request->getUri(), 'api');

            return $this->failResponse('invalid_data', $errorCode, $errorMsg);
        }

        // 同步資料
        $syncResult = $this->syncWorkshopRemote($row);
        $respData['result'] = jsonDecode($syncResult, true);

        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'api');
        return $this->successRespone("updated", $respData);
    }

// --------  開發期間使用的 API START ------------
// 如果有需要，解鎖操作使用。注意正式環境不要使用
// --------  開發期間使用的 API END ------------

    private function denyProduction()
    {
        if ("production" != $_ENV['CI_ENVIRONMENT']) {
            die("不可使用");
        }
    }

    /**
     * 創建 demo 學員帳號.
     *
     * (update: 240705 已進入測試階段，先停用該功能)
     *
     * @api
     * @path /api/demoCreateMemberAccount/

     * @return string 返回 Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function demoCreateMemberAccount()
    {
        $this->denyProduction();
        ignore_user_abort(true);
        set_time_limit(0);

        $this->checkToken();
        $memberModel = Factories::models('MemberModel', ['getShared' => true]);
        $memberModel->truncate();
        $respData = [];
        $respData['result'] = [];
        for ($count = 1; $count <= 8000; $count++) {
            $num = "" . (100000 + $count);
            $areaNo = ($count % 12 + 1);
            $area = "第" . $areaNo . "號分營區";
            $scoutNo = $areaNo * 10 + ($count % 10);
            $scoutName = $scoutNo . "號童軍團";
            $scoutNum = "平行縣第" . $scoutNo . "團";
            $data = [
                "member_num" => $num, "member_name" => "大壯第" . (100000 + $count) . "號", "member_area" => $area, "scout_name" => $scoutName, "scout_num" => $scoutNum, "member_phone" => "0911123456", "member_contact_name" => "帶隊老師第" . (100000 + $count) . "號", "member_contact_phone" => "0911123456",
            ];

            $is_success = $memberModel->insert($data);
            $respData['result'][] = $is_success;
            if ($is_success) {
                $respData['result'][] = $data['member_num'];
            }
        }

        return $this->successRespone("updated", $respData);
    }

    /**
     * 創建 demo 簽到記錄.
     *
     * (update: 240705 已進入測試階段，先停用該功能)
     * @api
     * @path /api/demoCreateSignLog/

     * @return string 返回 Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function demoCreateSignLog()
    {
        $this->denyProduction();
        ignore_user_abort(true);
        set_time_limit(0);

        $this->checkToken();
        $signLogModel = Factories::models('SignLogModel', ['getShared' => true]);
        $signLogModel->truncate();
        $respData = [];
        $respData['result'] = [];

        $sessions = ["071101", "071201", "071202", "071401", "071402", "071403", "071504"];
        // $sessions = AdminLib::getAllowSessions();
        $now = date("Y-m-d H:i:s");

        // 130 人
        for ($count = 1; $count <= 130; $count++) {

            // 工作坊
            for ($workshopCount = 1; $workshopCount <= 150; $workshopCount++) {
                $workshopUsername = "" . (100000 + $workshopCount);
                // 場次
                foreach ($sessions as $session) {

                    $memberNum = "" . (100000 + ($count % 8000));

                    // 約有 3/4 機率簽到
                    $signIn = (rand(0, 100) % 4 > 0) ? $now : '0000-00-00 00:00:00';
                    // 如果沒有簽到記錄，則不會有簽出記錄
                    // 有簽到記錄狀況下約 4/5 機率簽出
                    $signOut = ($signIn == '0000-00-00 00:00:00') || (rand(0, 100) % 5 == 0) ? '0000-00-00 00:00:00' : $now;

                    $data = ["workshop_username" => $workshopUsername, "member_num" => $memberNum, "workshop_session" => $session, "sign_in" => $signIn, "sign_out" => $signOut];

                    $is_success = $signLogModel->insert($data);
                    $respData['result'][] = $is_success;
                    if ($is_success) {
                        $respData['result'][] = $is_success;
                    }
                }
            }
        }

        return $this->successRespone("updated", $respData);
    }

    /**
     * 創建 demo 工作坊帳號.
     *
     * 將會匯出工作坊帳號至 kv (update: 240705 已進入測試階段，先停用該功能)
     * @api
     * @path /api/demoCreateWorkshopAccount/
     * @return string 返回 Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function demoCreateWorkshopAccount()
    {
        $this->denyProduction();
        ignore_user_abort(true);
        set_time_limit(0);

        $this->checkToken();

        $workshopModel = Factories::models('WorkshopModel', ['getShared' => true]);
        $workshopModel->truncate();

        $respData = [];
        $workshopUsernames = [];

        $respData['local'] = [];
        $sessionsStr = implode(',', AdminLib::getAllowSessions());
        for ($count = 1; $count <= 150; $count++) {
            $name = "" . (100000 + $count);
            $workshopArea = "第 " . ($count % 10 + 1) . " 號活動中心";
            $data = [
                "workshop_name" => $name . "工作坊", "workshop_username" => $name, "workshop_password" => $name, "workshop_sessions" => $sessionsStr, "workshop_area" => $workshopArea,
            ];
            // var_dump($data);die("**");
            $is_success = $workshopModel->insert($data);
            $respData['local'][] = $is_success;
            if ($is_success) {
                $workshopUsernames[] = $data['workshop_username'];
            }
        }

        $respData['result'] = [];
        foreach ($workshopUsernames as $workshopUsername) {

            $row = $this->getWorkshopObject($workshopModel, $workshopUsername);

            // 同步資料
            $syncResult = $this->syncWorkshopRemote($row);
            $respData['result'][] = jsonDecode($syncResult, true);

            $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'api');
        }

        return $this->successRespone("updated", $respData);
    }

    /**
     * 清空資料. 有需要臨時開啟就好
     *
     * 工作坊和簽到簽出資料也會刪除 kv 上的資料，刪除後會需要一段時間
     * @api
     * @path /api/deleData/

     * @return string 返回 Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function deleData()
    {
        $this->denyProduction();
        ignore_user_abort(true);
        set_time_limit(0);

        $this->checkToken();
        $respData = [];

        $workshop = intval($this->request->getVar('workshop')) ?? 0;
        $respData['delete']['workshop'] = [];
        if (!empty($workshop)) {
            $workshopModel = Factories::models('WorkshopModel', ['getShared' => true]);
            $workshopModel->truncate();

            $cloudflareApi = new CloudflareApi();
            $syncResult = $cloudflareApi->deleteData('workshop');

            $respData['delete']['workshop'] = $syncResult;

            $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'api');
        }

        $signLog = intval($this->request->getVar('signlog')) ?? 0;
        $respData['delete']['signlog'] = [];
        if (!empty($signLog)) {
            $signLogModel = Factories::models('SignLogModel', ['getShared' => true]);
            $signLogModel->truncate();

            // 注意這邊可能會造成 cancel
            $cloudflareApi = new CloudflareApi();
            $syncResult = $cloudflareApi->deleteData('signlog');

            $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'api');
            $respData['delete']['signlog'] = $syncResult;
        }

        $member = intval($this->request->getVar('member')) ?? 0;
        $respData['delete']['member'] = 0;
        if (!empty($member)) {
            $memberModel = Factories::models('MemberModel', ['getShared' => true]);
            $memberModel->truncate();

            $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'api');
            $respData['delete']['member'] = 1;
        }

        return $this->successRespone("updated", $respData);
    }

    /**
     * 創建正式學員帳號.
     *
     * 從 WRITEPATH . 'uploads/members.json' 匯入資料庫，
     * @api
     * @path /api/prepareProductionMembers/

     * @return string 返回 Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function prepareProductionMembers()
    {
        $this->denyProduction();
        ignore_user_abort(true);
        set_time_limit(0);

        $this->checkToken();

        $memberModel = Factories::models('MemberModel', ['getShared' => true]);
        $memberModel->truncate();
        $respData = [];

        $file = WRITEPATH . 'uploads/members.json';
        $content = jsonDecode(file_get_contents($file));

        foreach ($content as $count => $row) {
            /*
            {
            "member_num": 101103,
            "member_name": "陳律齊",
            "member_area": "第一分營區",
            "scout_name": "",
            "scout_num": "新北市",
            "member_phone": 965599810,
            "member_contact_name": "陳鴻杰",
            "member_contact_phone": 921550733
            }
             */
            $data = [];
            foreach ($row as $key => $value) {

                $vaule = trim($value);
                $vaule = str_replace(PHP_EOL, '', $vaule);

                $data[$key] = $vaule;
            }

            $is_success = $memberModel->insert($data);

            if ($is_success) {
                $respData['successed'][] = $data;
            } else {
                $respData['failed'][] = $data;
            }
        }

        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'api');

        return $this->successRespone("updated", $respData);
    }

    /**
     * 創建正式工作坊帳號.
     *
     * 從 WRITEPATH . 'uploads/workshops.json' 匯入資料庫，並匯出工作坊帳號至 kv
     * @api
     * @path /api/prepareProductionWorkshops/

     * @return string 返回 Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function prepareProductionWorkshops()
    {
        $this->denyProduction();
        ignore_user_abort(true);
        set_time_limit(0);

        $this->checkToken();

        $workshopModel = Factories::models('WorkshopModel', ['getShared' => true]);
        $workshopModel->truncate();

        $respData = [];
        $workshopUsernames = [];

        $respData['local'] = [];
        $sessionsStr = implode(',', AdminLib::getAllowSessions());

        $file = WRITEPATH . 'uploads/workshops.json';
        $content = jsonDecode(file_get_contents($file));
        // var_dump(file_get_contents($file));die();
        foreach ($content as $count => $row) {
            /*
            array(21) { [0]=> string(17) "01四海文化村" [1]=> string(6) "100000" [2]=> string(6) "933366" [3]=> string(15) "測試工作坊" [4]=> string(6) "071101" [5]=> string(6) "071102" [6]=> string(6) "071103" [7]=> string(6) "071104" [8]=> string(6) "071201" [9]=> string(6) "071202" [10]=> string(6) "071203" [11]=> string(6) "071204" [12]=> string(6) "071401" [13]=> string(6) "071402" [14]=> string(6) "071403" [15]=> string(6) "071404" [16]=> string(6) "071501" [17]=> string(6) "071502" [18]=> string(6) "071503" [19]=> string(6) "071504" }
             */
            $name = $row[3];
            $password = md5($row[2]);
            $workshopArea = $row[0];
            $sessions = array_slice($row, 4);
            $sessionsStr = AdminLib::initWorkshopSessionsByArray($sessions);
            $data = [
                "workshop_name" => $name, "workshop_username" => $row[1], "workshop_password" => $password, "workshop_sessions" => $sessionsStr, "workshop_area" => $workshopArea,
            ];
            $is_success = $workshopModel->insert($data);
            $respData['local'][] = $data;
            if ($is_success) {
                $workshopUsernames[] = $data['workshop_username'];
            }
        }

        $respData['result'] = [];
        foreach ($workshopUsernames as $workshopUsername) {

            $row = $this->getWorkshopObject($workshopModel, $workshopUsername);

            // 同步資料
            $syncResult = $this->syncWorkshopRemote($row);
            $res = jsonDecode($syncResult);
            $respData['result'][] = $res['result']['checkSuccess'];

            $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'api');
        }

        return $this->successRespone("updated", $respData);
    }

// --------  開發期間使用的 API END ------------

}
