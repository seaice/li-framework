<?php
namespace Li;

class Route
{
    private static $__route;

    private $_rules=array();
    private $_pathInfo;

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

    public function init($request)
    {
        $this->_pathInfo = $this->parseUrl($request);
        return $this->_pathInfo;
    }
   
    public function parseUrl($request)
    {
        if($this->_pathInfo === null)
        {
            $pathInfo = $request->getPathInfo();

            /**
             * 支持正则,以后完成，暂时不支持
             */
            $this->_pathInfo = $this->route($pathInfo);

        }

        return $this->_pathInfo;
    }


    /**
     * 支持正则
     * @param  [type] $pathInfo [description]
     * @return [type]           [description]
     */
    function route($pathInfo)
    {
        if($this->caseSensitive === null || $this->caseSensitive)
            $case = '';
        else
            $case = 'i';

        foreach($this->_rules as $pattern => $v)
        {
            preg_match('@'.$pattern.'@i', $pathInfo, $match);
            if(!empty($match))
            {
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
}
