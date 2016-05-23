<?php
namespace Li;

abstract class Form implements \ArrayAccess {
    public $validator;
    public $attributes;

    protected static $_form = array();
    protected $_scenario = 'default';

    public function __construct() {

    }

    public function __get($name) {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return null;
    }

    public function __set($name, $value) {
        $this->attributes[$name] = $value;
    }

    public static function form($className = __CLASS__) {
        if (isset(self::$_form[$className])) {
            return self::$_form[$className];
        } else {
            $model = self::$_form[$className] = new $className(null);
            return $model;
        }
    }

    public function validate($scenario = '') {
        if (!empty($scenario)) {
            $this->_scenario = $scenario;
        }
        $rules = $this->rules();

        if (isset($rules[$this->_scenario])) {
            $this->validator = Validator::make($this->attributes, $rules[$this->_scenario], [], $this->alias());
            return $this->validator->fails();
        }

        return true;
    }

    public function getValidator($scene = 'default') {
        $rule = $this->rules();
        if (isset($this->validator) && $this->validator instanceof Validator) {
            $this->validator->getValidator();
        } 
        return '';
    }

    public function getErrors() {
        if ($this->validator instanceof Validator) {
            return $this->validator->errors;
        }

        return [];
    }

    protected function rules() {
        return array();
    }

    protected function alias() {
        return array();
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }
    public function offsetExists($offset) {
        return isset($this->attributes[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->attributes[$offset]);
    }
    public function offsetGet($offset) {
        return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }
}
