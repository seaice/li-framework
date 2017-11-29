<?php
namespace Li;

/**
 * Li framework application
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
        'timezone'=>'Asia/Shanghai',
    );

    public $namespace; // 当前app的命名空间，必须和app的目录名一致
    public $route;
    public $controller;
    private static $_coreClasses = array(
        // 'Li\Config' => '/Config.php',
        'Li\Route' => '/Route.php',
        'Li\Controller' => '/Controller.php',
        'Li\Model' => '/Model.php',
        'Li\Form' => '/Form.php',
        'Li\Service' => '/Service.php',
        'Li\Expression' => '/Expression.php',
        'Li\HttpRequest' => '/HttpRequest.php',
        'Li\Exception' => '/Exception.php',
        'Li\Db' => '/Db.php',
        'Li\Log' => '/Log.php',
        'Li\File' => '/File.php',

        'Li\Mysql' => '/driver/Mysql.php',
        'Li\Redis' => '/driver/Redis.php',
        'Li\Iterator' => '/Iterator.php',

        'Li\GridView' => '/GridView.php',
        'Li\Pagination' => '/Pagination.php',
        'Li\Validate' => '/Validate.php',
        'Li\DataProvider' => '/DataProvider.php',

        'Li\Log' => '/Log.php',
        'Li\Session' => '/Session.php',
        
        // 'Li\Redis' => '/Redis.php',
        
        'Li\Captcha' => '/Captcha.php',
        'Li\Upload' => '/Upload.php',
    );

    public function __construct()
    {
        require(PATH_APP.'core/config/'.ENV.'/config.php');
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }

        if($this->config['debug'] !== false) {
            error_reporting($this->config['debug']);
        } else {
            error_reporting(0);
        }
    }

    /**
     * init app
     * @return App the app instance
     */
    public static function init()
    {
        self::$_app = new self();
        self::$_app->_initConstant();

        require(PATH_LI . DIRECTORY_SEPARATOR . 'Function.php');
        return self::$_app;
    }

    private function _initRegister()
    {
        spl_autoload_register(array('Li\App', 'autoload'));
        register_shutdown_function(array('Li\App', 'shutdown'));
        // 设置一个用户定义的异常处理函数。
        set_exception_handler(array('Li\App', 'exceptionHandler'));
        // set_error_handler(array('Li\App', 'errorHandler'));


        Session::init();
    }

    /**
     * get app instance
     * @return App the app instance
     */
    public static function app()
    {
        return self::$_app;
    }

    /**
     * start the app
     */
    public function run($namespace)
    {
        $this->namespace = $namespace;
        $this->_initRegister();

        date_default_timezone_set($this->config['timezone']);

        if (false === IS_CLI) {
            $this->route = Route::instance()->init();
            $this->controller = Controller::create($this->route);
        }
    }

    public function _initConstant()
    {
        define('CHAR_TRUE', '1');
        define('CHAR_FALSE', '0');

        define('NOW', isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time());

        define('IS_POST', isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'POST'));
        define('IS_GET', isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'GET'));
        define('IS_PUT', isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'PUT'));
        define('IS_DELETE', isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'DELETE'));
        define('IS_CLI', PHP_SAPI == 'cli');
        define('IS_AJAX', isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest");

        define('PATH_VENDOR', realpath(__DIR__ . "/../../") . '\\'); //基础目录(绝对)
        define('PATH_BASE', PATH_VENDOR . '../'); // 整个代码的根目录
        define('PATH_LI', dirname(__FILE__)); // 框架目录(绝对)
        define('PATH_APP_REL', dirname($_SERVER['SCRIPT_NAME'])); // 当前APP的相对路径
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
        if (isset(self::$_coreClasses[$className])) {
            require_once PATH_LI . self::$_coreClasses[$className];
        } else {
            // 无命名空间
            if (strpos($className, '\\')===false) {  // class without namespace
                if (isset(App::app()->config['import'])
                    && is_array(App::app()->config['import'])) {
                    foreach (\Li\App::app()->config['import'] as $path) {
                        $classFile = PATH_APP . 'core' . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $className.'.php';
                        if (is_file($classFile)) {
                            include($classFile);
                        }
                    }
                }
            } else {
                // 命名空间
                $path = explode('\\', $className);

                $classFile = PATH_APP . '..' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $path) . '.php';

                if (is_file($classFile)) {
                    include($classFile);
                }

            }
            return class_exists($className, false) || interface_exists($className, false);
        }
        return true;
    }

    /**
     * catch fatal error
     */
    public static function shutdown()
    {
        $error = error_get_last();

        if ($error !== null) {
            if (App::app()->config['debug']) {
                Log::log()->fatal('Fatal Error:  ' .$error['message'] . ' in '. $error['file']. ' on '.$error['line']);
            }
        }
    }

    public static function exceptionHandler($exception)
    {
        if (App::app()->config['debug']) {
            echo "Exception: " . $exception->getMessage(). ' in ' . $exception->getFile() . ' on ' . $exception->getLine() . "<br />";
            echo 'trace    :' . '<br />';
            $trace = $exception->getTrace();
            foreach ($trace as $v) {
                echo '<b>' . $v['file'] . '</b>  in  <b>'. $v['function'] .'</b>  on  <b>' . $v['line'] . "</b><br />";
            }
        } else {
            Log::log()->error("Exception: " . $exception->getMessage(). ' in ' . $exception->getFile() . ' on ' . $exception->getLine());
        }
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline, $errContext)
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
        if (defined('E_STRICT')) {
            $errtype[E_STRICT] = 'runtime notice';
        }

        if ($errtype[$errno] != 'E_DEPRECATED') {
            echo "[" . date('Y-m-d H:i:s', NOW) . "] PHP {$errtype[$errno]}:  $errstr in $errfile on $errline<br/>";
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
    public function url($controller, $action, $param)
    {
        $path = url();
        if (empty($param)) {
            // return PATH_APP_REL.'/'.$controller.'/'.$action;
            return $path . '/' . $controller.'/'.$action;
        } else {
            // return PATH_APP_REL.'/'.$controller.'/'.$action.'?'.http_build_query($param);
            return $path . '/' . $controller.'/'.$action.'?'.http_build_query($param);
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
