<?php
namespace Li;

/**
 *
 */
class DataProvider  {
    public $model;
    public $criteria;

    public $_pagination;

    function __construct($model, $config=array()) {
        $this->model = $model;
        // $this->model->attributes = $_GET;

        foreach($config as $key=>$value)
            $this->$key=$value;

        $this->_pagination =  new Pagination($this->model, $this->criteria);

    }

    public function getData() {
        return $this->model->findAll($this->criteria);
    }

    public function getPagination() {
        return $this->_pagination;
    }
}
