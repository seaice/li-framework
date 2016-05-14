<?php
namespace Li;
/**
 * Li framework application
 *
 * it's what you want 
 * @author seaice <haibing1458@163.com>
 * @link http://www.sunhaibing.com
 * @copyright 2015-2016 seaice (http://www.makeclean.net/php/li)
 * @license BSD license
 */
class App
{
    private static $_app;
    public $config = array(
        'debug'=>true,
        'controllerPath' => 'controller',
        'viewPath' => 'view',
        'defaultController' => 'site',
        'defaultAction' => 'index',
        'import'=>array(),
        'route'=>array(),
    );

    public $route;
    public $controller;
    private static $_coreClasses = array(
        'Li\Config' => '/Config.php',
        'Li\Route' => '/Route.php',
        'Li\Controller' => '/Controller.php',
        'Li\Model' => '/Model.php',
        'Li\Service' => '/Service.php',
        'Li\HttpRequest' => '/HttpRequest.php',
        'Li\Exception' => '/Exception.php',
        'Li\Db' => '/Db.php',
        'Li\Log' => '/Log.php',
        'Li\File' => '/File.php',

        'Li\Mysql' => '/driver/Mysql.php',
        'Li\Iterator' => '/Iterator.php',

        'Li\GridView' => '/GridView.php',
        'Li\Pagination' => '/Pagination.php',
        'Li\Validate' => '/Validate.php',

        'Li\Log' => '/Log.php',
        'Li\Session' => '/Session.php',
        
        'Li\Redis' => '/Redis.php',
        
        'Li\Captcha' => '/Captcha.php',
        'Li\Upload' => '/Upload.php',
    );

    function __construct()
    {
        require(PATH_APP.'core/config/'.ENV.'/config.php');

        if(is_array($config))
        {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * init app
     * 
     * @return App the app instance
     */
    static function init()
    {
        self::$_app = new self();
        return self::$_app;
    }

    private function _initRegister()
    {
        spl_autoload_register(array('Li\App', 'autoload'));
        register_shutdown_function(array('Li\App', 'shutdown'));
        // 设置一个用户定义的异常处理函数。
        set_exception_handler(array('Li\App', 'exception_handler'));
        set_error_handler(array('Li\App', 'error_handler'));


        Session::init();
    }

    /**
     * get app instance 
     * @return App the app instance
     */
    static function app()
    {
        return self::$_app;
    }

    /**
     * start the app
     */
    public function run()
    {
        $this->_initRegister();


        $this->route = Route::instance()->init();
        $this->controller = Controller::create($this->route);
    }

    /**
     * get controller path
     * @return string the path of controller
     */
    public function getControllerPath()
    {
        return PATH_APP . 'core\\' . $this->config['controllerPath'];
    }

    public function getViewPath()
    {
        return BASE_PATH. 'view\\' . $this->config['viewPath'];
    }

    public static function autoload($className)
    {
        if(isset(self::$_coreClasses[$className]))
        {
            require_once PATH_LI . self::$_coreClasses[$className];
        }
        else
        {
            // 无命名空间
            // include class file relying on include_path
            if(strpos($className,'\\')===false)  // class without namespace
            {
                if(isset(App::app()->config['import']) 
                    && is_array(App::app()->config['import']))
                {
                    foreach(\Li\App::app()->config['import'] as $path)
                    {

                        $classFile = PATH_APP . 'core' . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $className.'.php';

                        if(is_file($classFile))
                        {
                            include($classFile);
                            break;
                        }
                    }
                }
            }
            else
            {
                // 命名空间
                
            }
            return class_exists($className,false) || interface_exists($className,false);
        }
        return true;
    }

    /**
     * catch fatal error
     */
    public static function shutdown()
    {
        $error = error_get_last();

        if($error !== null)
        {
            if(App::app()->config['debug'] == false)
            {
                Log::log()->fatal('Fatal Error:  ' .$error['message'] . ' in '. $error['file']. ' on '.$error['line']);
            }
        }
    }

    public static function exception_handler($exception)
    {
        if(App::app()->config['debug'])
        {
            echo "Exception: " . $exception->getMessage(). ' in ' . $exception->getFile() . ' on ' . $exception->getLine() . "<br />";
            echo 'trace    :' . '<br />';
            $trace = $exception->getTrace();
            foreach($trace as $v)
            {
                echo '<b>' . $v['file'] . '</b>  in  <b>'. $v['function'] .'</b>  on  <b>' . $v['line'] . "</b><br />";
            }
        }
        else
        {
            Log::log()->error("Exception: " . $exception->getMessage(). ' in ' . $exception->getFile() . ' on ' . $exception->getLine());
        }
    }

    public static function error_handler($errno, $errstr, $errfile, $errline, $errContext)
    {
        $errtype = array(
            E_ERROR              => 'E_ERROR',
            E_WARNING            => 'E_WARNING',
            E_PARSE              => 'E_PARSE',
            E_NOTICE             => 'E_NOTICE',
            E_CORE_ERROR         => 'E_CORE_ERROR',
            E_CORE_WARNING       => 'E_CORE_WARNING',
            E_COMPILE_ERROR      => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING    => 'E_COMPILE_WARNING',
            E_USER_ERROR         => 'E_USER_ERROR',
            E_USER_WARNING       => 'E_USER_WARNING',
            E_USER_NOTICE        => 'E_USER_NOTICE',
            E_STRICT             => 'E_STRICT',
            E_RECOVERABLE_ERROR  => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED         => 'E_DEPRECATED',
            E_USER_DEPRECATED    => 'E_USER_DEPRECATED',
            E_ALL                => 'E_ALL'
        );
        if(defined('E_STRICT'))
            $errtype[E_STRICT] = 'runtime notice';

        if($errtype[$errno] != 'E_DEPRECATED') {
            echo "[" . date('Y-m-d H:i:s') . "] PHP {$errtype[$errno]}:  $errstr in $errfile on $errline<br/>";
        }
    }

    /**
     * @return string the version of LiPHP framework
     */   
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * build url with controller action and url param
     *
     * @param $controller string the controller name
     * @param $action string the action name
     * @param $param array the url param
     *
     * @return string the url
     */
    public function url($controller,$action,$param)
    {
        if(empty($param))
        {
            return PATH_APP_REL.'/'.$controller.'/'.$action;
        }
        else
        {
            return PATH_APP_REL.'/'.$controller.'/'.$action.'?'.http_build_query($param);
        }
    }
    public static function setLocale($locale)
    {

    }

    public static function t($id, $parameters = [], $locale = null)
    {
        return Locale::locale()->trans($id, $parameters, $locale);
    }
}

