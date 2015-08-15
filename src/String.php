<?php

namespace Xplosio\PhpFramework;

class String
{
    public static function startsWith($haystack, $needle)
    {
        return $needle === '' || substr($haystack, 0, strlen($needle)) === $needle;
    }

    public static function endsWith($haystack, $needle)
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }

    public static function toSnakeCase($string)
    {
        return strtolower(preg_replace_callback('/([a-z])([A-Z])/', function ($match) {
            return $match[1] . '_' . strtolower($match[2]);
        }, $string));
    }

    public static function toCamelCase($string, $capitalizeFirstCharacter = false)
    {
        $string = preg_replace_callback('/_([a-z])/', function ($match) {
            return strtoupper($match[1][0]);
        }, $string);

        return $capitalizeFirstCharacter ? ucfirst($string) : $string;
    }
}
