<?php
namespace Li;
class Di
{
    private $_di = array();

    public function get($key)
    {
        if(isset($this->_di[$key]))
        {
            return $this->_di[$key];
        }
        else
        {
            throw new \Li\Exception('class not exist');
            echo 'class not exist';
        }
    }

    public function set($key, $value)
    {
        if(isset($this->_di[$key]))
        {
            echo 'class existed';
        }
        else
        {
            $this->_di[$key] = $value;
        }
    }
}

