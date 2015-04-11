<?php

namespace Xplosio\PhpFramework;

class Session
{
    public static function get($name, $default = null)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
    }

    public static function set($name, $value)
    {
        if ($value != null) {
            $_SESSION[$name] = $value;
        } else {
            self::remove($name);
        }
    }

    public static function remove($name)
    {
        unset($_SESSION[$name]);
    }
}
