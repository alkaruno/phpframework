<?php

namespace Xplosio\PhpFramework;

class Cookie
{
    public static function get($name, $default = null)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
    }

    public static function set($name, $value, $period, $path = '/', $domain = null)
    {
        setcookie($name, $value, time() + $period, $path, $domain);
    }

    public static function remove($name)
    {
        setcookie($name, '', 0, '/');
    }
}