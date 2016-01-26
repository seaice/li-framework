<?php
namespace Li;

class Log {
    private static $_log;
    private $_configIndex=null;

    private $_config=array(
        'multi'=>false,
    );

    private $_defaultConfig=array(
        'name'=>'Ymd',
        'debug'=>true,
    );
    
    public function __get($name)
    {
        $this->_configIndex=$name;
        return $this;
    }

    public static function log()
    {
        if(!(self::$_log instanceof self))
        {
            self::$_log = new self();
        }

        return self::$_log;
    }

    public function __construct()
    {
        $this->_config = array_merge($this->_config,App::app()->config['log']);
    }

    /**
     * 
     * @return string the log file name
     */
    private function _getFileName()
    {
        // 单文件
        if($this->_config['multi'] == false)
        {
            return date($this->_config['config']['name'], NOW).'.log';
        }
        // 多文件
        else
        {
            return $this->_configIndex.'_'.date($this->_config['config'][$this->_configIndex]['name'], NOW).'.log';
        }
    }

    private function _checkConfig()
    {
        if($this->_config['multi'] == true)
        {
            if($this->_configIndex === null)
                $this->_configIndex='default';
            if(isset($this->_config['config'][$this->_configIndex]))
            {
                $this->_config['config'][$this->_configIndex] = array_merge($this->_defaultConfig,$this->_config['config'][$this->_configIndex]);
            }
            else
            {
                if(App::app()->config['debug'])
                    throw new Exception("the log config ".$this->_configIndex.' is not defined');

                $this->_config['config'][$this->_configIndex] = $this->_defaultConfig;
            }
        }
        else
        {
            $this->_config['config'] = array_merge($this->_defaultConfig,$this->_config['config']);
        }
    }
    /**
     * 如果框架debug为true，返回true
     * 如果框架debug为false，根据日志配置的debug返回
     * if li global config debug is false, 
     * @return boolean if write return true, otherwise false 
     */
    private function _checkLevel($level)
    {
         // 框架debug开启
        if(isset(App::app()->config['debug'])
            && App::app()->config['debug'] === true)
            return true;

        //单日志
        if($this->_config['multi'] == false)
        {
            if(isset($this->_config['debug'])
                && $this->_config['debug'] === true)
                return true;
            else
            {
                if($level == 'debug')
                    return false;
                else
                    return true;
            }
        }
        //多文件
        else
        {
            if(isset($this->_config['config'][$this->_configIndex]['debug'])
                && $this->_config['config'][$this->_configIndex]['debug'] === true)
                return true;
            else
            {
                if($level == 'debug')
                    return false;
                else
                    return true;
            }
        }
    }

    private function _write($message,$level)
    {
        $this->_checkConfig();

        if(!$this->_checkLevel($level))
            return false;

        $fileName = $this->_getFileName();
        $filePath = APP_PATH.'runtime'.DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR;
        $log = date('Y-m-d H:i:s', NOW).' '.'['.$level.'] '.$message."\r\n";

        error_log($log, 3, $filePath.$fileName);

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
    public function error($message)
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
