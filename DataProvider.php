<?php
namespace Li;

/**
 *
 */
class DataProvider implements \Iterator, \ArrayAccess {
    public $model;
    public $config;

    private $_position = 0;

    private $_pagination=false;
    private $_data;

    function __construct($model, $config=array()) {
        $this->model = $model;

        $this->config = $config;

        if(!(isset($this->config['pagination']) && $this->config['pagination'] === false)) {
            $this->_pagination =  new Pagination($this->model, $config);
        }

        if(isset($this->config['pagination']) && $this->config['pagination'] === false) {
            $this->_data = $this->model->findAll($this->config['criteria']);
        } else {
            $this->_data = $this->model->page($this->_pagination->currentPage)->findAll($this->config['criteria']);
        }
    }

    public function getData() {
        return $this->_data;
    }

    public function getPagination() {
        return $this->_pagination;
    }

    public function rewind() {
        $this->_position = 0;
    }

    public function current() {
        return $this->_data[$this->_position];
    }

    public function key() {
        return $this->_position;
    }

    public function next() {
        ++$this->_position;
    }

    public function valid() {
        return isset($this->_data[$this->_position]);
    }


    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->_data[] = $value;
        } else {
            $this->_data[$offset] = $value;
        }
    }
    public function offsetExists($offset) {
        return isset($this->_data[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->_data[$offset]);
    }
    public function offsetGet($offset) {
        return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
    }

}
