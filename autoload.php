<?php

require __DIR__ . '/classes/App.php';
require __DIR__ . '/classes/Request.php';
require __DIR__ . '/classes/Controller.php';

App::$folder = __DIR__;

spl_autoload_register('phpFrameworkAutoload');

function phpFrameworkAutoload($class)
{
    if (substr($class, -5) == 'Model' && file_exists('../app/models/' . $class . '.php')) {
        require '../app/models/' . $class . '.php';
        return true;
    }

    if (substr($class, -6) == 'Helper') {
        require '../app/helpers/' . $class . '.php';
        return true;
    }

    if (substr($class, -10) == 'Controller' && file_exists('../app/controllers/' . $class . '.php')) {
        require '../app/controllers/' . $class . '.php';
        return true;
    }

    if (file_exists(__DIR__ . '/classes/' . $class . '.php')) {
        require __DIR__ . '/classes/' . $class . '.php';
        return true;
    }

    if (isset(App::$config['autoload'][$class])) {
        require '../app/' . App::$config['autoload'][$class] . $class . '.php';
        return true;
    }

    return false;
}

new App();