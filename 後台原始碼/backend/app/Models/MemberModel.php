<?php
namespace App\Models;

use App\Models\BaseModel;

class MemberModel extends BaseModel
{

    protected $table = 'members';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $useSoftDeletes = false;
    protected $allowedFields = ["member_num", "member_name", "member_area", "scout_name", "scout_num", "member_phone", "member_contact_name", "member_contact_phone"];

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

    # ref: https://codeigniter4.github.io/userguide/models/model.html?highlight=model%20get%20builder#working-with-query-builder
}
