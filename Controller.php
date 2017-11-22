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
        $this->layout = 'layout' . DIRECTORY_SEPARATOR . 'main';
        $this->initTemplate();
    }

    public function init()
    {
    }

    /**
     * 创建一个控制器
     * @param  [type] $route [description]
     * @return [type]        [description]
     */
    public static function create($route)
    {
        if (($route = trim($route, '/')) === '') {
            $route = App::app()->config['defaultController'];
        }

        $route .= '/';
        if (($pos = strpos($route, '/')) !== false) {
            // 得到controller id
            $id = substr($route, 0, $pos);
            //controller id必须是字母
            if (!preg_match('/^\w+$/', $id)) {
                return null;
            }

            $className = ucfirst($id) . 'Controller';
            $fullClassName = App::app()->namespace . '\core\controller\\' . $className;
            $classFile = App::app()->getControllerPath() . '\\' . $className . '.php';

            if (is_file($classFile)) {
                if (!class_exists($fullClassName, false)) {
                    require $classFile;
                }

                if (class_exists($fullClassName, true) && is_subclass_of($fullClassName, 'Li\Controller')) {
                    $id[0] = strtolower($id[0]);

                    $route = substr($route, $pos + 1);

                    $seg = strpos($route, '/');

                    if (($actionId = substr($route, 0, $seg)) == false) {
                        $actionId = '';
                    }
                    // 初始化$_GET
                    \Li\Route::instance()->parseActionParams(substr($route, $seg + 1));

                    $controller = new $fullClassName($id);
                    $controller->init();
                    $controller->runAction((empty($actionId) ? App::app()->config['defaultAction'] : $actionId));

                    return $controller;
                } else {
                    echo 'controller must extends Controller!';
                    die;
                }

                return null;
            } else {
                Log::log()->warning("controller $fullClassName not exist");
                if(!App::app()->config['debug']){
                    redirect('/404.html');
                }
            }
        } else {
            echo 'error';
        }
    }

    public function runAction($action)
    {
        if (empty($action)) {
            $action = $this->action;
        }
        $this->action = $action;
        $action = $action . 'Action';
        $this->$action();
    }

    private function initTemplate()
    {
        $this->_template = new \Smarty();

        if (isset(App::app()->config['smarty']['caching'])) {
            $this->_template->caching = App::app()->config['smarty']['caching'];
        }
        if (isset(App::app()->config['smarty']['cache_lifetime'])) {
            $this->_template->cache_lifetime = App::app()->config['smarty']['cache_lifetime'];
        }

        $this->_template->muteExpectedErrors();
        $this->_template->php_handling = \Smarty::PHP_ALLOW;

        $this->_template->setTemplateDir(PATH_APP . 'core\\view\\');
        $this->_template->setCompileDir(PATH_APP . 'runtime\\template\\templates_c');
        // $smarty->setConfigDir('/web/www.example.com/guestbook/configs/');
        $this->_template->setCacheDir(PATH_APP . 'runtime\\template\\cache');

        $this->_template->addPluginsDir(PATH_LI . '\\vendor\\smarty\\plugin');
        $this->_template->addPluginsDir(PATH_APP . 'core\\vendor\\smarty\\plugin');
    }

    public function assign($tpl_var, $value, $nocache = false)
    {
        $this->_template->assign($tpl_var, $value, $nocache);
    }

    public function display($template = '')
    {
        if (empty($template)) {
            $template = $this->id . '\\' . $this->action;
        }
        if (isset($this->title)) {
            $this->assign('title', $this->title);
        }
        if (isset($this->meta_keywords)) {
            $this->assign('meta_keywords', $this->meta_keywords);
        }
        if (isset($this->meta_description)) {
            $this->assign('meta_description', $this->meta_description);
        }
        // $this->assign('content', $this->_getTemplateName($template), true);
        // $this->_template->display($this->_getTemplateName($this->layout));
        $this->_template->display($this->_getTemplateName($template));
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
    public function displayPartial($template = '')
    {
        if (empty($template)) {
            $template = $this->id . '\\' . $this->action;
        }

        $this->_template->display($this->_getTemplateName($template));
    }

    public function fetch($template = '')
    {
        if (empty($template)) {
            $template = $this->id . '\\' . $this->action;
        }

        return $this->_template->fetch($this->_getTemplateName($template));
    }

    private function _getTemplateName($template)
    {
        return $template . '.html';
    }

    public function outputJSON($errno, $msg="", $data = array())
    {
        $ret = array(
            'errno' => $errno ? "1" : "0",
            'msg' => $msg,
        );

        if(!empty($data)) {
            $ret['data'] = $data;
        }

        echo json_encode($ret);
        die;
    }

    /**
     * 创建url
     */
    public function url($param = array())
    {
        return App::app()->url($this->id, $this->action, $param);
    }

    public function redirect($uri = '', $params = [])
    {
        if (is_numeric($uri)) {
            http_response_code($uri);
            Log::log()->warning($uri. ' ' . $_SERVER['REQUEST_URI']);
            $uri .= '.html';
        }
        $urlParam = [];
        foreach ($params as $key => $value) {
            $urlParam[] = $key;
            $urlParam[] = $value;
        }
        if (empty($urlParam)) {
            $url = $uri;
        } else {
            $url = $uri . '/' . implode('/', $urlParam);
        }

        redirect(App::app()->config['domain'] . url() . '/' . $url);
    }
}
