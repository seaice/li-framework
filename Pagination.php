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
 */
class Pagination {
    private $_model;

    public $currentPage; // 当前页数
    public $prePage; // 前一页数
    public $nextPage; // 下一页数
    public $itemCount; // 总条目
    public $pageCount; // 总页数
    public $pageSize = 10; // 每页条数
    public $pageVar  = 'p'; // get变量名

    public $rangePage = 5; //显示当前页码的前后5页
    public $startPage = 1;
    public $endPage = 1;

    public $preHref;
    // public $offset;// 数据偏移
    // public $limit;// 同pageSize

    public function __construct($model, $config) {
        $this->_model      = $model;
        $this->currentPage = empty($_GET[$this->pageVar]) ? 1 : (int) $_GET[$this->pageVar];

        if(isset($config['pagination']) && is_array($config['pagination'])) {
            foreach($config['pagination'] as $key => $value) {
                $this->$key = $value;
            }
        }

        $this->init($config['criteria']);

        if (empty($criteria['order'])) {
            $criteria['order'] = $this->_model->pk . ' DESC';
        }

        $criteria['limit'] = ($this->currentPage - 1) * $this->pageSize . ',' . $this->pageSize;
    }

    public function init($criteria = array()) {
        $this->itemCount = $this->_model->count($criteria);

        $this->pageCount = ceil($this->itemCount / $this->pageSize);

        if ($this->pageCount < 1) {
            $this->pageCount = 1;
        }

        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        }

        if ($this->currentPage > $this->pageCount) {
            $this->currentPage = $this->pageCount;
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