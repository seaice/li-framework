<?php
namespace Li;

class Redis
{
    public $_redis;
    public $config = [
        'ip'        => '127.0.0.1',
        'port'      => 6379,
        'timeout'   => 1,
        'retry'     => 2,
    ];

    public function __construct($config)
    {
        try {
            if(!empty($config))
            {
                $this->config = array_merge($this->config, $config);
            }

            $this->_redis = new \Redis();

            if($this->connect() === false) {
                $this->_redis->getLastError();
            }

        } catch (\Exception $e) {
            Log::log()->warning($e);
        }
    }

    protected function connect()
    {
        if(isset($this->config['pconnect']) && $this->config['pconnect']) {
            return $this->_redis->pconnect($this->config['ip'], $this->config['port']);
        } else {
            return $this->_redis->connect($this->config['ip'], $this->config['port']);
        }
    }

    public function __call($name, $arguments) 
    {
        return $this->_redis->$name(...$arguments);
    }
}
