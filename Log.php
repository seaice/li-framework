<?php
namespace Li;

class Log
{
    private static $_log;

    private $_name = 'default';
    private $_config = [];

    private $_defaultConf = array(
        'format'  => 'YmdH', //按天分文件
        'level' => [
            'debug',
            'warning',
            'trace',
            'fatal',
        ],
    );
    
    public static function log($name='default')
    {
        if (!(self::$_log instanceof self)) {
            self::$_log = new self($name);
        }

        $trace = debug_backtrace();
        self::$_log->file = $trace[0]['file'];
        self::$_log->line = $trace[0]['line'];


        self::$_log->_init($name);

        return self::$_log;
    }


    private function _init($name=null)
    {
        if(empty($name)) {
            $name = 'default';
        }

        $this->_name = $name;

        if(isset(App::app()->config['log'][$name])) {
            $this->_config[$name] = array_merge($this->_defaultConf, App::app()->config['log'][$name]);
        } else {
            $this->_config[$name] = $this->_defaultConf;
        }
    }

    public function __construct($name=null)
    {
        $this->_config = App::app()->config['log'];
        $this->_init($name);
    }

    /**
     * @return string the log file name
     */
    private function _getFileName($level)
    {
        $filename = 'log';
        if($level == 'warning' || $level == 'fatal') {
            $filename .= '.wf';
        }

        return $this->_name . '.' . $filename.'.'.date($this->_config[$this->_name]['format'], NOW);
    }

    /**
     * 如果框架debug为true，返回true
     * 如果框架debug为false，根据日志配置的debug返回
     * if li global config debug is false,
     * @return boolean if write return true, otherwise false
     */
    private function _checkLevel($level)
    {
        if(in_array($level, $this->_config[$this->_name]['level'])) {
            return true;
        }

        return false;
    }

    private function _write($message, $level)
    {
        if (!$this->_checkLevel($level)) {
            return false;
        }

        $fileName = $this->_getFileName($level);
        $filePath = PATH_APP.'runtime'.DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR;

        if($message instanceof \Exception) {
            $log = date('Y-m-d H:i:s', NOW).' '.'['.$level.'] '.$message->getMessage(). ' [' . $message->getFile().':'.$message->getLine() . "]\r\n";
        } else {
            $log = date('Y-m-d H:i:s', NOW).' '.'['.$level.'] '.$message. ' [' . $this->file.':'.$this->line . "]\r\n";
        }

        if(!is_dir($filePath)) {
            mkdir($filePath);
        }

        if($message instanceof \Exception) {
            $log = date('Y-m-d H:i:s', NOW).' '.'['.$level.'] '.$message->getMessage(). ' [' . $message->getFile().':'.$message->getLine() . "]\r\n";
        } else {
            $log = date('Y-m-d H:i:s', NOW).' '.'['.$level.'] '.$message. ' [' . $this->file.':'.$this->line . "]\r\n";
        }

        if(App::app()->config['debug']) {
            echo $log . PHP_EOL;
        } else {
            error_log($log, 3, $filePath.$fileName);
        }

        $this->_configIndex=null;
    }

    /**
     * trace日志内容
     */
    public function debug($message)
    {
        $this->_write($message, __FUNCTION__);
    }

    /**
     * trace日志内容
     */
    public function trace($message)
    {
        $this->_write($message, __FUNCTION__);
    }

    /**
     * error日志内容
     */
    public function warning($message)
    {
        $this->_write($message, __FUNCTION__);
    }

    /**
     * fatal日志内容
     */
    public function fatal($message)
    {
        $this->_write($message, __FUNCTION__);
    }
}
