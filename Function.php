<?php

function debug($m)
{
    if(IS_CLI) {
        var_dump($m);
    } else  {
        echo '<pre>';
        var_dump($m);
        echo '</pre>';
    }
}

/**
 * 引入第三方库
 * @return [type] [description]
 */
function import($class)
{
    if (!class_exists($class)) {
        $file = PATH_APP . 'core' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . $class.'.php';
        require($file);
    }
}

/**
 * 获取model
 * @return [type] [description]
 */
function M($model, $new = false)
{
    $class = \Li\App::app()->namespace.'\core\model\\'.$model;

    if (!class_exists($class)) {
        $file = PATH_APP . 'core' . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR . $model.'.php';
        require($file);
    }
    if($new) {
        return new $class();
    } else {
        $class::model()->reset();
        return $class::model();
    }
}

/**
 * 将model返回的array，返回json
 * @param [type] $array  model数组
 * @param [type] $params 要Json的字段
 */
function J($array, $params=null)
{
    if(empty($params) || !is_array($params)) {
        return json_encode($array);
    } else {
        $arr = [];
        foreach($array as $value) {
            $tmp = [];
            foreach($params as $p) {
                $tmp[$p] = $value->$p;
            }
            $arr[] = $tmp;
        }

        return json_encode($arr);
    }

}

/**
 * 获取表单
 * @return [type] [description]
 */
function F($form)
{
    $class =  \Li\App::app()->namespace.'\core\form\\'.$form;

    if (!class_exists($class)) {
        $file = PATH_APP . 'core' . DIRECTORY_SEPARATOR . 'form' . DIRECTORY_SEPARATOR . $form.'.php';
        require $file;
    }
    return $class::form();
}
/**
 * 获得服务
 * @return [type] [description]
 */
function S($service)
{
    $class = \Li\App::app()->namespace.'\core\service\\'.$service;

    if (!class_exists($class)) {
        $file = PATH_APP . 'core' . DIRECTORY_SEPARATOR . 'service' . DIRECTORY_SEPARATOR . $service.'.php';
        require($file);
    }
    return $class::service();
}
/**
 * 生成url
 * @param  string $url [description]
 * @return [type]      [description]
 */
// function url($url = '')
// {
//     $preUrl = str_replace('\\', '/', PATH_APP_REL);
//     if ($preUrl === '/') {
//         $preUrl = '';
//     }
//     return $preUrl . $url;
// }
function url($route='', $absolute=false)
{
    return \Li\Route::instance()->createUrl($route, $absolute);
}

/**
 * 获得根路径
 * @return [type]      [description]
 */
function baseUrl($absolute=false)
{
    return \Li\Route::instance()->getBaseUrl($absolute);
}

/**
 * 跳转
 * @param  [type] $url [description]
 * @return [type]      [description]
 */
function redirect($url)
{
    header('Location: ' . $url);
    die;
}

function params($params)
{
    http_build_query($params, '/');
    foreach ($params as $key => $value) {
        # code...
    }
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

/**
 * [get description]
 * @param  [type]  $key        [description]
 * @param  string  $default    [description]
 * @param  boolean $empty true  如果get结果empty()为true，返回default
 *                        false 
 * @return [type]              [description]
 */
function get($key, $default = '', $empty = false)
{
    if (!array_key_exists($key, $_GET)) {
        return $default;
    }

    if($empty && empty($_GET[$key])) {
        return $default;
    }
    return $_GET[$key];
}

function post($key, $default = '')
{
    if (!array_key_exists($key, $_POST)) {
        return $default;
    }
    return $_POST[$key];
}


/**
 * RGB转 十六进制
 * @param $rgb RGB颜色的字符串 如：rgb(255,255,255);
 * @return string 十六进制颜色值 如：#FFFFFF
 */
function RGBToHex($rgb){
    $regexp = "/^rgb\(([0-9]{0,3})\,\s*([0-9]{0,3})\,\s*([0-9]{0,3})\)/";
    $re = preg_match($regexp, $rgb, $match);
    $re = array_shift($match);
    $hexColor = "#";
    $hex = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
    for ($i = 0; $i < 3; $i++) {
        $r = null;
        $c = $match[$i];
        $hexAr = array();
        while ($c > 16) {
            $r = $c % 16;
            $c = ($c / 16) >> 0;
            array_push($hexAr, $hex[$r]);
        }
        array_push($hexAr, $hex[$c]);
        $ret = array_reverse($hexAr);
        $item = implode('', $ret);
        $item = str_pad($item, 2, '0', STR_PAD_LEFT);
        $hexColor .= $item;
    }
    return $hexColor;
}
/**
 * 十六进制 转 RGB
 */
function hex2rgb($hexColor) {
    $color = str_replace('#', '', $hexColor);
    if (strlen($color) > 3) {
        $rgb = array(
            'r' => hexdec(substr($color, 0, 2)),
            'g' => hexdec(substr($color, 2, 2)),
            'b' => hexdec(substr($color, 4, 2))
        );
    } else {
        $color = $hexColor;
        $r = substr($color, 0, 1) . substr($color, 0, 1);
        $g = substr($color, 1, 1) . substr($color, 1, 1);
        $b = substr($color, 2, 1) . substr($color, 2, 1);
        $rgb = array(
            'r' => hexdec($r),
            'g' => hexdec($g),
            'b' => hexdec($b)
        );
    }
    return $rgb;
}

