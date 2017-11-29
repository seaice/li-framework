<?php
namespace Li;

class Route
{
    private static $__route;

    private $_rules=array();
    private $_scriptUrl;
    private $_requestUri;
    private $_pathInfoOri;
    private $_pathInfo;
    private $_hostInfo;
    private $_baseUrl;
    private $_urlFormat='get';

    public $caseSensitive = true;

    function __construct()
    {
        if(isset(App::app()->config['route']['rules']))
        {
            $this->_rules = App::app()->config['route']['rules'];
        }
    }

    public static function instance()
    {
        if(!(self::$__route instanceof self))
        {
            self::$__route = new self();
        }
        
        return self::$__route;
    }

    public function init()
    {
        if(isset(App::app()->config['route']['urlFormat'])) {
            $this->_urlFormat = App::app()->config['route']['urlFormat'];
        }
        $this->_pathInfo = $this->parseUrl();
        return $this->_pathInfo;
    }
   
    public function parseUrl()
    {
        if($this->_pathInfo === null)
        {
            if($this->_urlFormat == 'path') {
                $this->_pathInfoOri = $this->getPathInfo();
            } else {
                $this->_pathInfoOri = '/';
                if(isset($_GET['r'])) {
                    $this->_pathInfoOri = $_GET['r'];
                }
            }
            $this->_pathInfo = $this->parseRegular($this->_pathInfoOri);
        }

        return $this->_pathInfo;
    }


    /**
     * 支持正则
     * @param  [type] $pathInfo [description]
     * @return [type]           [description]
     */
    function parseRegular($pathInfo)
    {
        if($this->caseSensitive === null || $this->caseSensitive)
            $case = '';
        else
            $case = 'i';

        foreach($this->_rules as $pattern => $v)
        {
            preg_match('@'.$pattern.'@'.$case, $pathInfo, $match);
            if(!empty($match))
            {
                $len = count($match);
                for($i=1;$i<$len;$i++) {
                    $v = str_replace('$'.$i, $match[$i], $v);
                }
                $pathInfo = $v;
                break;
            }
        }

        return $pathInfo;
    }

    public function parseActionParams($pathInfo)
    {
        if($pathInfo == '')
            return;
        $segs=explode('/',$pathInfo.'/');
        $n=count($segs);
        for($i=0;$i<$n-1;$i+=2)
        {
            $key=$segs[$i];
            if($key==='') continue;
            $value=$segs[$i+1];
            if(($pos=strpos($key,'['))!==false && ($m=preg_match_all('/\[(.*?)\]/',$key,$matches))>0)
            {
                $name=substr($key,0,$pos);
                for($j=$m-1;$j>=0;--$j)
                {
                    if($matches[1][$j]==='')
                        $value=array($value);
                    else
                        $value=array($matches[1][$j]=>$value);
                }
                if(isset($_GET[$name]) && is_array($_GET[$name]))
                    $value=CMap::mergeArray($_GET[$name],$value);
                $_REQUEST[$name]=$_GET[$name]=$value;
            }
            else
                $_REQUEST[$key]=$_GET[$key]=$value;
        }
    }


    public function getPathInfo()
    {
        if($this->_pathInfo === null)
        {
            $pathInfo = $this->getRequestUri();

            if(($pos = strpos($pathInfo,'?')) !== false)
                $pathInfo = substr($pathInfo, 0, $pos);
            
            $scriptUrl = $this->getScriptUrl();
            if(strpos($pathInfo,$scriptUrl) === 0) {
                $pathInfo = substr($pathInfo, strlen($scriptUrl));
            }
            elseif(strpos($_SERVER['PHP_SELF'], $scriptUrl) === 0) {
                $pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
            }
            else {
                throw new Exception('CHttpRequest is unable to determine the path info of the request.');
            }

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
        if($this->_scriptUrl === null)
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

    public function getIsSecureConnection()
    {
        return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'],'on')===0 || $_SERVER['HTTPS']==1)
            || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'],'https')===0;
    }

    public function getHostInfo()
    {
        if($this->_hostInfo === null) {
            if($secure=$this->getIsSecureConnection())
                    $http='https';
                else
                    $http='http';

            if(isset($_SERVER['HTTP_HOST'])) {
                $this->_hostInfo=$http . '://' . $_SERVER['HTTP_HOST'];
            } else {
                $this->_hostInfo=$http . '://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
            }
        }

        return $this->_hostInfo;
    }

    public function getBaseUrl($absolute=false)
    {
        if($this->_baseUrl === null) {
            $this->_baseUrl = rtrim(dirname($this->getScriptUrl()),'\\/');
        }

        return $absolute ? $this->getHostInfo() . $this->_baseUrl : $this->_baseUrl;
    }

    public function createUrl($route, $absolute=false)
    {
        $route = trim($route, '/');

        if(empty($route)) {
            return $this->getBaseUrl($absolute);
        }

        if($this->_urlFormat == 'path') {
            return $this->getBaseUrl($absolute) . '/' . $route;
        } else {
            if($absolute) {
                return $this->getHostInfo() . $this->getScriptUrl() . '?r=' . $route;
            } else {
                return $this->getScriptUrl() . '?r=' . $route;
            }
        }
    }
}
