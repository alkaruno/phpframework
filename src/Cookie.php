<?php

namespace Xplosio\PhpFramework;

class Cookie
{
    public static function get($name, $default = null)
    {
        return array_key_exists($name, $_COOKIE) ? $_COOKIE[$name] : $default;
    }

    public static function set($name, $value, $period, $path = '/', $domain = null, $secure = null)
    {
        setcookie($name, $value, time() + $period, $path, $domain, $secure);
    }

    public static function remove($name)
    {
        $value = self::get($name);
        setcookie($name, '', 0, '/');

        return $value;
    }
}
