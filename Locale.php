<?php
namespace Li;
class Locale {
    public static $_instance;
    protected $_language = 'en';
    protected $_message = [];

    public static function locale() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function trans($id, $parameters = [], $locale = null) {
        $file = 'default';

        if (strpos($id, '.')) {
            list($file, $ids) = explode('.', $id, 2);
            $arr_ids = explode('.', $ids);
        }

        if(! array_key_exists($file, $this->_message)) {
            $this->_message[$file] = require APP_PATH . 'resoures' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $this->_language . DIRECTORY_SEPARATOR . $file . '.php';
        }

        $ret = $this->_message[$file];

        foreach($arr_ids as $value) {
            if(array_key_exists($value, $ret)) {
                $ret = str_replace(array_keys($parameters), array_values($parameters), $ret[$value]);
            } else {
                return $id;
            }
        }
        
        return $ret;
    }

}
