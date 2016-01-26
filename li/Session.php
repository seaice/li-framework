<?php
namespace Li;

class session {
    private static $_session;

    public static function session()
    {
        if(!(self::$_session instanceof self))
        {
            self::$_session = new self();
        }

        return self::$_session;
    }

    public static function init()
    {
        if(isset(App::app()->config['session']))
        {
            if(isset(App::app()->config['session']['driver'])
                && App::app()->config['session']['driver'] = 'mysql')
            {
                $driver = 'Session'.ucfirst(App::app()->config['session']['driver']);
                $handler = new SessionMysql(App::app()->config['session']['config']);
                session_set_save_handler($handler, true);
            }
        }
        session_start();
    }
}

class SessionMysql implements \SessionHandlerInterface 
{
    private $_config;
    private $_gc_maxlifetime;

    private function getTimeout()
    {
        return (int)ini_get('session.gc_maxlifetime');
    }

    public function __construct($config)
    {
        $this->_config = $config;
        $this->_gc_maxlifetime = $this->getTimeout();
    }

    public function close ()
    {
        return true;
    }

    public function destroy ($session_id)
    {
        $dbName = $this->_config['dbName'];
        $db = Db::db()->$dbName;
        $sql = 'DELETE FROM `'.$this->_config['tableName'].'` WHERE `id`=:0';

        Db::db()->$dbName->prepare($sql);
        Db::db()->$dbName->bindValues(array(
            array(':0', $session_id),
        ));

        Db::db()->$dbName->execute();

        return true;
    }

    public function gc ($maxlifetime)
    {
        $dbName = $this->_config['dbName'];
        $db = Db::db()->$dbName;
        $sql = 'DELETE FROM `'.$this->_config['tableName'].'` WHERE `expire`<:0';

        Db::db()->$dbName->prepare($sql);
        Db::db()->$dbName->bindValues(array(
            array(':0', NOW),
        ));

        Db::db()->$dbName->execute();

        return true;
    }
    
    public function open ($savePath, $sessionName)
    {
        return true;
    }

    public function read ($session_id)
    {
        $dbName = $this->_config['dbName'];
        $db = Db::db()->$dbName;
        $sql = 'SELECT `data` FROM `'.$this->_config['tableName'].'` WHERE `id`=:0 AND `expire`>:1 limit 1';

        Db::db()->$dbName->prepare($sql);
        Db::db()->$dbName->bindValues(array(
            array(':0', $session_id),
            array(':1', NOW),
        ));

        $data = Db::db()->$dbName->fetch();

        if($data===false)
            return '';

        return $data['data'];
    }

    public function write ($session_id,$session_data)
    {
        $dbName = $this->_config['dbName'];
        $db = Db::db()->$dbName;
        $sql = 'REPLACE INTO `'.$this->_config['tableName'].'` (`id`, `data`, `expire`) VALUES (:0,:1,:2)';

        Db::db()->$dbName->prepare($sql);
        Db::db()->$dbName->bindValues(array(
            array(':0', $session_id),
            array(':1', $session_data),
            array(':2', NOW+$this->_gc_maxlifetime),
        ));

        Db::db()->$dbName->execute();

        return true;
    }

}
