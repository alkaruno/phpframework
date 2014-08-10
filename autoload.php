<?php

require __DIR__ . '/classes/App.php';
require __DIR__ . '/classes/Request.php';
require __DIR__ . '/classes/Session.php';
require __DIR__ . '/classes/Controller.php';

use Xplosio\PhpFramework\App;

App::$folder = __DIR__;

spl_autoload_register(function ($class) {

    $prefix = 'Xplosio\\PhpFramework\\';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) === 0) {
        require __DIR__ . '/classes/' . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, $len)) . '.php';
        return true;
    }

    $arr = [
        'Model' => 'models',
        'Helper' => 'helpers',
        'Controller' => 'controllers'
    ];

    foreach ($arr as $postfix => $dir) {
        if (substr($class, -strlen($postfix)) == $postfix) {
            $filename = "../app/{$dir}/{$class}.php";
            if (file_exists($filename)) {
                require $filename;
                return true;
            }
        }
    }

    if (isset(App::$config['autoload'][$class])) {
        require '../app/' . App::$config['autoload'][$class] . $class . '.php';
        return true;
    }

    return false;
});

new App();