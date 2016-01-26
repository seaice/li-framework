<?php
namespace Li;

class Redis
{
    public static $_instance;

    static public function redis()
    {
        if(!(self::$_instance instanceof self))
        {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    function __get($cacheName)
    {
        if(isset(App::app()->config['redis'][$cacheName]))
        {
            if(!(isset($this->$cacheName) && $this->$cacheName instanceof \Redis))
            {
                $this->$cacheName =  new \Redis();
                if(isset(App::app()->config['redis'][$cacheName]['pconnect'])
                    && App::app()->config['redis'][$cacheName]['pconnect'] === true
                )
                {
                    $ret = $this->$cacheName->pconnect(App::app()->config['redis'][$cacheName]['ip'], App::app()->config['redis'][$cacheName]['port']);
                }
                else
                {
                    $ret = $this->$cacheName->connect(App::app()->config['redis'][$cacheName]['ip'], App::app()->config['redis'][$cacheName]['port']);
                }
                if($ret === false)
                {
                    throw new Exception("Redis ".App::app()->config['redis'][$cacheName]['ip'].':'.App::app()->config['redis'][$cacheName]['port'].' can not connect');
                }
            }

            return $this->$cacheName;

        }
        else
        {
            throw new Exception("config no redis ".$dbName);
        }
    }

}

