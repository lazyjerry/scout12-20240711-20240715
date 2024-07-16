<?php

namespace App\Libraries\Admin;

use CodeIgniter\Config\Factories;

class AdminLib
{
    // 場次對應時間
    public static $mapOfSessions = [
        '01' => ['08:00', '10:00'], // 0830 - 1000
        '02' => ['10:00', '11:30'],
        '03' => ['13:46', '15:30'], // 1400 - 1530
        '04' => ['15:30', '18:00'], // 1530 - 1700
    ];

    // 所有允許的場次
    public static $allowSessions = ["071101", "071102", "071103", "071104", "071201", "071202", "071203", "071204", "071401", "071402", "071403", "071404", "071501", "071502", "071503", "071504"];

// --- 權限部分使用 START ---

    // 用戶的權限列表，無介面可調整，寫在這裡處理
    public static $permissions = [
        'authorily' => '權限管理',
        'operator' => '操作後台',
    ];

    /**
     * 取得所有權限
     * @return array 權限s
     */
    public static function getAllPermissions()
    {
        return self::$permissions;
    }

    /**
     * 驗證是否有權限
     * @param  string $needPermission 需要的權限
     * @param  array $permissions 用戶當前的權限
     * @return bool
     */
    public static function hasPermission(string $needPermission, string | array $permissions): bool
    {
        if (is_string($permissions)) {
            $permissions = explode(',', $permissions);
        }

        // EDIT  admin 拿掉，後台只能有一個超級管理員
        if (!isset(self::$permissions[$needPermission]) && "admin" != $needPermission) {
            return false;
        }

        return in_array($needPermission, $permissions);
    }

// --- 權限部分使用 END ---
//
    public static function getAllowSessions(): array
    {
        return self::$allowSessions;
    }

    public static function isAllowSession(string $session)
    {

        return in_array($session, self::$allowSessions);
    }

    public static function getMapOfSessions(): array
    {
        return self::$mapOfSessions;
    }

    // 從資料庫取出場次的 array
    private static function getWorkshopSessionsArray(string &$workshopSessions): array
    {
        $workshopSessions = str_replace(" ", "", $workshopSessions);
        return explode(",", $workshopSessions);
    }

    // 取得最近時間的場次
    public static function getTheClosestSession(array &$workshopSessions, array &$mapOfSessions, $time): string
    {
        $nowDay = date("Y-m-d ", $time);
        $sessionDate = date("md", $time); // 取得 session 前綴
        $result = "";
        // UGLY 這裡要確認機制正確，所以先做時間判斷，不做日期判斷
        foreach ($mapOfSessions as $sessionF => $range) {
            $currentSession = $sessionDate . $sessionF;

            if (!in_array($currentSession, $workshopSessions)) {
                // 如果這不是允許的 session 則跳過
                continue;
            }
            $start = strtotime($nowDay . $range[0]);
            $end = strtotime($nowDay . $range[1]);
            // 依照時間順序，抓當前時間正在執行 session
            if ($start <= $time) {
                // 以時間內為主
                $result = $currentSession;

            } else {
                break;
            }
        }

        return $result;
    }

    // 取得最近的一筆 session
    public static function getClosestSession(string &$workshopSessions, int $time = 0): string
    {
        $_arr = self::getWorkshopSessionsArray($workshopSessions);

        $time = empty($time) ? time() : $time;

        return self::getTheClosestSession($_arr, self::$mapOfSessions, $time);
    }

    // 整理所有允許的場次並且排序
    public static function initWorkshopSessions(string &$workshopSessions): string
    {
        $_arr = self::getWorkshopSessionsArray($workshopSessions);
        $arr = [];
        // 透過預先準備好的 array 同時實作過濾與排序
        foreach (self::$allowSessions as $index => $session) {
            $session = trim($session); // 去前後空白
            if (in_array($session, $_arr)) {
                $arr[] = $session;
            }
        }
        $workshopSessions = implode(",", $arr);
        return $workshopSessions;
    }

    // 整理所有允許的場次並且排序
    public static function initWorkshopSessionsByArray(array &$_arr): string
    {
        $arr = [];
        // 透過預先準備好的 array 同時實作過濾與排序
        foreach (self::$allowSessions as $index => $session) {
            $session = trim($session); // 去前後空白
            if (in_array($session, $_arr)) {
                $arr[] = $session;
            }
        }
        $workshopSessions = implode(",", $arr);
        return $workshopSessions;
    }

    // 取得工作坊的匯出 csv 第一行（header)
    public static function getWorkshopCsvHeader(array $allowSessions, string $extraColumn = ''): array
    {
        $header = ["名稱", "編號", "所屬活動中心"];
        if (!empty($extraColumn)) {
            $header[] = $extraColumn;
        }
        foreach ($allowSessions as $allowSession) {
            $header[] = "場次" . $allowSession;
        }
        return $header;
    }

    public static function getWorkshopCsvHeaderForImport(array $allowSessions): array
    {
        $header = ["名稱", "編號", "所屬活動中心", "工作坊登入密碼"];
        foreach ($allowSessions as $allowSession) {
            $header[] = "場次" . $allowSession;
        }
        return $header;
    }

    // 列出工作坊
    public static function listWorkshops(array $conditions, array $arguments, string $workshopSessions, string $workshopName, string $workshopUsername, string $workshopArea)
    {
        $workshopModel = Factories::models('WorkshopModel', ['getShared' => true]);
        if (!empty($workshopSessions)) {
            // 這裡必須已經排序好
            $conditions['workshop_sessions LIKE'] = '%' . $workshopSessions . '%';
            // $conditions["{$keyName} LIKE"] = '%' . $data[$keyName] . '%';
        }
        if (!empty($workshopName)) {
            $conditions['workshop_name LIKE'] = '%' . $workshopName . '%';
        }
        if (!empty($workshopUsername)) {
            $conditions['workshop_username LIKE'] = '%' . $workshopUsername . '%';
        }
        if (!empty($workshopArea)) {
            $conditions['workshop_area LIKE'] = '%' . $workshopArea . '%';
        }

        $workshopModel->preWhere($conditions);
        $workshopModel->preArg($arguments);
        $result = $workshopModel->getArray();
        $workshopModel->preWhere($conditions);
        $total = $workshopModel->getCount();
        return [$result, $total];
    }

    // 列出學員清班 針對有次數的方式計算
    public static function listMemberByCount(array $conditions, array $arguments, int $sessionCount, string $workshopUsername, bool $isAbove)
    {
        $memberModel = Factories::models('MemberModel', ['getShared' => true]);
        $mTable = $memberModel->getTable();
        $signLogModel = Factories::models('SignLogModel', ['getShared' => true]);
        $sTable = $signLogModel->getTable();

        if (!empty($workshopUsername)) {
            // $conditions["{$sTable}.workshop_username LIKE"] = '%' . $workshopUsername . '%';
            $signLogModel->preWhereRow("({$sTable}.workshop_username LIKE '%{$workshopUsername}%')");
        }

        foreach ($conditions as $key => $value) {
            $signLogModel->preWhereRow("{$key} {$value}");
        }

        if ($sessionCount > 0) {
            // $conditions["{$sTable}.sign_in <>"] = '0000-00-00 00:00:00';
            // $conditions["{$sTable}.sign_out <>"] = '0000-00-00 00:00:00';
            $signLogModel->preWhereRow("({$sTable}.sign_in <> '0000-00-00 00:00:00' OR {$sTable}.sign_out <> '0000-00-00 00:00:00')");
        } else if ($sessionCount == 0) {
            $signLogModel->preWhereRow("({$sTable}.sign_in = '0000-00-00 00:00:00' AND {$sTable}.sign_out = '0000-00-00 00:00:00')");
        }

        // 為了計算數量
        $signLogModel->join($mTable, "{$sTable}.member_num={$mTable}.member_num");

        // 如果有數量
        $arguments['groupBy'] = "{$mTable}.id";
        $opt = $isAbove ? ">=" : "=";
        $arguments['having'] = ["count({$sTable}.member_num) {$opt}", $sessionCount];

        // $signLogModel->preWhere($conditions);
        $signLogModel->preArg($arguments);
        $result = $signLogModel->getArray();
        // $lastQuery = $signLogModel->getLastQuery();
        // die($lastQuery);

        // $signLogModel->preWhere($conditions);
        $signLogModel->join($mTable, "{$sTable}.member_num={$mTable}.member_num");
        unset($arguments['limit']);
        unset($arguments['offset']);
        $signLogModel->preArg($arguments);
        $total = $signLogModel->getCount();

        return [$result, $total];
    }

    // 列出學員清單 針對無次數的方式計算
    public static function listMemberByNonCount(array $conditions, array $arguments, bool $onlyNonJoin)
    {
        $memberModel = Factories::models('MemberModel', ['getShared' => true]);
        $mTable = $memberModel->getTable();
        $signLogModel = Factories::models('SignLogModel', ['getShared' => true]);
        $sTable = $signLogModel->getTable();

        if ($onlyNonJoin) {
            $memberModel->preWhereRow("({$mTable}.member_num NOT IN (SELECT {$sTable}.member_num FROM {$sTable}))");
        }
        $memberModel->preWhere($conditions);
        $memberModel->preArg($arguments);
        $result = $memberModel->getArray();
        // $lastQuery = $memberModel->getLastQuery();
        // die($lastQuery);

        $memberModel->preWhere($conditions);
        unset($arguments['limit']);
        unset($arguments['offset']);
        $memberModel->preArg($arguments);
        $total = $memberModel->getCount();

        return [$result, $total];
    }

    // 列出簽到簽出記錄
    public static function listSingLog(array $conditions, array $arguments)
    {
        $signLogModel = Factories::models('SignLogModel', ['getShared' => true]);
        $workshopModel = Factories::models('WorkshopModel', ['getShared' => true]);
        $memberModel = Factories::models('MemberModel', ['getShared' => true]);
        $mTable = $memberModel->getTable();
        $wTable = $workshopModel->getTable();
        $sTable = $signLogModel->getTable();

        $signLogModel->join($mTable, "{$sTable}.member_num={$mTable}.member_num");
        $signLogModel->join($wTable, "{$sTable}.workshop_username={$wTable}.workshop_username");

        $signLogModel->preWhere($conditions);
        $signLogModel->preArg($arguments);
        $result = $signLogModel->getArray();
        // $lastQuery = $signLogModel->getLastQuery();
        // die($lastQuery);

        $signLogModel->preWhere($conditions);
        $signLogModel->join($mTable, "{$sTable}.member_num={$mTable}.member_num");
        $signLogModel->join($wTable, "{$sTable}.workshop_username={$wTable}.workshop_username");

        unset($arguments['limit']);
        unset($arguments['offset']);
        $signLogModel->preArg($arguments);
        $total = $signLogModel->getCount();

        return [$result, $total];
    }

    // 添加儀表版的資料
    public static function getStatusData()
    {
        $data = [];
        $signLogModel = Factories::models('SignLogModel', ['getShared' => true]);
        $workshopModel = Factories::models('WorkshopModel', ['getShared' => true]);
        $memberModel = Factories::models('MemberModel', ['getShared' => true]);
        $mTable = $memberModel->getTable();
        $wTable = $workshopModel->getTable();
        $sTable = $signLogModel->getTable();

        $sql = "SELECT {$wTable}.id, {$wTable}.workshop_name, {$wTable}.workshop_username,
        count({$sTable}.id) as total ,
        ( select count({$sTable}.id) from {$sTable}
        where {$sTable}.sign_in <> '0000-00-00 00:00:00'
        AND {$sTable}.workshop_username={$wTable}.workshop_username  ) as singInCount,
        ( select count({$sTable}.id) from {$sTable}
        where {$sTable}.sign_out <> '0000-00-00 00:00:00'
        AND {$sTable}.workshop_username={$wTable}.workshop_username  ) as singOutCount
        FROM {$wTable}
        LEFT JOIN {$sTable}
        ON {$sTable}.workshop_username={$wTable}.workshop_username
        GROUP BY {$wTable}.workshop_username";

        $result = $workshopModel->query($sql)->getResult();
        foreach ($result as $row) {
            $data[] = [
                'title' => "{$row->workshop_name} 統計",
                'total' => "{$row->total} 場次",
                'subtitle1' => '簽到人次 ' . $row->singInCount,
                'subtitle2' => '簽出人次 ' . $row->singOutCount,
                'subtitle3' => "工作坊編號 {$row->workshop_username}",
            ];
        }
        return $data;
    }

}
