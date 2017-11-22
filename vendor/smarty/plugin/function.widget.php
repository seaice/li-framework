<?php
/**
 * [smarty_function_widget description]
 * @param  array $params
 *                 name     必须，widget名称，assign到模板中的变量名称
 *                 template 非必须，模板名称，如果未设置，template=name
 *                 service  非必须，对应服务名，可为空，为空不请求数据
 *                 function 非必须，对应服务的方法，默认为getData
 *                 params   非必须，function的参数，可为空,字符串or数组
 * @param  mixed $smarty [description]
 * @return [type]         [description]
 *
 * eg.
 * {widget name='manual_list' service='constant' function='getManualList' params='10'}
 * {widget name='manual_list' service='constant' function='getManualList' params=["start"=>10, "size"=>123]} // params 数组
 */

function smarty_function_widget($params, $smarty)
{
    if (empty($params['name'])) {
        trigger_error('Widget is missing name.', E_USER_ERROR);
    }

    if (empty($params['template'])) {
        $template = 'widget/'. $params['name'] . '.html';
    } else {
        $template = $params['template'];
    }
    
    $plugin = $smarty->createTemplate($template);

    if (!empty($params['service'])) {
        $service = $params['service'];
        $class   = \Li\App::app()->namespace.'\\core\service\\'.ucfirst($service);

        if (!class_exists($class)) {
            trigger_error('Widget is missing service ' . $class, E_USER_ERROR);
        }

        $function = 'getData';
        if (!empty($params['function'])) {
            $function = $params['function'];
        }

        if (!method_exists($class::service(), $function)) {
            trigger_error('Widget is missing function "' . $function . '" of service "'. $service . '"', E_USER_ERROR);
        }

        if (empty($params['params'])) {
            $data = $class::service()->$function();
        } else {
            $data = $class::service()->$function($params['params']);
        }

        $plugin->assign($params['name'], $data);
    }

    $plugin->display();
}
