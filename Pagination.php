<?php
namespace Li;
/**
 * currentPage 当前页数
 * itemCount 总条目
 * pageCount 总页数
 * pageSize 每页条数
 * pageVar get变量名
 * offset 数据便宜
 * limit 同pageSize
 *
 * 三种用法：
 * new Pagination($model, $config)
 * new Pagination($config)
 * new Pagination()
 * 
 */
class Pagination {
    // private $_model;
    // private $_config;

    public $className   = 'page'; // css类名

    public $currentPage = 1; // 当前页数
    public $prePage     = 1; // 前一页数
    public $nextPage    = 1; // 下一页数
    public $itemCount   = 15; // 总条目
    public $pageCount   = 1; // 总页数
    public $pageSize    = 15; // 每页条数
    public $pageVar     = 'p'; // get变量名

    public $rangePage   = 5; //显示当前页码的前后5页
    public $startPage   = 1;
    public $endPage     = 1;

    public $preHref;

    public function __construct($config=null)
    {
        $this->currentPage = intval(get($this->pageVar, 1));
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        }

        $this->initConfig($config);

        // if($argc == 0) {
        //     // $this->init();
        // } else if($argc == 1) { // config
        //     $this->initConfig($argc[0]);
        //     // $this->init();

        // } else if($argc == 2) { // $model, $config

        //     $this->_model = $argv[0];

        //     $this->initConfig($argv[1]);
        //     $this->init($config['criteria']);

        //     if (empty($criteria['order'])) {
        //         $criteria['order'] = $this->_model->getPk() . ' DESC';
        //     }

        //     $criteria['limit'] = ($this->currentPage - 1) * $this->pageSize . ',' . $this->pageSize;
        // }
    }

    protected function initConfig($config)
    {
        if(!empty($config) && is_array($config)) {
            foreach($config as $key => $value) {
                $this->$key = $value;
            }   
        }
    }

    public function getItemCount($model, $criteria=null)
    {
        if(is_string($model)) {
            return M($model)->count($criteria);
        } else {
            return $model->count($criteria);
        }
    }

    public function init($model, $criteria=null) {
        $this->itemCount = $this->getItemCount($model, $criteria);

        $this->pageCount = ceil($this->itemCount / $this->pageSize);

        if ($this->currentPage > $this->pageCount) {
            $this->currentPage = $this->pageCount;
        }

        if ($this->pageCount < 1) {
            $this->pageCount = 1;
        }

        $this->prePage  = $this->currentPage - 1;
        $this->nextPage = $this->currentPage + 1;

        if ($this->prePage < 1) {
            $this->prePage = 1;
        }

        if ($this->nextPage > $this->pageCount) {
            $this->nextPage = $this->pageCount;
        }

        if($this->currentPage < $this->rangePage*2) {
            $this->startPage = 1;
            $this->endPage = ($this->pageCount > $this->rangePage*2) ? $this->rangePage*2 : $this->pageCount;
        } else if(($this->currentPage + $this->rangePage*2) > $this->pageCount) {
            $this->startPage = $this->pageCount - $this->rangePage*2;
            $this->endPage = $this->pageCount;
        } else {
            $this->startPage = $this->currentPage - $this->rangePage;
            $this->endPage = $this->currentPage + $this->rangePage;
        }

        $this->preHref = $this->unsetParam($this->pageVar, $_SERVER['REQUEST_URI']);
        if (strpos($this->preHref, '?') === false) {
            $this->preHref .= '?';
        } else {
            $this->preHref .= '&';
        }
    }

    function unsetParam($param, $url) {
        return preg_replace(
            array("/{$param}=[^&]*/i", '/[&]+/', '/\?[&]+/', '/[?&]+$/'),
            array('', '&', '?', ''),
            $url
        );
    }
}