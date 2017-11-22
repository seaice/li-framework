<?php
/**
 * [smarty_function_js description]
 * @param  array $params name    生成的文件名
 *                       include 包含的文件名数组
 * @param  class $smarty
 * @return string         返回html标签script
 */
function smarty_function_js($params, $smarty)
{
    $basePath = PATH_APP . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR;
    if (empty($params['name'])) {
        trigger_error('Widget is missing name.', E_USER_ERROR);
    }

    if (empty($params['include'])) {
        trigger_error('Widget is missing include.', E_USER_ERROR);
    }

    if (!is_array($params['include'])) {
        trigger_error('Widget include must be a array.', E_USER_ERROR);
    }

    require_once PATH_LI . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'minifier' . DIRECTORY_SEPARATOR . 'minifier.php';

    $string = '';
    foreach ($params['include'] as $i) {
        // 检查文件是否存在
        $file = $basePath . $i;

        if (!file_exists($file)) {
            trigger_error($file . ' not exist.', E_USER_ERROR);
        }

        $tmp = file_get_contents($file);
        if (\Li\App::app()->config['debug']) {
            $string .= $tmp;
        } else {
            $string .= minify_js($tmp);
        }
    }

    if (!empty($string)) {
        $objFile = $basePath . 'min' . DIRECTORY_SEPARATOR . $params['name'];

        if (!file_put_contents($objFile, $string)) {
            trigger_error($objFile . ' file_put_contents error.', E_USER_ERROR);
        }

        $jsName = url() . '/assets/js/min/' . $params['name']. '?'. md5_file($objFile);
        return '<script src="' .$jsName . '"></script>';
    }
}
