<?php
// 後台：https://ftp.h5-x.com/wendy/topgolf/dist/#/team_verification
namespace App\Controllers;

use App\Controllers\ProjectController;
use App\Libraries\Admin\AdminLib;
use App\Libraries\Admin\CloudflareApi;
use App\Libraries\PageLib;
use App\Libraries\Project\CsvLib;
use CodeIgniter\Config\Factories;

/**
 * 後台系統設置頁面、全域  使用 API.
 * 與後台溝通的 API ，該類別須登入以後才能使用
 * @package Admin
 */
class Admin extends ProjectController
{

// --- public function ---
    /**
     * Initializer
     * @internal
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    /**
     * 匯入資料
     *
     * 匯入資料更新檔案
     * @api
     * @path /admin/import/
     * @uses file file 匯入 csv 檔案
     * @uses string type 需要處理的資料標記
     * @uses int|boolean isDel 是否需要刪除資料，是為 1 不是為 0
     * @return string 返回Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    private function import(string $target)
    {
        // $isDel = empty($this->request->getVar('isDel') ?? 0) ? false : true;
        if ("workshops" == $target) {
            $overwrite = true;
        }
        $maxSize = '10240';
        $validateFile = $this->validate([
            'file' => "uploaded[file]|max_size[file,{$maxSize}]|ext_in[file,csv],",
        ]);

        if ($validateFile) {
            $fileObj = $this->request->getFile('file'); // getFileMultiple

            if (!$fileObj->isValid()) {
                $errorCode = __FUNCTION__ . " " . $target . "001";
                $errorMessage = "上傳檔案錯誤：" . $fileObj->getErrorString() . '(' . $fileObj->getError() . ')';
                $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');
                return $this->failResponse('invalid_data', $errorCode, $errorMessage);
            }

            // 移動檔案保存
            // $fileObj->move(WRITEPATH . 'uploads');
            $model = null;
            if ("workshops" == $target) {
                $model = Factories::models('WorkshopModel', ['getShared' => true]);
                // 清空資料表
                $model->truncate();
            } else if ("records" == $target) {

                $model = Factories::models('SignLogModel', ['getShared' => true]);
            } else {
                $errorCode = __FUNCTION__ . " " . $target . " 001.1";
                $errorMessage = "系統錯誤，不支援的參數類型。";
                $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');

                return $this->failResponse('invalid_data', $errorCode, $errorMessage);
            }
            $file = fopen($fileObj->getTempName(), "r");
            $lineNum = 0;
            $successCount = 0;

            while ($data = fgetcsv($file)) {
                if (0 == $lineNum) {
                    $lineNum++;
                    continue;
                }
                try {
                    $successCount += $model->workshopReadCsv($data);
                } catch (\Exception $e) {
                    $errorCode = __FUNCTION__ . " " . $target . " 002";
                    $errorMessage = "檔案內容錯誤：" . $e->getMessage();
                    $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');

                    return $this->failResponse('invalid_data', $errorCode, $errorMessage);
                }
                $lineNum++;
            }
            fclose($file);

            $resp = ['lineNum' => $lineNum, 'successCount' => $successCount];
            return $this->successRespone("updated", $resp);
        }

        $errorCode = __FUNCTION__ . " " . $target . " 003";
        $errorMessage = "請確認檔案大小、類型（必須 .csv 檔、{$$maxSize}）";
        $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');

        return $this->failResponse('invalid_data', $errorCode, $errorMessage);
    }

    /**
     * 工作坊操作.
     *
     * 各種關於工作坊的操作，包含列表、添加、修改三大類型
     * @api
     * @path /admin/workshops/
     * @uses int page 頁數，最小從 1 開始。
     * @uses int per 單頁一次顯示數量
     * @uses string action 操作 enum: list edit add download 各種
     * @uses string ...
     * @response array result 資料
     * @response string result.message 錯誤訊息
     * @response int total 總數量
     * @response int count 單次顯示數量
     * @response int start 單次顯示 index
     * @return string 返回Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function workshops()
    {
        $this->checkPermission("operator");
        $action = $this->request->getVar('action');

        $workshopModel = Factories::models('WorkshopModel', ['getShared' => true]);

        if ("uploadData" == $action) {
            return $this->import("workshops");
        } else if ("list" == $action || "download" == $action || "downloadDemo" == $action) {

            // 如果要給前端支援的話需要修改這裡
            $page = [
                'page' => intval($this->request->getVar('page') ?? "1"),
                'per' => intval($this->request->getVar('per') ?? "0"),
            ];

            list($start, $page, $count) = PageLib::getPageArgs($page['page'], $page['per']);

            $result = [];
            $conditions = [];

            if ("downloadDemo" == $action) {
                $allowAessions = AdminLib::getAllowSessions();
                $total = 1;
                $result = [];
                // 設定一組範例
                $result[] = [
                    'workshop_name' => 'XX工作坊',
                    'workshop_username' => '100001',
                    'workshop_area' => 'XX活動中心',
                    'workshop_password' => '100001',
                    'workshop_sessions' => implode(',', $allowAessions),
                ];
            } else {

                $workshopSessions = $this->request->getVar('workshopSessions') ?? "";
                $workshopSessions = AdminLib::initWorkshopSessions($workshopSessions);
                $workshopName = $this->request->getVar('workshopName') ?? "";
                $workshopUsername = $this->request->getVar('workshopUsername') ?? "";
                $workshopArea = $this->request->getVar('workshopArea') ?? "";

                $order = $this->request->getVar('order') ?? "id";
                $allowedOrderFields = ["id", "workshop_username", "workshop_name", "workshop_area"];
                if (!in_array($order, $allowedOrderFields)) {
                    $order = "id";
                }
                $sort = ($this->request->getVar('sort') == "desc") ? "DESC" : "ASC";

                $arguments = ['orderBy' => "{$order} {$sort}", 'select' => 'id,workshop_name,workshop_username,workshop_area, workshop_sessions'];
                if ($count > 0) {
                    $arguments['limit'] = $count;
                    $arguments['offset'] = $start;
                }

                list($result, $total) = AdminLib::listWorkshops($conditions, $arguments, $workshopSessions, $workshopName, $workshopUsername, $workshopArea);
            }

            if ("download" == $action || "downloadDemo" == $action) {
                // 如果是下載的話
                $allowSessions = AdminLib::getAllowSessions();
                if ("downloadDemo" == $action) {
                    $header = AdminLib::getWorkshopCsvHeaderForImport($allowSessions);
                } else {
                    $header = AdminLib::getWorkshopCsvHeader($allowSessions);
                }

                if ("download" == $action) {
                    // 添加一個 ID 的元素塞在頭
                    array_splice($header, 0, 0, ["ID"]);
                }
                $resData = [];
                // UGLY 需要簡化複雜度
                foreach ($result as $row) {
                    $sessions = explode(',', $row['workshop_sessions']);
                    unset($row['workshop_sessions']);
                    foreach ($allowSessions as $allowSession) {
                        $row['allow' . $allowSession] = in_array($allowSession, $sessions) ? "1" : "0";
                    }
                    $resData[] = $row;
                }
                $fileName = ("download" == $action) ? __FUNCTION__ : "Expample";
                $url = $this->exportData(__FUNCTION__, $header, $resData);
                $isSuccess = 1;
                $data = [
                    'isSuccess' => !empty($isSuccess),
                    'url' => $url,
                ];

                $this->logSuccess("執行成功", __FUNCTION__ . "002", (string) $this->request->getUri(), 'admin');
                return $this->successRespone("updated", $data);
            }

            $data = [
                'result' => $result,
            ];
            PageLib::addFieldofList($data, $total, $count, $start);
            $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
            return $this->successRespone("updated", $data);
        } else if ("edit" == $action || "add" == $action) {

            $data = [];
            if ("edit" == $action) {
                $id = intval($this->request->getVar('id') ?? 0);
                if (empty($id)) {
                    $errorCode = __FUNCTION__ . "001";
                    $errorMessage = "ID 欄位錯誤";
                    $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');

                    return $this->failResponse('invalid_data', $errorCode, $errorMessage);
                }
                $data['id'] = $id;
            }

            // $workshopSessions = $this->request->getVar('workshopSessions') ?? "";
            // $workshopSessions = AdminLib::initWorkshopSessions($workshopSessions);
            // $workshopName = $this->request->getVar('workshopName') ?? "";
            $workshopUsername = $this->request->getVar('workshopUsername') ?? "";
            if ("add" == $action) {
                // 檢查工作坊編號是否重複
                $conditions['workshop_username'] = $workshopUsername;
                $arguments = ['limit' => 1, 'offset' => 0, 'orderBy' => 'id DESC', 'select' => 'id'];
                $workshopModel->preWhere($conditions);
                $workshopModel->preArg($arguments);
                $result = $workshopModel->getArray();

                if (isset($result) && !empty($result)) {
                    $errorCode = __FUNCTION__ . "001";
                    $errorMessage = "ID 欄位錯誤";
                    $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');

                    return $this->failResponse('invalid_data', $errorCode, $errorMessage);
                }
            }

            // 添加 or 修改資料，這裡採規則運作
            $json = $this->request->getJSON(true);
            $allowedFields = $workshopModel->getAllowedFields();

            // 預處理欄位
            foreach ($allowedFields as $field) {
                if ("workshop_password" == $field) {
                    // UGLY 處理密碼，這邊資料庫的密碼和前端傳的密碼不一致
                    $nField = "password";
                } else {
                    $nField = convertSnakeToLowerCamel($field);
                }

                if (!isset($json[$nField]) || empty(trim($json[$nField]))) {
                    if (!("edit" == $action && "password" == $nField)) {
                        // 只有 password 允許為空
                        $errorCode = __FUNCTION__ . "002";
                        $errorMessage = "({$field})欄位錯誤，資料不得為空";

                        $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');

                        return $this->failResponse('invalid_data', $errorCode, $errorMessage);
                    }
                }
                // 填充欄位
                $data[$field] = $json[$nField];

                if ("workshop_password" == $field) {

                    if (!empty($data[$field]) && $data[$field] != '') {
                        //密碼透過 md5 編碼，提供給工作坊登入驗證使用
                        //在 cloudfalre 上也是同樣使用 md5 驗證
                        $data[$field] = $workshopModel->initPassword($data[$field]);
                    } else {
                        // 如果為空則不處理密碼欄位
                        unset($data[$field]);
                    }
                } else if ("workshop_sessions" == $field) {
                    // 整理所有允許的場次並且排序
                    $data[$field] = $workshopModel->initWorkSessionss($data[$field]);
                }
            }

            $isSuccess = $workshopModel->insertOrUpdate($data);

            log_message("debug", "insertOrUpdate() isSuccess: " . $isSuccess);
            $data = [
                'isSuccess' => !empty($isSuccess),
            ];

            // 工作坊需要和 kv 同步
            $data['sync'] = [];
            if ($data['isSuccess']) {

                // 重新 select 工作坊預處理同步
                $conditions['workshop_username'] = $workshopUsername;

                $arguments = ['limit' => 1, 'offset' => 0, 'orderBy' => 'id DESC', 'select' => '*'];
                $workshopModel->preWhere($conditions);
                $workshopModel->preArg($arguments);
                $row = $workshopModel->getObject(true);

                $cloudflareApi = new CloudflareApi();
                // 同步 kv 資料
                $data['sync'] = $cloudflareApi->registerWorkShop($row->workshop_username, $row->workshop_password, $row->workshop_name, $row->workshop_sessions);

            }
            $this->logSuccess("執行成功", __FUNCTION__ . "003", (string) $this->request->getUri(), 'admin');
            return $this->successRespone("updated", $data);
        }
    }

    /**
     * 學員操作.
     *
     * 各種關於學員的操作，包含列表、添加、修改三大類型
     * @api
     * @path /admin/members/
     * @uses int page 頁數，最小從 1 開始。
     * @uses int per 單頁一次顯示數量
     * @uses string action 操作 enum: list edit add download 各種
     * @uses string ...
     * @response array result 資料
     * @response string result.message 錯誤訊息
     * @response int total 總數量
     * @response int count 單次顯示數量
     * @response int start 單次顯示 index
     * @return string 返回Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function members()
    {
        $this->checkPermission("operator");
        $action = $this->request->getVar('action');

        $memberModel = Factories::models('MemberModel', ['getShared' => true]);
        $mTable = $memberModel->getTable();

        if ("list" == $action || "download" == $action) {
            $signLogModel = Factories::models('SignLogModel', ['getShared' => true]);
            $workshopModel = Factories::models('WorkshopModel', ['getShared' => true]);

            // 如果要給前端支援的話需要修改這裡
            $page = [
                'page' => intval($this->request->getVar('page') ?? "1"),
                'per' => intval($this->request->getVar('per') ?? "10000"),
            ];

            list($start, $page, $count) = PageLib::getPageArgs($page['page'], $page['per']);

            $result = [];
            $conditions = [];

            $workshopUsername = $this->request->getVar('workshopUsername') ?? "";

            // UGLY 這裡的條件都是一樣的，一起處理。 未來如果有要弄要額外拆開
            $cond = [];
            $cond['member_area'] = $this->request->getVar('memberArea') ?? "";
            $cond['scout_name'] = $this->request->getVar('scoutName') ?? "";
            $cond['scout_num'] = $this->request->getVar('scoutNum') ?? "";
            $cond['member_num'] = $this->request->getVar('memberNum') ?? "";
            $cond['member_name'] = $this->request->getVar('memberName') ?? "";

            foreach ($cond as $key => $value) {
                if (!empty($value)) {
                    $conditions["{$mTable}.{$key} LIKE"] = '%' . $value . '%';
                }
            }

            $sessionCountStr = $this->request->getVar('sessionCount') ?? "";
            $sessionCount = $sessionCountStr === "" ? "-1" : intval($sessionCountStr);

            $isAbove = $this->request->getVar('isAbove') == true ?? false;
            $onlyNonJoin = $this->request->getVar('onlyNonJoin') == true ?? false;

            $order = $this->request->getVar('order') ?? "id";
            $allowedOrderFields = ["id", "member_num", "member_area"];
            if (!in_array($order, $allowedOrderFields)) {
                $order = "id";
            }
            $sort = ($this->request->getVar('sort') == "desc") ? "DESC" : "ASC";

            $arguments = ['limit' => $count, 'offset' => $start, 'orderBy' => "{$mTable}.{$order} {$sort}", 'select' => "{$mTable}.id,{$mTable}.member_num,{$mTable}.member_name,{$mTable}.member_area,{$mTable}.scout_name,{$mTable}.scout_num,{$mTable}.member_phone,{$mTable}.member_contact_name,{$mTable}.member_contact_phone"];

            // 次數的部分用其他方式 另外計算
            if (!$onlyNonJoin && ($sessionCount >= 0 || !empty($workshopUsername))) {
                list($result, $total) = AdminLib::listMemberByCount($conditions, $arguments, $sessionCount, $workshopUsername, $isAbove);
            } else {
                list($result, $total) = AdminLib::listMemberByNonCount($conditions, $arguments, $onlyNonJoin);
            }

            if ("download" == $action) {
                // 如果是下載的話

                $url = $this->exportData(__FUNCTION__, ["ID", "學員編號", "學員名稱", "分營區", "團名", "團次", "聯絡方式", "緊急聯絡人", "緊急聯絡電話"], $result);
                $isSuccess = 1;
                $data = [
                    'isSuccess' => !empty($isSuccess),
                    'url' => $url,
                ];

                $this->logSuccess("執行成功", __FUNCTION__ . "002", (string) $this->request->getUri(), 'admin');
                return $this->successRespone("updated", $data);
            }

            $data = [
                'result' => $result,
            ];
            PageLib::addFieldofList($data, $total, $count, $start);
            $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
            return $this->successRespone("updated", $data);
        } else if ("edit" == $action || "add" == $action) {

            $data = [];
            if ("edit" == $action) {
                $id = intval($this->request->getVar('id') ?? 0);
                if (empty($id)) {
                    $errorCode = __FUNCTION__ . "001";
                    $errorMessage = "ID 欄位錯誤";
                    $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');

                    return $this->failResponse('invalid_data', $errorCode, $errorMessage);
                }
                $data['id'] = $id;
            }

            // 編輯資料
            $json = $this->request->getJSON(true);
            $allowedFields = $memberModel->getAllowedFields();
            foreach ($allowedFields as $field) {
                $nField = convertSnakeToLowerCamel($field);
                if (!isset($json[$nField]) || empty(trim($json[$nField]))) {
                    $errorCode = __FUNCTION__ . "002";
                    $errorMessage = "({$field})欄位錯誤，資料不得為空";
                    $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');

                    return $this->failResponse('invalid_data', $errorCode, $errorMessage);
                }
                $data[$field] = $json[$nField];
            }

            $isSuccess = $memberModel->insertOrUpdate($data);

            $data = [
                'isSuccess' => !empty($isSuccess),
            ];

            $data['sync'] = [];

            $this->logSuccess("執行成功", __FUNCTION__ . "003", (string) $this->request->getUri(), 'admin');
            return $this->successRespone("updated", $data);
        }
    }

    /**
     * 取得儀表版資料.
     *
     * 取得儀表版資料
     * @api
     * @path /admin/getStatus/
     * @response array result 資料
     * @response string result.message 錯誤訊息
     * @response array data 資料內容
     * @return string 返回Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function getStatus()
    {
        $this->checkPermission("operator");
        $cacheName = 'status';

        if (!$data = cache($cacheName)) {
            $data = AdminLib::getStatusData();
            // Save into the cache for 1 minutes
            cache()->save($cacheName, $data, 60);
        }
        return $this->successRespone("updated", $data);
    }

    /**
     * 簽到簽出記錄操作.
     *
     * 各種關於簽到簽出記錄的操作，包含列表、添加、修改三大類型
     * @api
     * @path /admin/records/
     * @uses int page 頁數，最小從 1 開始。
     * @uses int per 單頁一次顯示數量
     * @uses string action 操作 enum: list edit add download 各種
     * @uses string ...
     * @response array result 資料
     * @response string result.message 錯誤訊息
     * @response int total 總數量
     * @response int count 單次顯示數量
     * @response int start 單次顯示 index
     * @return string 返回Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function records()
    {
        $this->checkPermission("operator");
        $action = $this->request->getVar('action');
        $signLogModel = Factories::models('SignLogModel', ['getShared' => true]);
        $workshopModel = Factories::models('WorkshopModel', ['getShared' => true]);
        $memberModel = Factories::models('MemberModel', ['getShared' => true]);
        if ("uploadData" == $action) {
            return $this->import("records");
        } else if ("downloadDemo" == $action) {

            // 設定一組範例
            $result[] = [
                'workshop_username' => '100001',
                'member_num' => '100001',
                'workshop_session' => '071101',

                'sign_in' => date('Y-m-d H:i:s'),
                'sign_out' => date('Y-m-d H:i:s'),
            ];

            $url = $this->exportData(__FUNCTION__, ["工作坊編號", "學員編號", "場次", "簽到時間", "簽出時間"], $result);
            $isSuccess = 1;
            $data = [
                'isSuccess' => !empty($isSuccess),
                'url' => $url,
            ];

            $this->logSuccess("執行成功", __FUNCTION__ . "000", (string) $this->request->getUri(), 'admin');
            return $this->successRespone("updated", $data);
        } else if ("sync" == $action) {

            $workshopUsername = $this->request->getVar('workshop');
            $session = $this->request->getVar('session') ?? "";
            return $this->_getSignData($workshopUsername, $session, __FUNCTION__);

        } else if ("list" == $action || "download" == $action) {

            $mTable = $memberModel->getTable();
            $wTable = $workshopModel->getTable();
            $sTable = $signLogModel->getTable();

            // 如果要給前端支援的話需要修改這裡
            $page = [
                'page' => intval($this->request->getVar('page') ?? "1"),
                'per' => intval($this->request->getVar('per') ?? "100000000"),
            ];

            list($start, $page, $count) = PageLib::getPageArgs($page['page'], $page['per']);

            $result = [];
            $conditions = [];
            //{"workshopSession":"","workshopUsername":"","memberNum":"","memberName":"","memberContactName":"","memberArea":"","scoutName":"","scoutNum":"","startTime":"2024-03-31T00:00:00","endTime":"2024-03-31T23:59:59","isSignIn":true,"isSignOut":true,"action":"list"}

            $cond = [];
            $cond['workshop_username'] = $this->request->getVar('workshopUsername') ?? "";

            $cond['workshop_session'] = $this->request->getVar('workshopSession') ?? "";
            foreach ($cond as $key => $value) {
                if (!empty($value)) {
                    $conditions["{$sTable}.{$key}"] = $value;
                }
            }

            $workshopName = $this->request->getVar('workshopName') ?? "";
            if (!empty($workshopName)) {
                $conditions["{$wTable}.workshop_name LIKE"] = "%{$workshopName}%";
            }

            $startTime = $this->request->getVar('startTime') ?? date("Y-m-d 00:00:00");
            $startTime = date("Y-m-d H:i:S", strtotime($startTime));
            $endTime = $this->request->getVar('endTime') ?? date("Y-m-d 23:59:59");
            $endTime = date("Y-m-d H:i:S", strtotime($endTime));
            $conditions["{$sTable}.created_at >="] = $startTime;
            $conditions["{$sTable}.created_at <="] = $endTime;

            $isSignIn = $this->request->getVar('isSignIn') === true ?? false;
            $isSignOut = $this->request->getVar('isSignOut') === true ?? false;
            if ($isSignIn) {
                $conditions["{$sTable}.sign_in <>"] = '0000-00-00 00:00:00';
            }
            if ($isSignOut) {
                $conditions["{$sTable}.sign_out <>"] = '0000-00-00 00:00:00';
            }

            // UGLY 這裡的條件都是一樣的，一起處理。 未來如果有要弄要額外拆開
            $cond = [];
            $cond['member_area'] = $this->request->getVar('memberArea') ?? "";
            $cond['scout_name'] = $this->request->getVar('scoutName') ?? "";
            $cond['member_contact_name'] = $this->request->getVar('memberContactName') ?? "";
            $cond['scout_num'] = $this->request->getVar('scoutNum') ?? "";
            $cond['member_num'] = $this->request->getVar('memberNum') ?? "";
            $cond['member_name'] = $this->request->getVar('memberName') ?? "";

            foreach ($cond as $key => $value) {
                if (!empty($value)) {
                    $conditions["{$mTable}.{$key} LIKE"] = '%' . $value . '%';
                }
            }

            $order = $this->request->getVar('order') ?? "id";
            $allowedOrderFields = ["id", "workshop_username", "workshop_session", "member_num", "sign_in", "sign_out"];
            if (!in_array($order, $allowedOrderFields)) {
                $order = "id";
            }

            $sort = ($this->request->getVar('sort') == "desc") ? "DESC" : "ASC";

            $arguments = ['limit' => $count, 'offset' => $start, 'orderBy' => "{$sTable}.{$order} {$sort}", 'select' => "{$sTable}.id,{$sTable}.workshop_username,{$wTable}.workshop_name,{$sTable}.workshop_session,{$sTable}.member_num,{$mTable}.member_name,{$sTable}.sign_in,{$sTable}.sign_out,{$mTable}.member_phone,{$mTable}.member_contact_name,{$mTable}.member_contact_phone,{$mTable}.member_area,{$mTable}.scout_name,{$mTable}.scout_num"];

            // 次數的部分用其他方式 另外計算
            list($result, $total) = AdminLib::listSingLog($conditions, $arguments);

            if ("download" == $action) {
                // 如果是下載的話

                $url = $this->exportData(__FUNCTION__, ["ID", "工作坊編號", "工作坊名稱", "場次", "學員編號", "學員名稱", "簽到時間", "簽出時間", "聯絡電話", "緊急聯絡人", "緊急聯絡電話", "分營區", "團名", "團次"], $result);
                $isSuccess = 1;
                $data = [
                    'isSuccess' => !empty($isSuccess),
                    'url' => $url,
                ];

                $this->logSuccess("執行成功", __FUNCTION__ . "002", (string) $this->request->getUri(), 'admin');
                return $this->successRespone("updated", $data);
            }

            $data = [
                'result' => $result,
            ];
            PageLib::addFieldofList($data, $total, $count, $start);
            $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
            return $this->successRespone("updated", $data);
        } else if ("edit" == $action || "add" == $action) {

            $data = [];
            if ("edit" == $action) {
                $id = intval($this->request->getVar('id') ?? 0);
                if (empty($id)) {
                    $errorCode = __FUNCTION__ . "001";
                    $errorMessage = 'ID 欄位錯誤';
                    $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');

                    return $this->failResponse('invalid_data', $errorCode, $errorMessage);
                }
                $data['id'] = $id;
            } else {
                $id = intval($this->request->getVar('id') ?? 0);
            }
            // 檢查添加欄位
            $json = $this->request->getJSON(true);
            $allowedFields = $signLogModel->getAllowedFields();
            foreach ($allowedFields as $field) {
                $nField = convertSnakeToLowerCamel($field);
                if (!isset($json[$nField]) || empty(trim($json[$nField]))) {
                    $errorCode = __FUNCTION__ . "002";
                    $errorMessage = "({$field})欄位錯誤，資料不得為空";
                    $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');
                    return $this->failResponse('invalid_data', $errorCode, $errorMessage);
                }
                $data[$field] = $json[$nField];
            }

            $memberNum = $this->request->getVar('memberNum');
            $workshopSession = $this->request->getVar('workshopSession');
            $workshopUsername = $this->request->getVar('workshopUsername');
            // 檢查重複添加

            $conditions = ['workshop_username' => $workshopUsername, 'member_num' => $memberNum, 'workshop_session' => $workshopSession];
            $arguments = ['limit' => 1, 'offset' => 0, 'orderBy' => 'id DESC', 'select' => 'id,workshop_session'];
            $signLogModel->preWhere($conditions);
            $signLogModel->preArg($arguments);
            $rowArr = $signLogModel->getArray(true);
            if ("add" == $action) {
                if ((isset($rowArr) && !empty($rowArr))) {
                    $errorCode = __FUNCTION__ . "003";
                    $errorMessage = "無法重複添加記錄，重複的工作坊編號/場次/學員編號";
                    $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');
                    return $this->failResponse('invalid_data', $errorCode, $errorMessage);
                }
            }
            // 檢查 memberNum
            $conditions = ['member_num' => $memberNum];
            $arguments = ['limit' => 1, 'offset' => 0, 'orderBy' => 'id DESC', 'select' => 'id'];
            $memberModel->preWhere($conditions);
            $memberModel->preArg($arguments);
            $rowArr = $memberModel->getArray(true);
            if (!isset($rowArr) || empty($rowArr)) {

                $errorCode = __FUNCTION__ . "004";
                $errorMessage = '學員編號不存在';

                $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');
                return $this->failResponse('invalid_data', $errorCode, $errorMessage);
            }

            // 檢查 workshopUsername workshopSession
            $conditions = ['workshop_username' => $workshopUsername];

            $arguments = ['limit' => 1, 'offset' => 0, 'orderBy' => 'id DESC', 'select' => 'id,workshop_sessions'];
            $workshopModel->preWhere($conditions);
            $workshopModel->preArg($arguments);
            $rowArr = $workshopModel->getArray(true);

            if (!isset($rowArr) || empty($rowArr)) {

                $errorCode = __FUNCTION__ . "005";
                $errorMessage = '工作坊編號不存在';
                $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');
                return $this->failResponse('invalid_data', $errorCode, $errorMessage);
            }

            $workshopSessions = explode(',', $rowArr['workshop_sessions']);
            if (!in_array($workshopSession, $workshopSessions)) {
                $errorCode = __FUNCTION__ . "006";
                $errorMessage = '不存在該場次';
                $this->logfailed($errorMessage, $errorCode, (string) $this->request->getUri(), 'admin');
                return $this->failResponse('invalid_data', $errorCode, $errorMessage);
            } else {
                // 取得最近一筆 session
                // $session = AdminLib::getClosestSession($workshopSessions) : $session;
            }

            $isSuccess = $signLogModel->insertOrUpdate($data);

            $data = [
                'isSuccess' => !empty($isSuccess),
            ];

            $data['sync'] = [];

            $this->logSuccess("執行成功", __FUNCTION__ . "003", (string) $this->request->getUri(), 'admin');
            return $this->successRespone("updated", $data);
        }
    }

    /**
     * 匯出檔案
     * @param  string $action 名稱
     * @param  array  $header 欄位頭（第一列
     * @param  array  $data   資料內容
     * @return string 生成 csv 資料
     */
    private function exportData(string $action, array $header, array $data): string
    {
        //移除半小時前的舊檔案
        $dir = PUBLIC_PATH . 'uploads';
        CsvLib::deleteOldCsvFiles($dir, 30);
        $filename = $action . '_' . date('Y-m-d-His') . '.csv';
        $filePath = $dir . "/{$filename}";
        $url = BASE_URL . "/uploads/{$filename}";
        CsvLib::exportData($filename, $filePath, $header, $data);
        return $url;
    }

    /**
     * 錯誤日誌 列表.
     *
     * 列出錯誤日誌<br>注意：目前使用分頁，如果不用分頁的話，需確認最高數量限制
     * @api
     * @path /admin/logs/
     * @uses int page 頁數，最小從 1 開始。
     * @uses int per 單頁一次顯示數量
     * @response array result 資料列表
     * @response int result.id  ID
     * @response string result.message 錯誤訊息
     * @response int total 總數量
     * @response int count 單次顯示數量
     * @response int start 單次顯示 index
     * @return string 返回Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function logs()
    {
        $this->checkPermission("authorily");
        // 錯誤時間（2022-01-02 11:00:00 ）、錯誤標記（e003）、錯誤 API Path（ http://xxx.xx/admin/startGame ）、分類（Game System 或是 Admin System）、錯誤訊息（遊戲已開始，無法再次開始遊戲）

        $page = [
            'page' => intval($this->request->getVar('page')),
            'per' => intval($this->request->getVar('per')),
        ];

        list($start, $page, $count) = PageLib::getPageArgs($page['page'], $page['per']);

        $time = $this->request->getVar('time') ?? "";

        $logModel = Factories::models('LogModel', ['getShared' => true]);

        $logModel->preArg(['limit' => $count, 'offset' => $start, 'orderBy' => 'id DESC']);

        $conditions = ['category !=' => 'game'];
        if (!empty($time)) {
            $time = date("Y-m-d", strtotime($time));
            $conditions['created_at >= '] = "{$time} 00:00:00";
            $conditions['created_at <= '] = "{$time} 23:59:59";
        }
        $logModel->preWhere($conditions);
        $result = $logModel->getArray();
        $logModel->preWhere($conditions);
        $total = $logModel->getCount();

        $data = [
            'result' => $result,
        ];
        PageLib::addFieldofList($data, $total, $count, $start);
        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
        return $this->successRespone("updated", $data);
    }

    /**
     * 權限管理頁面、修改密碼頁面的 列表.
     *
     * 列出所有管理員
     * @api
     * @path /admin/users/
     * @uses int page 頁數，最小從 1 開始。
     * @uses int per 單頁一次顯示數量
     * @response array result 資料列表
     * @response int result.id 管理員 ID
     * @response string result.name 姓名
     * @response string result.username 帳號
     * @response string result.permissions 權限名稱列表（key 值）用逗號分隔
     * @response string result.password 密碼
     * @response int total 總數量
     * @response int count 單次顯示數量
     * @response int start 單次顯示 index
     * @return string 返回Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function users()
    {
        $this->checkPermission("authorily");
        $data = [
            'page' => intval($this->request->getVar('page')),
            'per' => intval($this->request->getVar('per')),
        ];

        list($start, $page, $count) = PageLib::getPageArgs($data['page'], $data['per']);

        $userModel = Factories::models('UserModel', ['getShared' => true]);
        // $userModel->preWhere(['id !=' => '1']);
        $userModel->preArg(['limit' => $count, 'offset' => $start, 'orderBy' => 'id DESC', 'select' => $userModel->getUserAllSelect()]);

        $result = $userModel->getArray();

        $total = $userModel->getCount();

        $data = [
            'result' => $result,
        ];
        PageLib::addFieldofList($data, $total, $count, $start);

        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
        $respMessage = "如果 admin 表示為超級管理員";
        return $this->successRespone("updated", $data, $respMessage);
    }

    /**
     * 權限管理頁面、修改密碼頁面的 列表.
     *
     * 列出所有管理員。調用此搜尋將會不分頁全部列出。故至少需要有一個欄位有值。
     * @api
     * @path /admin/searchUsers/
     * @uses string permission 選擇一個權限名稱（key 值）
     * @uses string number 電話
     * @response array result 資料列表
     * @response int result.id 管理員 ID
     * @response string result.name 姓名
     * @response string result.username 帳號
     * @response array|string result.permissions 權限名稱列表（key 值）
     * @response string result.password 密碼
     * @response int total 總數量
     * @response int count 單次顯示數量
     * @response int start 單次顯示 index
     * @return string 返回Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function searchUsers()
    {
        $this->checkPermission("authorily");

        $data = [
            'permissions' => $this->request->getVar('permission'),
            'username' => $this->request->getVar('username'),
        ];

        $userModel = Factories::models('UserModel', ['getShared' => true]);

        $conditions = [];

        $permissions = $this->request->getVar('permissions') ?? [];

        if (!empty($permissions)) {
            $userModel->setConditions($conditions, $data, 'permissions', true, false);
        }

        $userModel->setConditions($conditions, $data, 'username', true, false);

        if (empty($conditions) && empty($permissions)) {
            $this->logfailed("欄位至少要一個有值", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');

            return $this->failResponse('invalid_data', __FUNCTION__ . '001', '至少一個要有值');
        }

        $userModel->preWhere($conditions);

        if (!empty($permissions)) {
            $extraConditions = [];
            foreach ($permissions as $_permission) {
                $extraConditions[] = "permissions LIKE '%$_permission%'";
            }

            $extraCondition = implode(" OR ", $extraConditions);

            $userModel->preWhereRow("($extraCondition)");
        }

        $userModel->preArg(['orderBy' => 'id DESC', 'select' => $userModel->getUserAllSelect()]);

        $result = $userModel->getArray();

        $total = $count = count($result);

        $data = [
            'result' => $result,
        ];
        PageLib::addFieldofList($data, $total, $count, 0);
        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
        $respMessage = "如果 permissions 是 admin 表示為超級管理員";
        return $this->successRespone("updated", $data, $respMessage);
    }

    /**
     * 編輯/添加用戶.
     *
     * 編輯/添加用戶資料 可變更用戶 名稱、電話、權限、密碼
     * @api
     * @path /admin/editUser/
     * @uses int id 用戶 ID 如果為 0 則添加
     * @uses string name 用戶名稱
     * @uses string username 用戶帳號
     * @uses array  permissions 用戶權限，需要是一個 josn-array
     * @uses string  password 用戶密碼
     * @response bool isSuccess 是否變更成功
     * @return string 返回 Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function editUser()
    {
        $this->checkPermission("authorily");

        $data = [
            'id' => $this->request->getVar('id'),
            'name' => $this->request->getVar('name') ?? "",
            'username' => trim($this->request->getVar('username') ?? ""),
            'permissions' => $this->request->getVar('permissions') ?? "",
            'password' => $this->request->getVar('password') ?? "",
        ];

        $data['permissions'] = trim($data['permissions']);
        $data['permissions'] = trim($data['permissions'], ',');
        if (isset($data['permissions']['admin'])) {
            unset($data['permissions']['admin']);
        }

        if (empty($data['id'])) {
            unset($data['id']);
        }

        $userModel = Factories::models('UserModel', ['getShared' => true]);

        if (!isset($data['id']) && (empty($data['username']) || empty($data['name']) || empty($data['permissions']) || empty($data['password']))) {
            // 新增且沒有 username
            $this->logfailed("新增用戶欄位有缺", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
            return $this->failResponse('invalid_data', __FUNCTION__ . '001', '新增用戶欄位有缺');
        } else if (!isset($data['id'])) {
            // 新增
            // 判斷是否 username 重復

            $conditions = ['username' => $data['username']];

            $userModel->preWhere($conditions);
            $userModel->preArg(['orderBy' => 'id DESC', 'select' => 'id']);

            $row = $userModel->getArray(true);
            if (!empty($row) && !isset($data['id'])) {
                $this->logfailed("用戶 username 重復", __FUNCTION__ . "002", (string) $this->request->getUri(), 'admin');
                return $this->failResponse('invalid_data', __FUNCTION__ . '002', '用戶 username 重復');
            } else {
                $data['user_slug'] = md5($data['username']);
            }
        } else if (!empty($data['username'])) {
            // 更新 如果有要更新 username user_slug 一並更新
            $data['user_slug'] = md5($data['username']);
        }

        $data = array_filter($data);

        $isSuccess = $userModel->insertOrUpdate($data);

        if (isset($data['id'])) {
            $userModel->preWhere(['id' => $data['id']]);
        } else {
            $userModel->preWhere(['user_slug' => $data['user_slug']]);
        }
        $userModel->preArg(['select' => $userModel->getUserAllSelect()]);

        $row = $userModel->getArray(true);

        $data = [
            'isSuccess' => !empty($isSuccess),
            'userData' => $row,
        ];

        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
        return $this->successRespone("updated", $data);
    }

    /**
     * 確認權限
     * @internal
     */
    private function checkPermission($permissionKey, $doReturn = false)
    {
        if (!isset($GLOBALS['currentUserData']) || empty($GLOBALS['currentUserData'])) {
            // 這個必為有值
            $user = $this->getCurrentUserData();
        } else {
            $user = $GLOBALS['currentUserData'];
        }

        $hasPermission = AdminLib::hasPermission($permissionKey, $user['permissions']);
        $isSuperUser = AdminLib::hasPermission("admin", $user['permissions']);
        $hasPermission = $isSuperUser ? true : $hasPermission;
        if ($doReturn) {
            return [
                'isSuperUser' => $isSuperUser,
                'hasPermission' => $hasPermission,
                'needPermission' => $permissionKey,
            ];
        }

        if (!$hasPermission) {
            $this->logfailed("你需要權限{$permissionKey} 這個用戶沒有權限", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
            return $this->failResponse('forbidden', __FUNCTION__ . '001', '你沒有權限');
        }
    }

    /**
     * 確認後台用戶是否有該指定權限
     * @api
     * @path /admin/hasPermission/
     * @uses string permissionKey AdminLib 權限列表中的參數
     * @response bool isSuperUser 則表示是否為超級用戶，如果是超級用戶則 ‵hasPermission‵ 必為 true
     * @response bool hasPermission true 表示有這權限，沒有則為 false
     * @return string 返回Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function hasPermission()
    {

        $permissionKey = $this->request->getVar('permission');

        $data = $this->checkPermission($permissionKey, true);

        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
        return $this->successRespone("updated", $data);
    }

    /**
     * 取得所有權限列表
     * @api
     * @path /admin/getAllPermissions/
     * @return string Json-string key 為 permission slug, value 為可視中文
     */
    public function getAllPermissions()
    {
        $permissions = AdminLib::getAllPermissions();
        $data = [
            'result' => $permissions,
        ];
        $total = count($permissions);
        PageLib::addFieldofList($data, $total, $total, 0);

        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
        return $this->successRespone("updated", $data);
    }

// --------
    /**
     * 取得當前用戶
     * @api
     * @path /admin/getCurrentUser/
     * @return string Json-string 返回用戶資料
     */
    public function getCurrentUser()
    {

        $userModel = Factories::models('UserModel', ['getShared' => true]);
        $arguments = ['select' => $userModel->getPublicUserSelect()];
        $data = $this->getCurrentUserData($arguments);

        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
        return $this->successRespone("created", $data);
    }

    /**
     * 錯誤日誌 列表.
     *
     * 列出錯誤日誌<br>注意：目前使用分頁，如果不用分頁的話，需確認最高數量限制
     * @api
     * @path /admin/alerts/
     * @uses int isReaded 是否讀過
     * @uses int isFixed 是否解決
     * @uses int page 頁數，最小從 1 開始。
     * @uses int per 單頁一次顯示數量
     * @response array result 資料列表
     * @response int result.id  ID
     * @response string result.title 標題，預設為"警告"
     * @response string result.type 分類，預設為"warning"
     * @response string result.content 內容
     * @response string result.isReaded 是否讀過
     * @response string result.isFixed 是否解決
     * @response string result.created_at 創建時間
     * @response string result.updated_at 更新時間
     * @response int total 總數量
     * @response int count 單次顯示數量
     * @response int start 單次顯示 index
     * @return string 返回Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function alerts()
    {
        $this->checkPermission("operator");

        $page = [
            'page' => intval($this->request->getVar('page')),
            'per' => intval($this->request->getVar('per')),
        ];

        $data = [
            'isReaded' => intval($this->request->getVar('isReaded')) ?? '-1',
            'isFixed' => intval($this->request->getVar('isFixed')) ?? '-1',
        ];

        if ('-1' == $data['isReaded']) {
            unset($data['isReaded']);
        }
        if ('-1' == $data['isFixed']) {
            unset($data['isFixed']);
        }

        if (empty($data)) {
            $data['id >'] = '0';
        }

        list($start, $page, $count) = PageLib::getPageArgs($page['page'], $page['per']);

        $alertsModel = Factories::models('AlertsModel', ['getShared' => true]);

        $alertsModel->preWhere($data);

        $alertsModel->preArg(['limit' => $count, 'offset' => $start, 'orderBy' => 'id DESC']);

        $result = $alertsModel->getArray();

        $alertsModel->preWhere($data);
        $total = $alertsModel->getCount();

        $data = [
            'result' => $result,
        ];
        PageLib::addFieldofList($data, $total, $count, $start);
        $this->logSuccess("執行成功", __FUNCTION__ . "001", (string) $this->request->getUri(), 'admin');
        return $this->successRespone("updated", $data);
    }

    /**
     * index
     * @internal
     */
    public function index()
    {
        return $this->failForbidden();
    }
}
