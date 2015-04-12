<?php

namespace Xplosio\PhpFramework;

use Exception;
use Smarty;

class App
{
    public static $folder;
    public static $config = [];

    /** @var Request */
    public static $request;

    public function __construct()
    {
        set_error_handler([$this, 'errorHandler'], E_ERROR | E_WARNING);
        set_exception_handler([$this, 'errorHandler']);

        App::$folder = dirname(__DIR__);
        App::$request = new Request();

        self::$config = array_merge_recursive(
            include('../app/config/app.php'),
            include('../app/config/env.php')
        );

        if (isset(App::$config['db'])) {
            Db::__init(App::$config['db']);
        }

        if (function_exists('app_filter')) {
            app_filter(App::$request);
        }

        /** @deprecated use app_filter function */
        if (isset(self::$config['filters'])) {
            foreach (self::$config['filters'] as $filter) {
                require '../app/' . $filter . '.php';
                /**
                 * @var Filter $filter
                 */
                $filter = new $filter;
                $filter->filter(App::$request);
            }
        }

        list($controller, $method, $params) = $this->getHandlerAndParams(App::$request->getUri());

        if (substr($controller, -10) !== 'Controller') {
            $controller .= 'Controller';
        }
        $controller = '\\app\\controllers\\' . $controller;

        $view = call_user_func_array([new $controller(App::$request), $method], $params);
        $this->parseControllerResult($view, App::$request);
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
                throw new Exception('Illegal view type', 500);
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

        throw new Exception('Page not found for URI: ' . $input, 404);
    }

    public static function loadConfig($name)
    {
        if (!isset(self::$config[$name])) {
            self::$config[$name] = include('../app/config/' . $name . '.php');
        }
        return self::$config[$name];
    }

    public static function loadPackage($name)
    {
        require '../app/packages/' . $name . '/autoload.php';

        $configFilename = '../app/config/' . $name . '.php';
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
}
