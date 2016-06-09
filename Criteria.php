<?php
namespace Li;

class Criteria implements \ArrayAccess {

    public $criteria;

    function __construct() {

    }

    public function compare() {
        $argc = func_num_args();
        $argv = func_get_args();

        if ($argc == 2 && !is_null($argv[1]) && $argv[1] !== '') {
            $this->criteria['condition'][] = [$argv[0], $argv[1]];
        } else if ($argc == 3 && !is_null($argv[2]) && $argv[2] !== '') {
            $this->criteria['condition'][] = [$argv[0], $argv[1], $argv[2]];
        }
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->criteria[] = $value;
        } else {
            $this->criteria[$offset] = $value;
        }
    }
    public function offsetExists($offset) {
        return isset($this->criteria[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->criteria[$offset]);
    }
    public function offsetGet($offset) {
        return isset($this->criteria[$offset]) ? $this->criteria[$offset] : null;
    }

    public function getCriteria() {
        return $this->criteria;
    }
}
