<?php 

function debug($m) {
    echo '<pre>';
    var_dump($m);
    echo '</pre>';
}

/**
 * 引入第三方库
 * @return [type] [description]
 */
function import($class) {
    if(!class_exists($class)) {
        $file = PATH_APP . 'core' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . $class.'.php';
        require($file);
    } 
}

/**
 * 获取model
 * @return [type] [description]
 */
function m($model) {
    $class = 'model\\'.$model;

    if(!class_exists($class)) {
        $file = PATH_APP . 'core' . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR . $model.'.php';
        require($file);
    } 
    return $class::model();
}

/**
 * 获取表单
 * @return [type] [description]
 */
function f($form) {
    $class = 'form\\'.$form;

    if(!class_exists($class)) {
        $file = PATH_APP . 'core' . DIRECTORY_SEPARATOR . 'form' . DIRECTORY_SEPARATOR . $form.'.php';
        require $file;
    }
    return $class::form();
}
/**
 * 获得服务
 * @return [type] [description]
 */
function s($service) {
    $class = 'service\\'.$service;

    if(!class_exists($class)) {
        $file = PATH_APP . 'core' . DIRECTORY_SEPARATOR . 'service' . DIRECTORY_SEPARATOR . $service.'.php';
        require($file);
    }
    return $class::service();
}
/**
 * 获得根路径url
 * @param  string $url [description]
 * @return [type]      [description]
 */
function url($url = '') {
    $preUrl = str_replace('\\', '/', PATH_APP_REL);
    if($preUrl === '/') {
        $preUrl = '';
    }
    return $preUrl . $url;
}

/**
 * 跳转
 * @param  [type] $url [description]
 * @return [type]      [description]
 */
function redirect($url) {
    header('Location: ' . $url);
    die;
}

function params($params) {
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
function array_key($array, $key) {
    $new_array = array();

    foreach ($array as $value) {
        $new_array[$value[$key]] = $value;
    }

    return $new_array;
}

function get($key, $default = '') {
    if (!array_key_exists($key, $_GET)) {
        return $default;
    }
    return $_GET[$key];
}

function post($key, $default = '') {
    if (!array_key_exists($key, $_POST)) {
        return $default;
    }
    return $_POST[$key];
}


