<?php

namespace Xplosio\PhpFramework;

use Xplosio\PhpFramework\Cache\Storage;

class Cache
{
    private static $storage;

    public static function cache($key, $seconds, \Closure $callback)
    {
        $value = self::get($key);

        if ($value === null) {
            $value = $callback();
            self::put($key, $value, $seconds);
        }

        return $value;
    }

    public static function get($key)
    {
        $storage = self::getStorage();
        $pair = $storage->get($key);

        if ($pair !== null) {
            list($value, $expiration) = $pair;
            if ($expiration > time()) {
                return $value;
            } else {
                $storage->remove($key);
            }
        }

        return null;
    }

    public static function put($key, $value, $seconds)
    {
        self::getStorage()->put($key, $value, time() + $seconds);
    }

    public static function remove($key)
    {
        self::getStorage()->remove($key);
    }

    public static function flush()
    {
        self::getStorage()->flush();
    }

    /**
     * @return Storage
     */
    private static function getStorage()
    {
        if (self::$storage === null) {
            $config = App::$config['cache'];
            $class = $config['storage'];
            self::$storage = new $class($config);
        }

        return self::$storage;
    }
}
