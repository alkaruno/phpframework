<?php

namespace Xplosio\PhpFramework;

class Assert
{
    public static function check($value, $message = null)
    {
        if ($value === false) {
            throw new \InvalidArgumentException($message);
        }

        return $value;
    }

    public static function notNull($value, $message = null)
    {
        return self::check($value !== null, $message);
    }
}
