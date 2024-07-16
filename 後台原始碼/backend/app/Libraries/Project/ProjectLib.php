<?php

namespace App\Libraries\Project;

class ProjectLib
{

    // 檢查資料是否存在且不為(空 或 0)
    private static function isValidData(&$data)
    {
        return (isset($data) && !empty($data));
    }

    public static function initResponseData(string $status, string $requestTime, string $error = '', string $message = '', array $messages = [], array $data = []): array
    {
        $responseData = ['status' => $status, 'error' => $error, 'message' => $message, 'messages' => $messages, 'data' => $data];

        $responseData['domain'] = BASE_URL;
        // 補一個返回時間戳記
        $responseData['responseTime'] = time();
        $responseData['requestTime'] = $requestTime;
        return $responseData;
    }

}
