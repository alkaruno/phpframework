<?php

class Dispatcher
{
    public static $config = array();

    private static $smarty;

    function __construct()
    {
        set_error_handler('Error::handle', E_ERROR | E_WARNING);
        set_exception_handler('Error::handle');

        $appConfig = include('../app/config/app.php');
        $envConfig = include('../app/config/env.php');
        self::$config = array_merge_recursive($appConfig, $envConfig);

        $request = new Request();
        $GLOBALS['app']['request'] = $request;

        if (isset(self::$config['filters'])) {
            foreach (self::$config['filters'] as $filter) {
                require '../app/' . $filter . '.php';
                /**
                 * @var Filter $filter
                 */
                $filter = new $filter;
                $filter->filter($request);
            }
        }

        list($controller, $method, $params) = $this->getHandlerAndParams($request->getUri());

        if (substr($controller, -10) !== 'Controller') {
            $controller .= 'Controller';
        }

        require '../app/controllers/' . $controller . '.php';
        $arr = explode('/', $controller);
        $controller = $arr[count($arr) - 1];

        /**
         * @var Controller $controller
         */
        $controller = new $controller($request);

        $view = call_user_func_array(array($controller, $method), $params);

        if (is_array($view)) {
            if (count($view) == 2) {
                list($view, $data) = $view;
                foreach ($data as $name => $value) {
                    $request->set($name, $value);
                }
            } else if (count($view) == 3) {
                $request->set($view[1], $view[2]);
                $view = $view[0];
            }
        }

        if (substr($view, 0, 9) == 'redirect:') {
            header('Location: ' . substr($view, 9));
            return;
        }

        if ($view != null) {
            self::showView($view, $request->getData());
        }
    }

    /**
     * Возвращает обработчик по REQUEST_URI и роутинговым правилам
     *
     * @param $uri
     * @return array
     */
    private function getHandlerAndParams($uri)
    {
        list($handler, $params) = self::route($uri, self::$config['routes']);

        $data = explode('.', $handler);
        if (count($data) == 2) {
            list($controller, $method) = $data;
        } else {
            $controller = $handler;
            $method = 'handle';
        }

        return array($controller, $method, $params);
    }

    /**
     * Отображает указанное представление с переданными параметрами
     *
     * @static
     * @param $view
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public static function showView($view, $data = array())
    {
        $path = isset(Dispatcher::$config['views_path']) ? Dispatcher::$config['views_path'] : '../app/views';
        $info = pathinfo($view);

        switch ($info['extension']) {

            case 'php':
                extract($data);
                include $path . '/' . $view;
                break;

            case 'tpl':
                if (self::$smarty == null) {
                    require FRAMEWORK_HOME . '/lib/smarty/Smarty.class.php';
                    self::$smarty = new Smarty();
                    self::$smarty->setTemplateDir($path);
                    self::$smarty->setCompileDir('../app/cache/views');
                    self::$smarty->addPluginsDir(FRAMEWORK_HOME . '/smarty');
                    self::$smarty->addPluginsDir('../app/helpers/smarty');
                }
                self::$smarty->assign($data);
                $result = self::$smarty->fetch($view);
                echo $result;
                break;

            case 'twig':
                require FRAMEWORK_HOME . '/lib/Twig/Autoloader.php';
                Twig_Autoloader::register();
                $loader = new Twig_Loader_Filesystem($path);
                $twig = new Twig_Environment($loader, array(
                    'cache' => '../app/cache/views',
                    'autoescape' => false,
                    'auto_reload' => true,
                ));
                $template = $twig->loadTemplate($view);
                echo $template->render($data);
                break;

            case 'json':
                echo json_encode($data);
                break;

            default:
                throw new Exception('Illegal view type', 500);
        }
    }

    /**
     * По роутинговым правилам находит хендлер и возвращает параметры
     *
     * @static
     * @param $input
     * @param $routes
     * @return array
     * @throws Exception
     */
    public static function route($input, $routes)
    {
        if ($routes !== null && is_array($routes)) {
            foreach ($routes as $key => $value) {
                if (is_array($value)) {
                    list($pattern, $hanler) = $value;
                } else {
                    $pattern = $key;
                    $hanler = $value;
                }
                if (preg_match('|' . $pattern . '|u', $input, $matches)) {
                    if (is_array($hanler)) {
                        return self::route($input, $hanler);
                    }
                    array_shift($matches);
                    return array($hanler, $matches);
                }
            }
        }

        throw new Exception('Page not found', 404);
    }

    /**
     * Загрузка конфигурационного файла
     *
     * @static
     * @param $name
     * @return mixed
     */
    public static function loadConfig($name)
    {
        if (!isset(self::$config[$name])) {
            self::$config[$name] = include('../app/config/' . $name . '.php');
        }
        return self::$config[$name];
    }
}