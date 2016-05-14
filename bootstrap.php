<?php
function debug($m)
{
    echo '<pre>';
    var_dump($m);
    echo '</pre>';
}

define('CHAR_TRUE', '1');
define('CHAR_FALSE', '0');

define('NOW', isset($_SERVER['REQUEST_TIME'])?$_SERVER['REQUEST_TIME']:time());

define('IS_POST', isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'POST'));
define('IS_GET', isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'GET'));
define('IS_PUT', isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'PUT'));
define('IS_DELETE', isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'DELETE'));
define('IS_CLI', PHP_SAPI == 'cli');
define('IS_AJAX', isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest");

define('PATH_VENDOR', realpath(__DIR__ . "/../../../") . '\\'); //基础目录(绝对)
define('PATH_BASE', PATH_VENDOR . '../');                       // 整个代码的根目录
define('PATH_LI',dirname(__FILE__));                            // 框架目录(绝对)
define('PATH_APP_REL', dirname($_SERVER['SCRIPT_NAME'])); // 当前APP的相对路径


function url($url='')
{
    if(PATH_APP_REL == '\\')
    {
        $pre = '';
    }
    else
    {
        $pre = PATH_APP_REL . '/';
    }

    return $pre.$url;
}

/**
 * 以数组中$key的值为索引，重建数组。
 * @param $array array a array
 * @param $key string the item of array
 */
function array_key($array, $key)
{
    $new_array = array();

    foreach ($array as $value) {
        $new_array[$value[$key]] = $value;
    }

    return $new_array;
}

function get($key,$filter)
{
    if($filter == 'int')
    {
        return (int)$_GET['key'];
    }
}

include PATH_VENDOR."autoload.php";

\Li\App::init()->run();
