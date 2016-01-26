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
        
abstract class Model 
// implements \IteratorAggregate
{
    public $pk; // 主键

    private $_tableName; // 表名

    private $dbName; // 数据库名

    private static $_models=array();
    private $_criteria = array(
        'field' => '*',
        'condition' => array(),
        'values' => array(),
    );

    public function __construct($col=array())
    {
        
    }

    public function getDbName()
    {
        if(empty($this->dbName))
        {
            $this->dbName = 'default';
        }
        return $this->dbName;
    }

    public function getTabelName()
    {
        if(empty($this->_tableName))
        {
            $this->_tableName = strtolower(get_class($this));
        }
        return $this->_tableName;
    }

    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    public static function model($className=__CLASS__)
    {
        if(isset(self::$_models[$className]))
            return self::$_models[$className];
        else
        {
            $model=self::$_models[$className]=new $className(null);
            return $model;
        }
    }

    /**
     * @return mixed null or array
     */
    public function find($criteria=array())
    {
        if(is_array($criteria) && !empty($criteria))
        {
            $this->_criteria = array_merge($this->_criteria,$criteria);
        }

        $this->_criteria['statement'] = 'select';
        $this->_criteria['limit'] = 1;
        $this->build();
        $data = $this->_query();
        return empty($data)?null:$data;
    }

    /**
     * @return mixed null or array
     */
    public function findByPk($pk)
    {
        $this->_criteria['statement'] = 'select';

        $this->_criteria['condition'][]=array($this->pk,$pk);
        
        $this->build();

        $data = $this->_query();
        return empty($data)?null:$data;
    }

    public function findAll($criteria=array())
    {
        if(is_array($criteria) && !empty($criteria))
        {
            $this->_criteria = array_merge($this->_criteria,$criteria);
        }

        $this->_criteria['statement'] = 'select';
        $this->build();
        return $this->_queryAll();
    }


    public function count($criteria=array())
    {
        if(is_array($criteria) && !empty($criteria))
        {
            $this->_criteria=array_merge_recursive ($this->_criteria,$criteria);
        }

        $this->_criteria['statement'] = 'select';
        $this->_criteria['field'] = 'COUNT(*) as `count`';

        $this->build();
        return $this->_query()['count'];
    }


    public function update($criteria,$data)
    {
        if(is_array($criteria) && !empty($criteria))
        {
            array_merge($this->_criteria,$criteria);
        }

        $this->_criteria['statement'] = 'update';
        $this->_criteria['data'] = $data;

        $this->build();
        return $this->query()[0]['count'];
    }

    public function updateByPk($pk, $data)
    {
        if(empty($data) && !is_array($data))
        {
            return false;
        }

        $this->_criteria['statement'] = 'update';

        $this->_criteria['condition'][]=array($this->pk,$pk);

        $this->_criteria['data']=$data;

        $this->build();
        return $this->exec();
    }

    
    public function delete($criteria,$data)
    {

        if(is_array($criteria) && !empty($criteria))
        {
            array_merge($this->_criteria,$criteria);
        }

        $this->_criteria['statement'] = 'delete';

        $this->build();
        return $this->exec();
    }
    
    public function deleteByPk($pk)
    {
        $this->_criteria['statement'] = 'delete';
        $this->_criteria['condition'][]=array($this->pk,$pk);

        $this->build();
        return $this->exec();
    }

    private function build()
    {
        $sqlConditon='';
        if(is_array($this->_criteria['condition']) 
            && !empty($this->_criteria['condition'])
        )
        {
            $i=0;
            $conditon=null;
            foreach($this->_criteria['condition'] as $value)
            {
                if(is_array($value))
                {
                    if(count($value) == 3)
                    {
                        $value[1]=strtolower($value[1]);
                        if($value[1] == 'in')
                        {
                            $inCount=count($value[2]);
                            $in='';
                            foreach($value[2] as $key=>$inValue)
                            {
                                $in.=':'.$i;
                                if($key < $inCount - 1)
                                {
                                    $in.=',';
                                }
                                $this->_criteria['values'][]=array(':'.$i,$inValue);
                                $i++;
                            }

                            $conditon[]='('.$value[0].' IN '.'('.$in.'))';
                        }
                        else if($value[1] == 'between')
                        {
                            $conditon[]='('.$value[0].' BETWEEN '.':'.$i.' AND :'.($i+1).')';
                            $this->_criteria['values'][]=array(':'.$i,$value[2][0]);
                            $this->_criteria['values'][]=array(':'.($i+1),$value[2][1]);
                            $i+=2;
                        }
                        else
                        {
                            $conditon[]='('.$value[0].' '.$value[1].' '.':'.$i.')';
                            $this->_criteria['values'][]=array(':'.$i,$value[2]);
                        }
                    }
                    else if(count($value) == 2)
                    {
                        $conditon[]='('.$value[0].'=:'.$i.')';
                        $this->_criteria['values'][]=array(':'.$i,$value[1]);
                    }
                    $i++;
                }
                else
                {
                    $conditon[]=$value;
                }
            }

            $sqlConditon .= ' WHERE '.implode(' AND ', $conditon);
        }

        if($this->_criteria['statement'] == 'select')
        {
            $sql = 'SELECT '.$this->_criteria['field'] . ' FROM `'.$this->getTabelName().'`';

            if(!empty($this->_criteria['join']))
            {
                foreach ($this->_criteria['join'] as $key => $value) {
                    $sql .= $value;
                }
            }

            $sql.=$sqlConditon;
            
            if(isset($this->_criteria['group']))
            {
                $sql .= ' GROUP BY '.$this->_criteria['group'];

                if(isset($this->_criteria['having']))
                {
                    $sql .= ' HAVING '.$this->_criteria['having'];
                }
            }

            if(isset($this->_criteria['order']))
            {
                $sql .= ' ORDER BY '.$this->_criteria['order'];
            }

            if(!empty($this->_criteria['limit']))
            {
                $sql .= ' LIMIT '.$this->_criteria['limit'];
            }
        }
        else if($this->_criteria['statement'] == 'update')
        {
            $sql = 'UPDATE `'.$this->getTabelName().'` SET';

            $countData = count($this->_criteria['data']);
            $iData=0;
            foreach($this->_criteria['data'] as $key=>$value)
            {
                $sql .= ' `'.$key.'`=:'.$key;
                
                if($iData < $countData-1)
                    $sql .= ',';

                $iData++;
                $this->_criteria['values'][] = array(':'.$key,$value);
            }

            $sql.=$sqlConditon;
        }
        else if($this->_criteria['statement'] == 'insert')
        {
            $sql = 'INSERT INTO `'.$this->getTabelName().'` (';
            $sqlField='';
            $sqlValue='';

            $countField = count($this->_criteria['field']);

            foreach($this->_criteria['field'] as $key=>$value)
            {
                $sqlField.='`'.$value.'`';
                $sqlValue.=':'.$value;
                if($key < $countField - 1)
                {
                    $sqlField .= ',';
                    $sqlValue .= ',';
                }
            }

            $sql.=$sqlField.') VALUES ('.$sqlValue.')';
        }
        else if($this->_criteria['statement'] == 'delete')
        {
            $sql = 'DELETE FROM `'.$this->getTabelName().'`';
            $sql .= $sqlConditon;
        }

        // debug($this->_criteria);
        // debug($sql);

        $this->_criteria['sql'] = $sql;
    }
    private function _query()
    {
        $dbName = $this->getDbName();
        Db::db()->$dbName->prepare($this->_criteria['sql']);
        Db::db()->$dbName->bindValues($this->_criteria['values']);
        $this->reset();
        return Db::db()->$dbName->fetch();
    }

    private function _queryAll()
    {
        $dbName = $this->getDbName();
        Db::db()->$dbName->prepare($this->_criteria['sql']);
        Db::db()->$dbName->bindValues($this->_criteria['values']);
        $this->reset();
        return Db::db()->$dbName->fetchAll();
        // return $this->populateRecord(Db::db()->$dbName->fetchAll(\PDO::FETCH_CLASS, get_class($this)));
    }

    /**
     * 暂时废弃
     */
    private function populateRecord($data)
    {
        if($this->_criteria['all'])
        {
            $this->reset();
            $record = array();
            if(is_array($data) && !empty($data))
            {
                $class=get_class($this);
                foreach($data as $col)
                {
                    $class=new $class($col);
                    $record[] = $class;
                }
            }
            return $record;        
        }
        else
        {
            debug($data);
            die;
            $class=get_class($this);
            $model=new $class();
            return $model;
        }
        $this->reset();
    }

    private function exec()
    {
        // debug($this->_criteria);

        $dbName = $this->getDbName();
        Db::db()->$dbName->prepare($this->_criteria['sql']);
        Db::db()->$dbName->bindValues($this->_criteria['values']);
        $this->reset();
        return Db::db()->$dbName->execute();
    }

    public function save($data)
    {
        if(empty($data) && !is_array($data))
        {
            return false;
        }

        $this->_criteria['statement'] = 'insert';
        $this->_criteria['field'] = array();

        foreach($data as $key=>$value)
        {
            $this->_criteria['field'][] = $key; 
            $this->_criteria['values'][]=array(':'.$key,$value);
        }

        $this->build();
        return $this->exec();   
    }

    public function getLastId()
    {
        $dbName = $this->getDbName();
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

    public function whereSql($sql,$parse=null)
    {
        if(!is_null($parse) && is_string($sql)) {
            if(!is_array($parse)) {
                $parse = func_get_args();
                array_shift($parse);
            }
            $dbName = $this->getDbName();
            $parse = array_map(array(Db::db()->$dbName,'quoteValue'),$parse);
            $condition =   vsprintf($sql,$parse);
            $this->_criteria['condition'][]=$condition;
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
    public function where()
    {
        $num_args = func_num_args();
        $args = func_get_args();

        // if($num_args == 1)
        // {
        //     $this->_criteria['condition'][]=$args[0];
        // }

        if($num_args == 2)
        {
            $this->_criteria['condition'][]=$args;
        }
        else if($num_args == 3)
        {
            $this->_criteria['condition'][]=$args;
        }
        // debug($this);
        // die;

        return $this;
    }

    public function filter()
    {
        $num_args = func_num_args();
        $args = func_get_args();

        if($num_args == 1)
        {
            if(isset($_GET[$args[0]]) && !empty($_GET[$args[0]]))
            {
                $this->where($args[0],$_GET[$args[0]]);
            }
        }
        else if($num_args == 2)
        {
            $this->where($args[0],$args[1]);
        }
        else if($num_args == 3)
        {
            $this->where($args[0],$args[1],$args[2]);
        }
    }

    public function groupBy($group)
    {
        $this->_criteria['group'] = $group;
        return $this;
    }

    public function orderBy($order)
    {
        $this->_criteria['order'] = $order;
        return $this;
    }

    public function having($having)
    {
        $this->_criteria['having'] = $having;
        return $this;
    }

    public function page($page, $pageSize=10)
    {
        return $this->limit(($page-1)*$pageSize, $pageSize);
    }

    public function limit($offset, $row_count)
    {
        $this->_criteria['limit'] = "$offset, $row_count";
        return $this;
    }

    public function field($field)
    {
        $this->_criteria['field'] = $field;
        return $this;
    }

    public function join($table, $condition)
    {
        $this->_criteria['join'][] = " UNION JOIN $table ON $condition";
        return $this;
    }

    public function leftJoin($table, $condition)
    {
        $this->_criteria['join'][] = " LEFT JOIN $table ON $condition";
        return $this;
    }
    public function rightJoin($table, $condition)
    {
        $this->_criteria['join'][] = " RIGHT JOIN $table ON $condition";
        return $this;
    }

    public function reset()
    {
        $this->_criteria = array(
            'field' => '*',
            'condition' => array(),
            'values' => array(),
        );
    }

    public function getColumns($tableName='')
    {
        if($tableName == '')
            $tableName = $this->getTabelName();

        $this->_criteria['sql'] = 'SHOW FULL COLUMNS FROM `'.$tableName.'`';

        $dbName = $this->getDbName();
        Db::db()->$dbName->prepare($this->_criteria['sql']);
        $column = Db::db()->$dbName->fetchAll();
        $this->reset();

        unset($value);
        foreach($column as &$value)
        {
            $value['dataType'] = $this->_extractType($value['Type']);
        }

        return $column;
    }

    private function _extractType($dbType)
    {
        if(stripos($dbType,'int')!==false && stripos($dbType,'unsigned int')===false)
            $type='integer';
        elseif(stripos($dbType,'bool')!==false)
            $type='boolean';
        elseif(preg_match('/(real|floa|doub)/i',$dbType))
            $type='double';
        else
            $type='string';

        return $type;
    }

    public function validate($data, $scene='default')
    {
        $rule = $this->rules();
        if(isset($rule[$scene]))
        {
            $this->validate = new Validate($rule[$scene]);
            return $this->validate->validate($data);
        }

    }

    public function getValidate($scene='default')
    {
        $rule = $this->rules();
        if(isset($this->validate) && $this->validate instanceof Validate)
        {
            $this->validate->getValidate();
        }
        else
        {
            if(isset($rule[$scene]))
            {
                $this->validate = new Validate($rule[$scene]);
                $this->validate->getValidate();
            }
        }

    }

    public function getError()
    {
        if($this->validate instanceof Validate)
        {
            $this->validate->getError();
        }
    }

    public function rules()
    {
        return array();
    }
    
    public function alias()
    {
        return array();
    }

    public function getCriteria()
    {
        return $this->_criteria;
    }
}
