<?php

namespace Xplosio\PhpFramework;

class Assert
{
    public static function check($value, $message = null)
    {
        if ($value === false) {
            throw new \InvalidArgumentException($message);
        }
    }

    public static function notNull($value, $message = null)
    {
        self::check($value !== null, $message);

        return $value;
    }
}
