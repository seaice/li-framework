<?php
namespace Li;

/**
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


    // protected $_criteria = array(
    //     'field' => '*',
    //     'condition' => array(),
    //     'values' => array(),
    //     'with' => [],
    // );

class Criteria implements \ArrayAccess
{
    public $statement = 'select';
    public $field = '*';
    public $limit;
    public $condition = [];
    public $values = [];
    public $with = [];
    public $order;

    public $index; //结果集key

    public function __construct()
    {
    }

    public function merge($criteria)
    {
        if (!empty($criteria)) {

            foreach($criteria as $key => $value) {
                if(empty($value)) {
                    continue;
                }

                if($key == 'condition') {
                    $this->condition = array_merge_recursive($this->condition, $value);
                } else {
                    $this->$key = $value; 
                }
            }


            // // $criteria = (array)$criteria;
            // $condition = [];
            
            // if (isset($criteria['condition'])) {
            //     $condition = array_merge_recursive($this->_criteria['condition'], $criteria['condition']);
            // }

            // $this->_criteria = array_merge($this->_criteria, $criteria);
            // $this->_criteria['condition'] = $condition;
        }

    }

    public function where()
    {
        $argc = func_num_args();
        $argv = func_get_args();

        if($argc == 0 || !is_array($argv) && !empty($argv)) {
            return;
        }

        if ($argc == 1) {
            foreach ($argv[0] as $value) {
                $this->where(...$value);
            }
        } elseif ($argc == 2) {
            $this->condition[] = [$argv[0], '=', $argv[1]];
        } elseif ($argc == 3) {
            $this->condition[] = $argv;
        }
    }


    public function whereSql()
    {
        $argc = func_num_args();
        $argv = func_get_args();

        if($argc == 0 || !is_array($argv) && !empty($argv)) {
            return;
        }

        if($argc == 1) {
            $this->condition[] = $argv[0];
        } else {
            $sql = $argv[0];
            array_shift($argv);
            $parse = array_map(array($this, 'quoteValue'), $argv);
            $condition = vsprintf($sql, $parse);
            $this->condition[] = $condition;
        }
    }

    public function quoteValue($str)
    {
        if(is_numeric($str)) {
            return $str;
        }

        return "'" . addcslashes(str_replace("'", "''", $str), "\000\n\r\\\032") . "'";
    }

    public function addCondition($cond, $prase=null)
    {
        $this->condition[] = $cond;
    }

    public function compare()
    {
        $argc = func_num_args();
        $argv = func_get_args();

        if ($argc == 2 && !is_null($argv[1]) && $argv[1] !== '') {
            $this->condition[] = [$argv[0], $argv[1]];
        } elseif ($argc == 3 && !is_null($argv[2]) && $argv[2] !== '') {
            $this->condition[] = [$argv[0], $argv[1], $argv[2]];
        }
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
    public function offsetGet($offset)
    {
        return isset($this->$offset) ? $this->$offset : null;
    }
}
