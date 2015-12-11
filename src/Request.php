<?php

namespace Xplosio\PhpFramework;

class Request
{
    const FLASH_MESSAGE_ATTRIBUTE = 'request.flash_message';

    private static $uri;
    private static $attributes;

    public function __construct()
    {
        $data = parse_url($_SERVER['REQUEST_URI']);
        self::$uri = $data['path'];
    }

    public static function getUri()
    {
        return self::$uri;
    }

    public static function setUri($uri)
    {
        self::$uri = $uri;
    }

    public static function set($name, $value)
    {
        self::$attributes[$name] = $value;
    }

    public static function get($name, $default = null)
    {
        return array_key_exists($name, self::$attributes) ? self::$attributes[$name] : $default;
    }

    public static function getAttributes()
    {
        return self::$attributes;
    }

    public static function isMethod($method)
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($method);
    }

    public static function isMethodPost()
    {
        return self::isMethod('POST');
    }

    public static function isMethodGet()
    {
        return self::isMethod('GET');
    }

    public static function getParameter($name, $default = null)
    {
        return array_key_exists($name, $_REQUEST) ? $_REQUEST[$name] : $default;
    }

    public static function getParameters(...$names)
    {
        return array_map(function ($name) {
            return self::getParameter($name);
        }, $names);
    }

    public static function setFlashMessage($message)
    {
        Session::set(self::FLASH_MESSAGE_ATTRIBUTE, $message);
    }

    public static function getFlashMessage()
    {
        $message = Session::get(self::FLASH_MESSAGE_ATTRIBUTE);
        if ($message !== null) {
            Session::remove(self::FLASH_MESSAGE_ATTRIBUTE);
        }
        return $message;
    }

    /** @deprecated */
    public static function isGet()
    {
        return self::isMethod('GET');
    }

    /** @deprecated */
    public static function isPost()
    {
        return self::isMethod('POST');
    }
}
