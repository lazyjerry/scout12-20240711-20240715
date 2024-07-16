<?php
namespace App\Models;

use App\Libraries\Admin\AdminLib;
use App\Models\BaseModel;

class SignLogModel extends BaseModel
{

    protected $table = 'sign_logs';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $useSoftDeletes = false;
    protected $allowedFields = ["workshop_username", "member_num", "workshop_session", "sign_in", "sign_out"];

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

    public function fixWorkshopSession(string $session): string
    {
        $session = trim($session);
        if (AdminLib::isAllowSession($session)) {
            return $session;
        }

        return "0" . $session;
    }

    public function workshopReadCsv(array $lineRow): int
    {
        $session = $this->fixWorkshopSession($lineRow[2]);
        if (!AdminLib::isAllowSession($session)) {
            throw new \Exception("該筆無法正確匯入，場次資料錯誤，請檢查場次內容");
        }
        $signIn = (empty($lineRow[3])) ? '0000-00-00 00:00:00' : date('Y-m-d H:i:s', strtotime($lineRow[3]));
        $signOut = (empty($lineRow[4])) ? '0000-00-00 00:00:00' : date('Y-m-d H:i:s', strtotime($lineRow[4]));
        $data = [
            'workshop_username' => trim($lineRow[0]),
            'member_num' => trim($lineRow[1]),
            'workshop_session' => $session,
            'sign_in' => $signIn,
            'sign_out' => $signOut,
        ];

        $this->initBuilder();
        $conditions = [
            'workshop_username' => $lineRow[0],
            'member_num' => $lineRow[1],
            'workshop_session' => $lineRow[2],
        ];

        $count = $this->builder->where($conditions)->countAllResults();
        if ($count > 0) {
            return 0;
        }

        $isSuccess = $this->insert($data);
        return (!empty($isSuccess)) ? 1 : 0;
    }

    public function syncData(array $data, array $conditions)
    {
        $this->initBuilder();
        $count = $this->builder->where($conditions)->countAllResults();
        if ($count == 0) {
            return $this->insert($data);
        } else {
            $row = $this->builder->where($conditions)->get()->getRow();

            // 因為預設取得場次期間不會更新，所以以更新資料為主
            if (($row->sign_out == "0000-00-00 00:00:00" && $row->sign_out != $data['sign_out']) || ($row->sign_in == "0000-00-00 00:00:00" && $row->sign_in != $data['sign_in'])) {
                return $this->update($row->id, $data);
            }
        }
        return false;
    }

    # ref: https://codeigniter4.github.io/userguide/models/model.html?highlight=model%20get%20builder#working-with-query-builder
}
