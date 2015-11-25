<?php

namespace Xplosio\PhpFramework;

use Smarty;

class App
{
    public static $config = [];
    public static $folder;

    /** @var Request */
    public static $request;

    public function __construct()
    {
        set_error_handler([$this, 'errorHandler'], E_ERROR | E_WARNING);
        set_exception_handler([$this, 'errorHandler']);

        self::$folder = dirname(__DIR__);
        self::$request = new Request();

        self::$config = array_merge_recursive(include('../app/config/app.php'), include('../app/config/env.php'));

        if (array_key_exists('db', self::$config)) {
            Db::__init(self::$config['db']);
        }

        if (function_exists('app_filter')) {
            app_filter(self::$request);
        }

        if (array_key_exists('filter', self::$config) && is_callable(self::$config['filter'])) {
            $callable = self::$config['filter'];
            $callable(self::$request);
        }

        list($controller, $method, $params) = $this->getHandlerAndParams(self::$request->getUri());

        if (substr($controller, -10) !== 'Controller') {
            $controller .= 'Controller';
        }
        $class = $controller[0] === '\\' ? $controller : '\\app\\controllers\\' . $controller;

        $view = call_user_func_array([new $class(self::$request), $method], $params);
        $this->parseControllerResult($view, self::$request);
    }

    private function getHandlerAndParams($uri)
    {
        list($handler, $params) = self::route($uri, self::$config['routes']);

        $data = explode('.', $handler);
        if (count($data) === 2) {
            list($controller, $method) = $data;
        } else {
            $controller = $handler;
            $method = 'handle';
        }

        return [$controller, $method, $params];
    }

    private function parseControllerResult($view, Request $request)
    {
        if (is_array($view)) {
            if (count($view) === 2) {
                list($view, $data) = $view;
                foreach ($data as $name => $value) {
                    $request->set($name, $value);
                }
            } else if (count($view) === 3) {
                $request->set($view[1], $view[2]);
                $view = $view[0];
            }
        }

        if (substr($view, 0, 9) === 'redirect:') {
            header('Location: ' . substr($view, 9));
            return;
        }

        if ($view !== null) {
            self::showView($view, $request->getData());
        }
    }

    public static function showView($view, $data = [], $return = false)
    {
        $viewsPath = isset(self::$config['views_path']) ? self::$config['views_path'] : '../app/views';

        $info = pathinfo($view);

        if ($return) {
            ob_start();
        }

        switch ($info['extension']) {

            case 'php':
                extract($data);
                include $viewsPath . '/' . $view;
                break;

            case 'tpl':

                $viewsCachePath = isset(self::$config['views_cache_path']) ? self::$config['views_cache_path'] : '../app/cache/views';

                $smarty = new Smarty();
                $smarty->muteExpectedErrors();
                $smarty->setTemplateDir($viewsPath);
                $smarty->setCompileDir($viewsCachePath);
                $smarty->addPluginsDir(self::$folder . '/smarty');
                $smarty->addPluginsDir('../app/helpers/smarty');
                $smarty->assign($data);
                $smarty->display($view);
                break;

            case 'json':
                echo json_encode($data);
                break;

            default:
                throw new \InvalidArgumentException('Illegal view type', 500);
        }

        if ($return) {
            return ob_get_clean();
        }

        return null;
    }

    public static function route($input, $routes)
    {
        if ($routes !== null && is_array($routes)) {
            foreach ($routes as $key => $value) {
                if (is_array($value)) {
                    list($pattern, $handler) = $value;
                } else {
                    $pattern = $key;
                    $handler = $value;
                }
                if (preg_match('|' . $pattern . '|u', $input, $matches)) {
                    if (is_array($handler)) {
                        return self::route($input, $handler);
                    }
                    array_shift($matches);
                    return [$handler, $matches];
                }
            }
        }

        throw new \LogicException("Page not found for URI: $input", 404);
    }

    public static function loadConfig($name)
    {
        if (!array_key_exists($name, self::$config)) {
            self::$config[$name] = include("../app/config/$name.php");
        }

        return self::$config[$name];
    }

    /**
     * @deprecated use composer packages
     */
    public static function loadPackage($name)
    {
        require "../app/packages/$name/autoload.php";

        $configFilename = "../app/config/$name.php";
        if (file_exists($configFilename)) {
            self::$config[$name] = include($configFilename);
        }
    }

    public static function errorHandler()
    {
        $args = func_get_args();
        if (count($args) === 5) {
            Error::handle($args[0], $args[1], $args[2], $args[3], $args[4]);
        } else {
            Error::handle($args[0]);
        }
    }

    public static function getConfigValue($key, $default = null)
    {
        $key = (array)$key;

        $array = App::$config;
        $lastKey = array_pop($key);
        foreach ($key as $keyPart) {
            $array = array_key_exists($keyPart, $array) ? $array[$keyPart] : null;
            if ($array === null) {
                return $default;
            }
        }

        return array_key_exists($lastKey, $array) ? $array[$lastKey] : $default;
    }
}
