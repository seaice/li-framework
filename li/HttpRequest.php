<?php
namespace Li;

class HttpRequest
{
    private static $__request;

    private $_requestUri;
    private $_pathInfo;
    private $_scriptUrl;

    static function instance()
    {
        if(!(self::$__request instanceof self))
        {
            self::$__request = new self();
        }
        
        return self::$__request;
    }
    
    /**
     * @param $config config file path
     */

    function __construct()
    {
       
    }


    public function getPathInfo()
    {
        if($this->_pathInfo === null)
        {
            $pathInfo = $this->getRequestUri();

            if(($pos = strpos($pathInfo,'?')) !== false)
                $pathInfo = substr($pathInfo, 0, $pos);
            
            $scriptUrl = $this->getScriptUrl();

            if(strpos($pathInfo,$scriptUrl) === 0)
                $pathInfo = substr($pathInfo, strlen($scriptUrl));
            elseif(strpos($_SERVER['PHP_SELF'], $scriptUrl) === 0)
                $pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
            else
                throw new Exception('CHttpRequest is unable to determine the path info of the request.');

            $this->_pathInfo = trim($pathInfo,'/');
        }
        return $this->_pathInfo;
    }

    function getRequestUri()
    {
        if($this->_requestUri===null)
        {
            if(isset($_SERVER['HTTP_X_REWRITE_URL'])) // IIS
                $this->_requestUri=$_SERVER['HTTP_X_REWRITE_URL'];
            elseif(isset($_SERVER['REQUEST_URI']))
            {
                $this->_requestUri=$_SERVER['REQUEST_URI'];
                if(!empty($_SERVER['HTTP_HOST']))
                {
                    if(strpos($this->_requestUri,$_SERVER['HTTP_HOST'])!==false)
                        $this->_requestUri=preg_replace('/^\w+:\/\/[^\/]+/','',$this->_requestUri);
                }
                else
                    $this->_requestUri=preg_replace('/^(http|https):\/\/[^\/]+/i','',$this->_requestUri);
            }
            elseif(isset($_SERVER['ORIG_PATH_INFO']))  // IIS 5.0 CGI
            {
                $this->_requestUri=$_SERVER['ORIG_PATH_INFO'];
                if(!empty($_SERVER['QUERY_STRING']))
                    $this->_requestUri.='?'.$_SERVER['QUERY_STRING'];
            }
            else
            {
               throw new Exception('CHttpRequest is unable to determine the request URI.');
            }
        }

        return $this->_requestUri;
    }

    public function getScriptUrl()
    {
        if($this->_scriptUrl===null)
        {
            $scriptName=basename($_SERVER['SCRIPT_FILENAME']);
            if(basename($_SERVER['SCRIPT_NAME'])===$scriptName)
                $this->_scriptUrl=$_SERVER['SCRIPT_NAME'];
            elseif(basename($_SERVER['PHP_SELF'])===$scriptName)
                $this->_scriptUrl=$_SERVER['PHP_SELF'];
            elseif(isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME'])===$scriptName)
                $this->_scriptUrl=$_SERVER['ORIG_SCRIPT_NAME'];
            elseif(($pos=strpos($_SERVER['PHP_SELF'],'/'.$scriptName))!==false)
                $this->_scriptUrl=substr($_SERVER['SCRIPT_NAME'],0,$pos).'/'.$scriptName;
            elseif(isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'],$_SERVER['DOCUMENT_ROOT'])===0)
                $this->_scriptUrl=str_replace('\\','/',str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']));
            else
                throw new Exception('CHttpRequest is unable to determine the entry script URL.');
        }
        return $this->_scriptUrl;
    }

}
