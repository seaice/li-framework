<?php
namespace Li;

class Mysql
{
    public $_link;
    public $charset='utf8';
    public $_statement;
    private $_sql;
    
    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    public function __construct($config)
    {
        if(!empty($config))
        {
            $this->host = $config['host'];
            $this->port = $config['port'];
            $this->dbname = $config['name'];
            $this->user = $config['user'];
            $this->password = $config['pass'];
            if(isset($config['charset']))
                $this->charset = $config['charset'];

            $this->connect();
        }
    }

    public function connect()
    {
        $dns = 'mysql:dbname=' . $this->dbname . ";host=" . $this->host . ";port=" . $this->port;
        try {
            $this->_link = new \PDO($dns, $this->user, $this->password);
            $this->exec('SET NAMES '.$this->_link->quote($this->charset));
        }
        catch(Exception $e)
        {
            die('database connect error');
        }
    }

    public function error()
    {

    }

    /**
     * 获得该引擎对应的CommandBuilder
     */
    public function getCommandBuilder()
    {
        return new MysqlCommandBuilder($this);
    }

    /**
     * 创建command
     */
    public function createCommand($query)
    {
        return new Command($this, $query);
    }

    public function getPdoInstance()
    {
        return $this->_link;
    }

    /**
     * 为sql语句过滤变量值
     */

    public function quoteValue($str)
    {
        if(is_int($str) || is_float($str))
            return $str;

        if(($value=$this->_link->quote($str))!==false)
            return $value;
        else  // the driver doesn't support quote (e.g. oci)
            return "'" . addcslashes(str_replace("'", "''", $str), "\000\n\r\\\032") . "'";
    }

    /**
     * Determines the PDO type for the specified PHP type.
     * @param string $type The PHP type (obtained by gettype() call).
     * @return integer the corresponding PDO type
     */
    public function getPdoType($type)
    {
        static $map=array
        (
            'boolean'=>\PDO::PARAM_BOOL,
            'integer'=>\PDO::PARAM_INT,
            'string'=>\PDO::PARAM_STR,
            'resource'=>\PDO::PARAM_LOB,
            'NULL'=>\PDO::PARAM_NULL,
        );
        return isset($map[$type]) ? $map[$type] : \PDO::PARAM_STR;
    }

    public function getLastInsertID($sequenceName='')
    {
        return $this->_link->lastInsertId($sequenceName);
    }

    public function prepare($sql)
    {
        $this->_sql = $sql;
        $this->_statement=$this->_link->prepare($sql);
    }

    public function bindValues($values)
    {
        if(is_array($values) && !empty($values))
        {
            $i=0;
            foreach($values as $v)
            {
                // $this->_statement->bindValue(':v'.$i, $v[1], $this->getPdoType($v[1]));
                $this->_statement->bindValue($v[0], $v[1], $this->getPdoType($v[1]));
                $i++;
            }
        }
    }

    public function fetchAll()
    {
        if(!$this->_statement->execute())
        {
            $error = $this->_statement->errorInfo();
            throw new  Exception('SQLSTATE['.$error[0] . ']: ' . $error[2]);
        }

        return $this->_statement->fetchAll();
    }

    public function fetch()
    {
        if(!$this->_statement->execute())
        {
            $error = $this->_statement->errorInfo();
            throw new  Exception('SQLSTATE['.$error[0] . ']: ' . $error[2]);
        }

        return $this->_statement->fetch();
    }

    public function execute()
    {
        if(!$this->_statement->execute())
        {
            $error = $this->_statement->errorInfo();
            throw new  Exception('SQLSTATE['.$error[0] . ']: ' . $error[2]);
        }

        $count = $this->_statement->rowCount();
        return $count;
    }

    /**
     * 构建查询的sql
     */
    public function buildQuery($query)
    {
        $sql=!empty($query['distinct']) ? 'SELECT DISTINCT' : 'SELECT';
        $sql.=' '.(!empty($query['select']) ? $query['select'] : '*');

        if(!empty($query['from']))
            $sql.="\nFROM ".$query['from'];
        else
            throw new CDbException(Yii::t('yii','The DB query must contain the "from" portion.'));

        if(!empty($query['join']))
            $sql.="\n".(is_array($query['join']) ? implode("\n",$query['join']) : $query['join']);

        if(!empty($query['where']))
            $sql.="\nWHERE ".$query['where'];

        if(!empty($query['group']))
            $sql.="\nGROUP BY ".$query['group'];

        if(!empty($query['having']))
            $sql.="\nHAVING ".$query['having'];

        if(!empty($query['union']))
            $sql.="\nUNION (\n".(is_array($query['union']) ? implode("\n) UNION (\n",$query['union']) : $query['union']) . ')';

        if(!empty($query['order']))
            $sql.="\nORDER BY ".$query['order'];

        $limit=isset($query['limit']) ? (int)$query['limit'] : -1;
        $offset=isset($query['offset']) ? (int)$query['offset'] : -1;
        if($limit>=0 || $offset>0)
            $sql=$this->_connection->getCommandBuilder()->applyLimit($sql,$limit,$offset);

        return $sql;
    }

    public function exec($sql)
    {
        return $this->_link->exec($sql);
    }
    public function query($sql)
    {
        $rs = $this->_link->query($sql);
        if($rs == false)
        {
            $error = $this->_link->errorInfo();
            throw new  Exception('SQLSTATE['.$error[0] . ']: ' . $error[2]);
        }

        return $rs->fetchAll();
    }

}
