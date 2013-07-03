<?php

define('FRAMEWORK_HOME', dirname(__FILE__));

spl_autoload_register('phpFrameworkAutoload');

function phpFrameworkAutoload($class)
{
    if (substr($class, -5) == 'Model' && file_exists('../app/models/' . $class . '.php')) {
        require '../app/models/' . $class . '.php';
        return true;
    } else if (substr($class, -6) == 'Helper') {
        require '../app/helpers/' . $class . '.php';
        return true;
    } else if (file_exists(FRAMEWORK_HOME . '/classes/' . $class . '.php')) {
        require FRAMEWORK_HOME . '/classes/' . $class . '.php';
        return true;
    } else if (isset(Dispatcher::$config['autoload'][$class])) {
        require '../app/' . Dispatcher::$config['autoload'][$class] . $class . '.php';
        return true;
    }

    return false;
}