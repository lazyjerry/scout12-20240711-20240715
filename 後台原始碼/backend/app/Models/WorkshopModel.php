<?php
namespace App\Models;

use App\Libraries\Admin\AdminLib;
use App\Libraries\Admin\CloudflareApi;
use App\Models\BaseModel;

class WorkshopModel extends BaseModel
{

    protected $table = 'workshops';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $useSoftDeletes = false;
    protected $allowedFields = ["workshop_name", "workshop_username", "workshop_password", "workshop_sessions", "workshop_area"];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    // protected $deletedField  = '';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $returnType = 'array';

    public function getTable()
    {
        return $this->table;
    }

    public function initWorkSessionss(string $sessions)
    {
        return AdminLib::initWorkshopSessions($sessions);
    }

    public function initPassword(string $password)
    {
        return md5($password);
    }

    public function workshopReadCsv(array $lineRow): int
    {
        $sessions = AdminLib::getAllowSessions();
        // ["名稱", "編號", "所屬活動中心"] ＋ 場次 ...
        $data = [
            'workshop_name' => $lineRow[0],
            'workshop_username' => $lineRow[1],
            'workshop_area' => $lineRow[2],
            'workshop_password' => $lineRow[3],
            'workshop_sessions' => '',
        ];

        $data['workshop_password'] = $this->initPassword($data['workshop_password']);

        $workshopSessions = [];
        for ($i = 0; $i < count($sessions); $i++) {
            if (!isset($lineRow[$i + 4])) {
                throw new \Exception("該筆無法正確匯入，資料被修改過。請確認匯入欄位與生成範例檔案是否一致");
            }

            if (!empty($lineRow[$i + 4])) {
                // 如果該筆場次為 1 或非 0 的數字/空值，則添加到允許場次中
                $workshopSessions[] = $sessions[$i];
            }
        }
        $data['workshop_sessions'] = implode(',', $workshopSessions);
        $data['workshop_sessions'] = $this->initWorkSessionss($data['workshop_sessions']);

        $isSuccess = $this->insert($data);

        if (!empty($isSuccess)) {
            $data['sync'] = $this->registerWorkShop($data['workshop_username'], $data['workshop_password'], $data['workshop_name'], $data['workshop_sessions']);
        }

        return (!empty($isSuccess)) ? 1 : 0;
    }

    public function registerWorkShop($workshop_username, $workshop_password, $workshop_name, $workshop_sessions): array
    {
        $cloudflareApi = new CloudflareApi();
        $syncResult = $cloudflareApi->registerWorkShop($workshop_username, $workshop_password, $workshop_name, $workshop_sessions);
        return jsonDecode($syncResult);
    }

    # ref: https://codeigniter4.github.io/userguide/models/model.html?highlight=model%20get%20builder#working-with-query-builder
}
