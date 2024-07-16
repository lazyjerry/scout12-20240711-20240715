<?php
namespace App\Models;

use App\Models\BaseModel;

class LogModel extends BaseModel
{

    protected $table = 'logs';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $useSoftDeletes = false;
    protected $allowedFields = ["type", "code", "message", "path", "category"];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = '';
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
