<?php
// https://codeigniter4.github.io/userguide/models/model.html
namespace App\Models;

use App\Libraries\TimeUtils;
use CodeIgniter\Model;

class BaseModel extends Model
{

    # ref: https://codeigniter4.github.io/userguide/models/model.html?highlight=model%20get%20builder#working-with-query-builder
    protected $table = '';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ["id"];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    // protected $deletedField  = 'deleted_time';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = ['escapeXSS'];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    protected function escapeXSS($data)
    {
        return esc($data);
    }

    public function getAllowedFields()
    {
        return $this->allowedFields;
    }

    public function getNow()
    {
        return TimeUtils::getNow();
    }

    public function getTable()
    {
        return $this->table;
    }

// -----------

    protected function initBuilder()
    {
        if (!isset($this->builder)) {
            $this->builder = $this->builder();
        }
    }

    public function truncate()
    {
        $this->initBuilder();
        return $this->builder->truncate();
    }

    public function getBuilder()
    {
        $this->initBuilder();
        return $this->builder;
    }

    public function query($sql)
    {
        return $this->db->query($sql);
    }

    public function getLastQuery()
    {
        return $this->db->getLastQuery();
    }

    public function join(string $table, string $conditionOrRawSql, string $type = 'left')
    {
        $this->initBuilder();
        $this->builder->join($table, $conditionOrRawSql, $type);
        return $this->builder;
    }

    public function debug()
    {
        return $this->getLastQuery()->getQuery();
    }

    public function preWhereRow(string $condition, string $type = "where")
    {
        $this->initBuilder();
        if (method_exists($this->builder, $type)) {
            $this->builder->{$type}($condition);
        }
        return $this->builder;
    }

    public function preWhere(array $conditions, string $type = "where")
    {
        $this->initBuilder();
        if (method_exists($this->builder, $type)) {
            if ("where" == $type) {
                $this->builder->{$type}($conditions);
            } else {
                foreach ($conditions as $condition) {
                    $this->builder->{$type}($condition);
                }
            }
        }
        return $this->builder;
    }

    public function getCount()
    {
        $this->initBuilder();

        return $this->builder->countAllResults();
    }

    public function deleteByWhere(array $where = [])
    {
        $this->initBuilder();
        if (!empty($where)) {
            $this->preWhere($where);
        }
        return $this->builder->delete();
    }

    public function preArg(array $arguments)
    {
        $this->initBuilder();
        if (isset($arguments['select'])) {
            $this->builder->select($arguments['select']);
        }

        if (isset($arguments['selectSubquery'])) {
            $this->builder->selectSubquery($arguments['selectSubquery'][0], [1]);
        }

        if (isset($arguments['from'])) {
            $this->builder->from($arguments['from']);
        }

        if (isset($arguments['limit'])) {
            if (is_array($arguments['limit'])) {
                $this->builder->limit($arguments['limit'][0], $arguments['limit'][1]);
            } else {
                $this->builder->limit($arguments['limit']);
            }
        }
        if (isset($arguments['offset'])) {
            $this->builder->offset($arguments['offset']);
        }

        if (isset($arguments['fromSubquery'])) {
            // https://codeigniter4.github.io/userguide/database/query_builder.html?highlight=builder#builder-fromsubquery
            $this->builder->fromSubquery($arguments['fromSubquery']);
        }

        if (isset($arguments['orderBy'])) {
            if (is_string($arguments['orderBy'])) {
                $this->builder->orderBy($arguments['orderBy']);
            } else if (is_array($arguments['orderBy'])) {
                # $builder->orderBy('title', 'RANDOM');
                $this->builder->orderBy($arguments['orderBy'][0], $arguments['orderBy'][1]);
            }
        }

        if (isset($arguments['groupBy'])) {
            if (is_string($arguments['groupBy'])) {
                $this->builder->groupBy($arguments['groupBy']);
            } else if (is_array($arguments['groupBy'])) {
                # $builder->orderBy('title', 'RANDOM');
                $this->builder->groupBy($arguments['groupBy']);
            }
        }

        if (isset($arguments['having'])) {
            if (is_string($arguments['having'])) {
                $this->builder->having($arguments['having']);
            } else if (is_array($arguments['having'])) {
                if (array_is_list($arguments['having'])) {
                    $this->builder->having($arguments['having'][0], $arguments['having'][1]);
                } else {
                    $this->builder->having($arguments['having']);
                }
            }
        }
        return $this->builder;
    }

    public function get()
    {
        $this->builder->get();
    }

    public function getArray(bool $isRow = false)
    {
        $this->initBuilder();
        if ($isRow) {
            return $this->builder->get()->getRowArray();
        }
        return $this->builder->get()->getResultArray();

    }

    public function getObject(bool $isRow = false)
    {
        $this->initBuilder();
        if ($isRow) {
            return $this->builder->get()->getRow();
        }
        return $this->builder->get()->getResult();
    }

    public function insertOrOverwrite(array $data, array $conditions)
    {
        $this->initBuilder();
        $row = $this->builder->where($conditions)->select($this->primaryKey)->get()->getRowArray();
        if (empty($row)) {
            log_message("debug", "insertOrOverwrite() insert");
            return $this->insert($data);
        }
        $pkValue = $row[$this->primaryKey];
        if (isset($data[$this->primaryKey])) {
            unset($data[$this->primaryKey]);
        }
        log_message("debug", "insertOrOverwrite() update");
        return $this->update($pkValue, $data);
    }

    public function insertIfNotExists(array $data, array $conditions)
    {
        $this->initBuilder();
        $count = $this->builder->where($conditions)->countAllResults();
        if ($count == 0) {
            return $this->insert($data);
        }
        return false;
    }

    public function insertOrUpdate(array $data)
    {

        if (isset($data[$this->primaryKey])) {
            $this->initBuilder();
            $count = $this->builder->where($this->primaryKey, $data[$this->primaryKey])->countAllResults();
            if ($count > 0) {
                $pkValue = $data[$this->primaryKey];
                unset($data[$this->primaryKey]);
                log_message("debug", "insertOrUpdate() update");
                return $this->update($pkValue, $data);
            }

        }
        log_message("debug", "insertOrUpdate() insert");
        return $this->insert($data);
    }

    public function setConditions(array &$conditions, array &$data, string $keyName, bool $isLike = false, bool $allowEmpty = false): void
    {
        if (isset($data[$keyName])) {
            if (!$allowEmpty && '' == trim($data[$keyName])) {
                return;
            }

            if (!$isLike) {
                $conditions[$keyName] = $data[$keyName];
                return;

            } else if ('' == trim($data[$keyName])) {
                // 如果允许空白，但是又允许 like ，但是又为空的话则纳入为空的条件
                $conditions[$keyName] = "";
                return;
            }

            $conditions["{$keyName} LIKE"] = '%' . $data[$keyName] . '%';
            return;

        }
    }

    // https://monkenwu.github.io/codeIgniter4-taiwan-User-Guide/models/model.html#id6
    // public function insert(array $data){}

    // public function countAllResults(){}

    // public function insertBatch(array $data){}

    // public function update(mixed $id, array $data){}

    // 不好用，查詢使用 in 有點耗能
    // public function save(array $data){}

}
