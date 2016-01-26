<?php
namespace Li;

abstract class Controller
{
    public $id;
    public $route;
    public $action = 'index';

    public $layout;

    private $_template;

    public function __construct($id)
    {
        $this->id = $id;
        $this->layout = 'layout'.DIRECTORY_SEPARATOR.'main';
        $this->initTemplate();
    }

    /**
     * 创建一个控制器
     * @param  [type] $route [description]
     * @return [type]        [description]
     */
    static function create($route)
    {

        if(($route = trim($route,'/'))==='')
            $route = App::app()->config['defaultController'];

        $route .= '/';
        // debug($route);
        if(($pos = strpos($route,'/')) !== false)
        {
            // 得到controller id
            $id = substr($route, 0, $pos);
            //controller id必须是字母
            if(!preg_match('/^\w+$/', $id))
                return null;
            
            $className = ucfirst($id) . 'Controller';
            $classFile = App::app()->getControllerPath() . '\\' . $className . '.php';

            if(is_file($classFile))
            {
                if(!class_exists($className, false))
                {
                    require($classFile);
                }

                // debug(class_parents ($className));
                if(class_exists($className, false) && is_subclass_of($className, 'Li\Controller'))
                {
                    $id[0] = strtolower($id[0]);

                    $route = substr($route, $pos + 1);

                    $seg = strpos($route, '/');

                    if(($actionId = substr($route, 0, $seg)) == false)
                    {
                        $actionId = '';
                    }
                    // 初始化$_GET
                    \Li\Route::instance()->parseActionParams(substr($route,$seg + 1));

                    $controller = new $className($id);
                    $controller->runAction((empty($actionId)?App::app()->config['defaultAction']:$actionId));

                    return $controller;
                }
                else
                {
                    echo 'controller must extends Controller!';
                    die;
                }

                return null;
            }
            else
            {
                echo 'controller file not exist';
                die;
            }
        }
        else
        {
            echo 'error';
        }

    }
   
    public function runAction($action)
    {
        if(empty($action))
        {
            $action = $this->action;
        }
        $this->action = $action;
        $action = $action . 'Action';
        $this->$action();
    }

    private function initTemplate()
    {
        include(LI_PATH.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'smarty'.DIRECTORY_SEPARATOR.'Smarty.php');
        $this->_template = new \Smarty();

        $this->_template->force_compile = true;
        $this->_template->caching = true;
        $this->_template->cache_lifetime = 120;

        $this->_template->muteExpectedErrors();
        $this->_template->setTemplateDir(APP_PATH. 'core\\view\\');
        $this->_template->setCompileDir(APP_PATH. 'runtime\\template\\templates_c');
        // $smarty->setConfigDir('/web/www.example.com/guestbook/configs/');
        $this->_template->setCacheDir(APP_PATH. 'runtime\\template\\cache');
    }

    public function assign($varname, $var)
    {
        $this->_template->assign($varname, $var);
    }

    public function display($template='')
    {
        if(empty($template))
        {
            $template = $this->id.'\\'.$this->action;
        }
        $this->assign('content', $this->_getTemplateName($template));
        $this->_template->display($this->_getTemplateName($this->layout));
    }

    public function clearAllAssign()
    {
        $this->_template->clearAllAssign();
    }

    public function clearAllCache()
    {
        $this->_template->clearAllCache();
    }

    /**
     * 不渲染layout
     */
    public function displayPartial($template='')
    {
        if(empty($template))
        {
            $template = $this->id.'\\'.$this->action;
        }

        $this->_template->display($this->_getTemplateName($template));       
    }

    public function fetch($template='')
    {
        if(empty($template))
        {
            $template = $this->id.'\\'.$this->action;
        }

        return $this->_template->fetch($this->_getTemplateName($template));
    }

    private function _getTemplateName($template)
    {
        return $template.'.html';
    }

    public function outputJSON($errno, $data=array())
    {
        $ret = array(
            'errno' => $errno ? CHAR_TRUE : CHAR_FALSE,
            'data' => $data
        );

        echo json_encode($ret);
        die;
    }

    /**
     * 创建url
     */
    public function url($param=array())
    {
        return App::app()->url($this->id,$this->action,$param);
    }
}
