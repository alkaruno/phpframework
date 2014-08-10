<?php

namespace Xplosio\PhpFramework;

class Session
{
    public function get($name)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }

    public function set($name, $value)
    {
        if ($value != null) {
            $_SESSION[$name] = $value;
        } else {
            $this->remove($name);
        }
    }

    public function remove($name)
    {
        unset($_SESSION[$name]);
    }
}