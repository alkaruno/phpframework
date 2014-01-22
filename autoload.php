<?php

require __DIR__ . '/classes/Dispatcher.php';
require __DIR__ . '/classes/Request.php';
require __DIR__ . '/classes/Controller.php';

App::$folder = __DIR__;

spl_autoload_register('phpFrameworkAutoload');

function phpFrameworkAutoload($class)
{
    $result = false;

    if (substr($class, -5) == 'Model' && file_exists('../app/models/' . $class . '.php')) {
        require '../app/models/' . $class . '.php';
        $result = true;
    } else if (substr($class, -6) == 'Helper') {
        require '../app/helpers/' . $class . '.php';
        $result = true;
    } else if (substr($class, -10) == 'Controller' && file_exists('../app/controllers/' . $class . '.php')) {
        require '../app/controllers/' . $class . '.php';
        $result = true;
    } else if (file_exists(__DIR__ . '/classes/' . $class . '.php')) {
        require __DIR__ . '/classes/' . $class . '.php';
        $result = true;
    } else if (isset(Dispatcher::$config['autoload'][$class])) {
        require '../app/' . Dispatcher::$config['autoload'][$class] . $class . '.php';
        $result = true;
    }

    return $result;
}

new App();