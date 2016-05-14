<?php 


namespace Li;

class Str {

    protected static $_studlyCache = [];

    public static function studly($value)
    {
        $key = $value;

        if (isset(static::$_studlyCache[$key])) {
            return static::$_studlyCache[$key];
        }

        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return static::$_studlyCache[$key] = str_replace(' ', '', $value);
    }
}