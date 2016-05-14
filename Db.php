<?php
namespace Li;

/**
 * db factor
 */
class Db
{
    public static $_instance;
    private static $_db = array();

    public static function db()
    {
        if(!(self::$_instance instanceof self))
        {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    function __get($dbName)
    {
        if(isset(App::app()->config['database'][$dbName]))
        {
            $driver = 'Li\\'.ucfirst(App::app()->config['database'][$dbName]['driver']);

            if(!(isset($this->$dbName) && $this->$dbName instanceof $driver))
            {
                $this->$dbName =  new $driver(App::app()->config['database'][$dbName]);
            }

            return $this->$dbName;
        }
        else
        {
            throw new Exception("config no db ".$dbName);
        }
    }

    /**
     * init db instance by config
     * @param $key string the db index
     * @param $config mixed the config
     */
    public function initByConfig($key, $config)
    {
        if(empty($config))
        {
            throw new Exception('db config is empty');
        }
        $driver = 'Li\\'.ucfirst($config['driver']);

        if(!(isset($this->$key) && $this->$key instanceof $driver))
        {
            $this->$key =  new $driver($config);
        }      

    }
}
