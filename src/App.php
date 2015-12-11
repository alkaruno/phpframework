<?php

namespace Xplosio\PhpFramework;

use Xplosio\PhpFramework\View\AbstractViewRenderer;

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

        if (array_key_exists('filter', self::$config) && is_callable(self::$config['filter'])) {
            $callable = self::$config['filter'];
            $callable(self::$request);
        }

        list($controller, $method, $params) = $this->getHandlerAndParams(self::$request->getUri());

        if (substr($controller, -10) !== 'Controller') {
            $controller .= 'Controller';
        }
        $class = substr($controller, 0, 4) === 'app\\' ? $controller : '\\app\\controllers\\' . $controller;

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
            self::render($view, $request->getAttributes());
        }
    }

    public static function render($view, array $data = [], $return = false)
    {
        $return === false || ob_start();

        $viewsPath = self::getConfigValue(['views', 'views_path'], '../app/views');
        $extension = pathinfo($view)['extension'];

        switch ($extension) {

            case 'php':
                extract($data);
                include $viewsPath . DIRECTORY_SEPARATOR . $view;
                break;

            case 'json':
                if (!headers_sent()) {
                    header('Content-type: application/json');
                }
                echo json_encode($data);
                break;

            default:
                $renderers = (array)self::getConfigValue(['views', 'renderers'], []);
                if (array_key_exists($extension, $renderers)) {
                    $options = $renderers[$extension];
                    $className = '\\' . $options['class'];
                    /** @var AbstractViewRenderer $renderer */
                    $cachePath = App::getConfigValue(['views', 'cache_path'], $viewsPath . '/cache');
                    $renderer = new $className($viewsPath, $cachePath, $options);
                    $renderer->render($view, $data);
                    break;
                }

                throw new \InvalidArgumentException('Illegal view type', 500);
        }

        return $return ? ob_get_clean() : null;
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

    public static function errorHandler()
    {
        $args = func_get_args();
        if (count($args) === 5) {
            Error::handle($args[0], $args[1], $args[2], $args[3], $args[4]);
        } else {
            Error::handle($args[0]);
        }
    }

    public static function getConfigValue($key, $default = null, array $config = null)
    {
        $key = (array)$key;
        $config = $config ?: self::$config;

        $lastKey = array_pop($key);
        foreach ($key as $keyPart) {
            $config = array_key_exists($keyPart, $config) ? $config[$keyPart] : null;
            if ($config === null) {
                return $default;
            }
        }

        return array_key_exists($lastKey, $config) ? $config[$lastKey] : $default;
    }
}
