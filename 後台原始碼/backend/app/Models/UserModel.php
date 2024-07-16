<?php

namespace App\Models;

use App\Models\BaseModel;

class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $insertID = 0;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ["username", "user_slug", "name", "password", "permissions"];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];
    protected $afterFind = ['escapeXSS'];

    protected function hashPassword(array $data)
    {
        if (!isset($data['data']['password'])) {
            return $data;
        }

        $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);

        return $data;
    }

    // 取得 DEMO 用戶用途
    public function getDemoUser($permissions = 'admin')
    {
        $this->preWhere(['permissions' => $permissions]);
        return $this->getArray(true);
    }

    // 取得公開用戶的查詢欄位
    public function getPublicUserSelect()
    {
        return "{$this->table}.username,{$this->table}.name,{$this->table}.permissions";
    }

    public function getUserAllSelect()
    {
        return "{$this->table}.id,{$this->table}.username,{$this->table}.password,{$this->table}.name,{$this->table}.permissions,{$this->table}.created_at,{$this->table}.updated_at";
    }

    public function getUsers(array $conditions, array $arguments = [])
    {
        $this->preWhere($conditions);
        $this->preArg($arguments);
        return $this->getArray(false);
    }

    public function getUser(array $conditions, array $arguments = [])
    {
        $this->preWhere($conditions);
        $this->preArg($arguments);
        return $this->getArray(true);
    }
}
