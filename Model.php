<?php
namespace Li;

// $data = News::model()->findByPk(2);
// SELECT * FROM `news` WHERE (id = '2') LIMIT 1

// $data = News::model()->find(array(
//     // 'field'=>'id',
//     'condition'=>array(
//         array('id','=',1),
//     )
// ));
// SELECT * FROM `news` WHERE (id = '1') LIMIT 1

// $data = News::model()->findAll(array(
//     'field'=>'*',
//     'condition'=>array(
//         array('id','>',2),
//         array('title','!=',''),
//         array('id','in',array(1,2,3)),
//         array('id','between',array(1,5)),
//     ),
//     'order'=>'id desc',
// ));
// SELECT * FROM `news` WHERE (id > '2') AND (title != '') AND (id IN ('1','2','3')) AND (id BETWEEN '1' AND '5') ORDER BY id desc

// $data = News::model()
//     ->leftJoin('news_class','news_class.id=news.class')
//     ->findAll(array(
//         'field'=>'news.*,news_class.name',
//         'condition'=>array(
//             array('news.id','>',2),
//             array('title','!=',''),
//             array('news.id','in',array(1,2,3)),
//             array('news.id','between',array(1,5)),
//         ),
//         'order'=>'news.id desc',
//     )
// );
// SELECT news.*,news_class.name FROM `news` LEFT JOIN news_class ON news_class.id=news.class WHERE (news.id > '2') AND (title != '') AND (news.id IN ('1','2','3')) AND (news.id BETWEEN '1' AND '5') ORDER BY news.id desc

/*
criteria:
statement   // 类型
-all         // 暂时废弃
field       // 选择的列
condition   // where条件
values      // 绑定的值
limit       // limit
join        // 数组，存储join信息
order       // 排序
sql         // 最终解析的sql
 */

abstract class Model implements \ArrayAccess {
    protected $db = 'default'; // 数据库名
    protected $table; // 表名
    protected $pk = 'id'; // 主键

    const BELONGS_TO = 'BelongsToRelation';
    const HAS_ONE = 'HasOneRelation';
    const HAS_MANY = 'HasManyRelation';
    const MANY_MANY = 'ManyManyRelation';
    // const STAT = 'StatRelation';

    public $validator;
    public $attributes; // 保存查询结果
    protected $_data; // 保存查询结果
    protected $_events = array(
        'beforeFind' => false,
        'beforeDelete' => false,
        'beforeSave' => false,
        'afterFind' => false,
        'afterDelete' => false,
        'afterSave' => false,
    );

    protected $_isNew = true;
    protected $_scenario = 'default';

    protected static $_models = array();

    protected $_criteria = array(
        'field' => '*',
        'condition' => array(),
        'values' => array(),
        'with' => [],
    );

    private $_position = 0;

    public function __construct($scenario = null) {
        if (!empty($scenario)) {
            $this->_scenario = $scenario;
        }
    }

    public function __get($name) {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        if (array_key_exists($name, $this->relations())) {
            $relation = $this->relations()[$name];
            if($relation[1] == 'BelongsToRelation') {
                $this->attributes[$name] = $relation[2]::model()->find([
                    'condition' => [
                        [$relation[3], '=', $this->$relation[0]],
                    ],
                ]);

                return $this->attributes[$name];
            }
        }
        return null;
    }

    public function __set($name, $value) {
        $this->attributes[$name] = $value;
    }

    public function getDb() {
        return $this->db;
    }

    public function getTable() {
        if (empty($this->table)) {
            $this->table = strtolower(get_class($this));
        }
        return $this->table;
    }

    public function getPk() {
        return $this->pk;
    }

    public static function model($className = __CLASS__) {
        if (isset(self::$_models[$className])) {
            return self::$_models[$className];
        } else {
            $model = self::$_models[$className] = new $className(null);
            return $model;
        }
    }

    public function relations() {
        return [];
    }

    public function with() {
        if(func_num_args()>0)
        {
            $with=func_get_args();

            foreach($with as $name) {
                if (!array_key_exists($name, $this->relations())) {
                    throw new Exception('没有这个关系'.$name);
                }
            }

            $this->_criteria['with'] = $with;
        }

        return $this;
    }

    protected function _transKeys($table, $keys) {
        $field = [];
        foreach($keys as $key) {
            $field[] = "{$table}.{$key} as {$table}_{$key}";
        }
        return $field;
    }

    /**
     * 唤起事件
     */
    public function event($event) {
        if (!isset($this->_events[$event])) {
            throw new Exception("不支持事件：" . $event);
        }
        $this->_events[$event] = true;
        return $this;
    }

    /**
     * @return mixed null or array
     */
    public function find($criteria = array()) {
        if (is_array($criteria) && !empty($criteria)) {
            $this->_criteria = array_merge($this->_criteria, $criteria);
        }

        $this->_criteria['statement'] = 'select';
        $this->_criteria['limit'] = 1;
        $this->build();
        return $this->populateRecord($this->_query());
    }

    /**
     * @return mixed null or model
     */
    public function findByPk($pk) {
        $this->_criteria['statement'] = 'select';
        $this->_criteria['condition'][] = array($this->pk, $pk);
        $this->build();
        return $this->populateRecord($this->_query());
    }

    /**
     * @param mixed criteria find condition
     */
    public function findAll($criteria = array()) {
        if($criteria instanceof Criteria) {
            $criteria = $criteria->getCriteria();
        }
        if ((is_array($criteria)) && !empty($criteria)) {
            $this->_criteria = array_merge_recursive($this->_criteria, $criteria);
        }

        $this->_criteria['statement'] = 'select';
        $this->build();

        return $this->populateRecords($this->_queryAll());
    }

    public function count($criteria = array()) {
        if($criteria instanceof Criteria) {
            $criteria = $criteria->getCriteria();
        }

        if (is_array($criteria) && !empty($criteria)) {
            $this->_criteria = array_merge_recursive($this->_criteria, $criteria);
        }

        $this->_criteria['statement'] = 'select';
        $this->_criteria['field'] = 'COUNT(*) as `count`';

        $this->build();
        $ret = $this->_query()['count'];
        $this->reset();
        return $ret;
    }

    public function update($data, $criteria = null) {
        if (is_array($criteria) && !empty($criteria)) {
            array_merge($this->_criteria, $criteria);
        }

        $this->_criteria['statement'] = 'update';
        $this->_criteria['data'] = $data;

        $this->build();
        return $this->exec();
    }

    public function updateByPk($pk, $data) {
        if (empty($data) && !is_array($data)) {
            return false;
        }

        $this->_criteria['statement'] = 'update';

        $this->_criteria['condition'][] = array($this->pk, $pk);

        $this->_criteria['data'] = $data;

        $this->build();
        return $this->exec();
    }

    public function delete($criteria = null) {
        if ($this->_events['beforeDelete']) {
            $this->beforeDelete();
        }
        if (is_array($criteria) && !empty($criteria)) {
            array_merge($this->_criteria, $criteria);
        }

        $this->_criteria['statement'] = 'delete';

        $this->build();

        if (!$this->exec()) {
            return false;
        }

        if ($this->_events['afterDelete']) {
            $this->afterDelete();
        }
        return true;
    }

    public function deleteByPk($pk) {
        if ($this->_events['beforeDelete']) {
            $this->beforeDelete();
        }
        $this->_criteria['statement'] = 'delete';
        $this->_criteria['condition'][] = array($this->pk, $pk);
        $this->build();

        if (!$this->exec()) {
            return false;
        }

        if ($this->_events['afterDelete']) {
            $this->afterDelete();
        }
        return true;
    }

    protected function build() {
        $sqlConditon = '';
        if (is_array($this->_criteria['condition'])
            && !empty($this->_criteria['condition'])
        ) {
            $i = 0;
            $conditon = null;
            foreach ($this->_criteria['condition'] as $value) {
                if (is_array($value)) {
                    if (count($value) == 3) {
                        $value[1] = strtolower($value[1]);
                        if ($value[1] == 'in') {
                            $inCount = count($value[2]);
                            $in = '';
                            foreach ($value[2] as $key => $inValue) {
                                $in .= ':' . $i;
                                if ($key < $inCount - 1) {
                                    $in .= ',';
                                }
                                $this->_criteria['values'][] = array(':' . $i, $inValue);
                                $i++;
                            }

                            $conditon[] = '(' . $value[0] . ' IN ' . '(' . $in . '))';
                        } else if ($value[1] == 'between') {
                            $conditon[] = '(' . $value[0] . ' BETWEEN ' . ':' . $i . ' AND :' . ($i + 1) . ')';
                            $this->_criteria['values'][] = array(':' . $i, $value[2][0]);
                            $this->_criteria['values'][] = array(':' . ($i + 1), $value[2][1]);
                            $i += 2;
                        } else {
                            $conditon[] = '(' . $value[0] . ' ' . $value[1] . ' ' . ':' . $i . ')';
                            $this->_criteria['values'][] = array(':' . $i, $value[2]);
                        }
                    } else if (count($value) == 2) {
                        $conditon[] = '(' . $value[0] . '=:' . $i . ')';
                        $this->_criteria['values'][] = array(':' . $i, $value[1]);
                    }
                    $i++;
                } else {
                    $conditon[] = $value;
                }
            }

            $sqlConditon .= ' WHERE ' . implode(' AND ', $conditon);
        }

        if ($this->_criteria['statement'] == 'select') {
            if(!empty($this->_criteria['with'])) {
                $fields = $this->_transKeys($this->getTable(), array_keys($this->alias()));

                foreach($this->_criteria['with'] as $name) {
                    $relation = $this->relations()[$name];

                    $this->leftJoin($relation[2]::model()->getTable(), $this->getTable() . '.' . $relation[0] . '=' . $relation[2]::model()->getTable() . '.'. $relation[3]);

                    $fields = array_merge($fields , $this->_transKeys($relation[2]::model()->getTable(), array_keys($relation[2]::model()->alias())));
                }

                $this->_criteria['field'] = implode(',', $fields);                  
            }

            $sql = 'SELECT ' . $this->_criteria['field'] . ' FROM `' . $this->getTable() . '`';
            
            if (!empty($this->_criteria['join'])) {
                foreach ($this->_criteria['join'] as $key => $value) {
                    $sql .= $value;
                }
            }

            $sql .= $sqlConditon;

            if (isset($this->_criteria['group'])) {
                $sql .= ' GROUP BY ' . $this->_criteria['group'];

                if (isset($this->_criteria['having'])) {
                    $sql .= ' HAVING ' . $this->_criteria['having'];
                }
            }

            if (isset($this->_criteria['order'])) {
                $sql .= ' ORDER BY ' . $this->_criteria['order'];
            }

            if (!empty($this->_criteria['limit'])) {
                $sql .= ' LIMIT ' . $this->_criteria['limit'];
            }
        } else if ($this->_criteria['statement'] == 'update') {
            $sql = 'UPDATE `' . $this->getTable() . '` SET';

            $countData = count($this->_criteria['data']);
            $iData = 0;
            foreach ($this->_criteria['data'] as $key => $value) {
                if ($value instanceof Expression) {
                    $sql .= '`'. $key . '`='.$value;
                } else {
                    $sql .= ' `' . $key . '`=:' . $key;
                    $this->_criteria['values'][] = array(':' . $key, $value);
                }

                if ($iData < $countData - 1) {
                    $sql .= ',';
                }

                $iData++;
            }

            $sql .= $sqlConditon;
        } else if ($this->_criteria['statement'] == 'insert') {
            $sql = 'INSERT INTO `' . $this->getTable() . '` (';
            $sqlField = '';
            $sqlValue = '';

            $countField = count($this->_criteria['field']);

            foreach ($this->_criteria['field'] as $key => $value) {
                $sqlField .= '`' . $value . '`';
                $sqlValue .= ':' . $value;
                if ($key < $countField - 1) {
                    $sqlField .= ',';
                    $sqlValue .= ',';
                }
            }

            $sql .= $sqlField . ') VALUES (' . $sqlValue . ')';
        } else if ($this->_criteria['statement'] == 'delete') {
            $sql = 'DELETE FROM `' . $this->getTable() . '`';
            $sql .= $sqlConditon;
        }
        $this->_criteria['sql'] = $sql;
    }

    public function getCondition() {
        return $this->_criteria['condition'];
    }

    private function _query($fetchStyle = \PDO::FETCH_ASSOC) {
        if ($this->_events['beforeFind']) {
            $this->beforeFind();
        }
        $dbName = $this->getDb();
        Db::db()->$dbName->prepare($this->_criteria['sql']);
        Db::db()->$dbName->bindValues($this->_criteria['values']);
        return Db::db()->$dbName->fetch($fetchStyle);
    }

    private function _queryAll($fetchStyle = \PDO::FETCH_ASSOC) {
        if ($this->_events['beforeFind']) {
            $this->beforeFind();
        }
        $dbName = $this->getDb();
        Db::db()->$dbName->prepare($this->_criteria['sql']);
        Db::db()->$dbName->bindValues($this->_criteria['values']);
        return Db::db()->$dbName->fetchAll($fetchStyle);
    }

    protected function init() {

    }

    protected function instantiate($attributes) {
        $class = get_class($this);
        $model = new $class();
        $model->attributes = $attributes;
        return $model;
    }

    protected function populateRecord($attributes, $reset=true) {
        if ($attributes !== false) {
            $record = $this->instantiate($attributes);
            $record->_isNew = false;
            $record->init();
            if ($this->_events['afterFind']) {
                $record->afterFind();
            }
            if($reset) {
                $this->reset();
            }
            return $record;
        } else {
            return null;
        }
    }

    protected function beforeFind() {
    }
    protected function beforeDelete() {
    }
    protected function beforeSave() {
    }
    protected function afterFind() {
    }
    protected function afterDelete() {
    }
    protected function afterSave() {
    }

    /**
     * 获得关联查询结果集中的数据
     * @return [type] [description]
     */
    protected function _getRelationAttributes($data, $relation) {

    }

    protected function populateRecords($data, $index = null) {
        $records = array();

        if(!empty($this->_criteria['with'])) {
            foreach($data as $value) {
                if(!isset($records[$value[$this->getTable() . '_' . $this->pk]])) {
                    $tmp = [];
                    foreach ($this->alias() as $key => $alias) {
                        $tmp[$key] = $value[$this->getTable() . '_' . $key];
                    }

                    if (($record = $this->populateRecord($tmp, false)) !== null) {
                        $records[$value[$this->getTable() . '_' . $this->pk]] = $record;
                    }
                }

                foreach($this->_criteria['with'] as $name) {
                    $relation = $this->relations()[$name];
                    $table = $relation[2]::model()->getTable();
                    $tmp = [];
                    foreach($relation[2]::model()->alias() as $key => $alias) {
                        $tmp[$key] = $value[$table . '_' . $key];
                    }

                    if($relation[1] == self::BELONGS_TO) {
                        $records[$this->pk][$name] = $tmp;
                    } else if($relation[1] == self::HAS_ONE) {
                        $records[$this->pk][$name] = $tmp;
                    } else if($relation[1] == self::HAS_MANY) {
                        $records[$this->pk][$name] = $tmp;
                    } else if($relation[1] == self::MANY_MANY) {
                        $records[$this->pk][$name] = $tmp;
                    }
                }
            }
        } else {
            foreach ($data as $attributes) {
                if (($record = $this->populateRecord($attributes, false)) !== null) {
                    if ($index === null) {
                        $records[] = $record;
                    } else {
                        $records[$record->$index] = $record;
                    }
                }
            }
        }
        $this->reset();
        return $records;
    }


    protected function exec() {
        $dbName = $this->getDb();
        Db::db()->$dbName->prepare($this->_criteria['sql']);
        Db::db()->$dbName->bindValues($this->_criteria['values']);
        $this->reset();
        return Db::db()->$dbName->execute();
    }

    public function save($runValidation = true, $attributeNames = null) {
        if ($runValidation === true) {
            if ($this->_isNew) {
                if ($this->validate($this->_scenario)) {
                    return false;
                }
            }
        }

        if ($this->_events['beforeSave']) {
            $this->beforeSave();
        }
        $pk = $this->getPk();

        if($this->_isNew) {
            $this->_criteria['statement'] = 'insert';
            $this->_criteria['field'] = array();

            foreach ($this->attributes as $key => $value) {
                $this->_criteria['field'][] = $key;
                $this->_criteria['values'][] = array(':' . $key, $value);
            }
        } else {
            $this->_criteria['statement'] = 'update';
            $this->where($pk, $this->$pk);
            $this->_criteria['data'] = $this->attributes;
        }
        $this->build();
        $ret = $this->exec();
        if($this->_isNew) {
            $this->$pk = $this->getLastId();
        }
        if ($this->_events['afterSave']) {
            $this->afterSave();
        }
        return $ret;
    }

    public function getLastId() {
        $dbName = $this->getDb();
        return Db::db()->$dbName->getLastInsertID();
    }
    /**
     *
     */
    // public function where()
    // {
    //     $num_args = func_num_args();
    //     $args = func_get_args();
    //     if($num_args == 1)
    //     {
    //         foreach ($args[0] as $key => $value) {
    //             $this->_criteria['condition'][] = $key.'=:'.$key;
    //             $this->_criteria['values'][':'.$key] = $value;
    //         }
    //     }
    //     else if($num_args == 2)
    //     {
    //         $this->_criteria['condition'][] = $args[0].'=:'.$args[0];
    //         $this->_criteria['values'][':'.$args[0]] = $args[1];
    //     }
    //     else if($num_args == 3)
    //     {
    //         $this->_criteria['condition'][] = $args[0].$args[1].':'.$args[0];
    //         $this->_criteria['values'][':'.$args[0]] = $args[2];
    //     }

    //     return $this;
    // }

    /**
     * ->whereSql('id>%d',array('6 OR 1=1','test or 1=1'))
     */

    protected function whereSql($sql, $parse = null) {
        if (!is_null($parse) && is_string($sql)) {
            if (!is_array($parse)) {
                $parse = func_get_args();
                array_shift($parse);
            }
            $dbName = $this->getDb();
            $parse = array_map(array(Db::db()->$dbName, 'quoteValue'), $parse);
            $condition = vsprintf($sql, $parse);
            $this->_criteria['condition'][] = $condition;
        }

        return $this;
    }
    /**
     * 支持2或者3个参数
     * $model->where('name','=','test')
     *       ->where('id','in',array(1,2,3))
     *       ->where('id=1 or id=2')
     *       ->where('score','>',10);
     *       ->where('score',8);
     */
    public function where() {
        $num_args = func_num_args();
        $args = func_get_args();

        // if($num_args == 1)
        // {
        //     $this->_criteria['condition'][]=$args[0];
        // }

        if ($num_args == 2) {
            $this->_criteria['condition'][] = $args;
        } else if ($num_args == 3) {
            $this->_criteria['condition'][] = $args;
        }

        return $this;
    }

    // public function first()
    // {
    //     if(empty($this->_data))
    //     {
    //         return null;
    //     }
    //     $this->attributes = $this->_data[0];
    //     return $this;
    // }

    protected function filter() {
        $num_args = func_num_args();
        $args = func_get_args();

        if ($num_args == 1) {
            if (isset($_GET[$args[0]]) && !empty($_GET[$args[0]])) {
                $this->where($args[0], $_GET[$args[0]]);
            }
        } else if ($num_args == 2 && !empty($args[1])) {
            $this->where($args[0], $args[1]);
        } else if ($num_args == 3 && !empty($args[2])) {
            $this->where($args[0], $args[1], $args[2]);
        }
    }

    public function group($group) {
        $this->_criteria['group'] = $group;
        return $this;
    }

    public function order($order) {
        $this->_criteria['order'] = $order;
        return $this;
    }

    protected function having($having) {
        $this->_criteria['having'] = $having;
        return $this;
    }

    public function page($page, $pageSize = 10) {
        return $this->limit(($page - 1) * $pageSize, $pageSize);
    }

    public function limit($offset, $row_count) {
        $this->_criteria['limit'] = "$offset, $row_count";
        return $this;
    }

    public function field($field) {
        $this->_criteria['field'] = $field;
        return $this;
    }

    protected function join($table, $condition) {
        $this->_criteria['join'][] = " INNER JOIN $table ON $condition";
        return $this;
    }

    protected function leftJoin($table, $condition) {
        $this->_criteria['join'][] = " LEFT JOIN $table ON $condition";
        return $this;
    }
    protected function rightJoin($table, $condition) {
        $this->_criteria['join'][] = " RIGHT JOIN $table ON $condition";
        return $this;
    }

    protected function reset() {
        $this->_criteria = array(
            'field' => '*',
            'condition' => array(),
            'values' => array(),
        );
    }

    protected function getColumns($tableName = '') {
        if ($tableName == '') {
            $tableName = $this->getTable();
        }

        $this->_criteria['sql'] = 'SHOW FULL COLUMNS FROM `' . $tableName . '`';

        $dbName = $this->getDb();
        Db::db()->$dbName->prepare($this->_criteria['sql']);
        $column = Db::db()->$dbName->fetchAll();
        $this->reset();

        unset($value);
        foreach ($column as &$value) {
            $value['dataType'] = $this->_extractType($value['Type']);
        }

        return $column;
    }

    private function _extractType($dbType) {
        if (stripos($dbType, 'int') !== false && stripos($dbType, 'unsigned int') === false) {
            $type = 'integer';
        } elseif (stripos($dbType, 'bool') !== false) {
            $type = 'boolean';
        } elseif (preg_match('/(real|floa|doub)/i', $dbType)) {
            $type = 'double';
        } else {
            $type = 'string';
        }

        return $type;
    }

    public function validate($scenario = '') {
        if (!empty($scenario)) {
            $this->_scenario = $scenario;
        }

        $rules = $this->rules();

        if (isset($rules[$this->_scenario])) {
            $this->validator = Validator::make($this->attributes, $rules[$this->_scenario], [], $this->alias());
            return $this->validator->fails();
        }

        return true;
    }

    public function getValidator($scene = 'default') {
        $rule = $this->rules();
        if (isset($this->validator) && $this->validator instanceof Validator) {
            $this->validator->getValidator();
        } else {
            return '{}';
        }
    }

    public function getErrors() {
        if ($this->validator instanceof Validator) {
            return $this->validator->errors;
        }

        return [];
    }

    protected function rules() {
        return array();
    }

    protected function alias() {
        return array();
    }

    public function getCriteria() {
        return $this->_criteria;
    }

    // private $container = array();
    // public function __construct() {
    //     $this->container = array(
    //         "one"   => 1,
    //         "two"   => 2,
    //         "three" => 3,
    //     );
    // }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }
    public function offsetExists($offset) {
        return isset($this->attributes[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->attributes[$offset]);
    }
    public function offsetGet($offset) {
        return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }
}
