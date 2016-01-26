<?php
namespace Li;

abstract class Service {
    private static $_service=array();

    public static function service($className=__CLASS__)
    {
        if(isset(self::$_service[$className]))
            return self::$_service[$className];
        else
        {
            $model=self::$_service[$className]=new $className(null);
            return $model;
        }
    }
}