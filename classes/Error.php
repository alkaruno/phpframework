<?php

/**
 * Обработчик ошибок и исключений
 */
class Error
{
    public static function handle()
    {
        $args = func_get_args();

        if (count($args) == 5) {
            list($code, $text, $file, $line, $info) = $args;
            $code = 500;
        } else {
            /**
             * @var Exception $e
             */
            $e = $args[0];
            $code = $e->getCode();
            $text = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $info = $e->getTraceAsString();
        }

        $errors = array(
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error'
        );

        if (!isset($errors[$code])) {
            $code = 500;
        }

        if (!headers_sent()) {
            header('HTTP/1.1 ' . isset($errors[$code]) ? $errors[$code] : '', true, $code);
        }

        $message = sprintf('%s %s %s:%s<pre>%s</pre>', $code, $text, $file, $line, $info);
        $data = array(
            'code' => $code,
            'title' => $errors[$code],
            'message' => $message,
            'debug' => isset(Dispatcher::$config['env']['debug']) && Dispatcher::$config['env']['debug']
        );

        Logger::log($message, 'error');

        if (isset(Dispatcher::$config['errorView']) && is_readable('../app/views/' . Dispatcher::$config['errorView'])) {
            /** @var $request Request */
            $request = $GLOBALS['app']['request'];
            foreach ($data as $key => $value) {
                $request->set($key, $value);
            }
            Dispatcher::showView(Dispatcher::$config['errorView'], $request->getData());
        } else {
            extract($data);
            include App::$folder . '/views/error.php';
        }

        exit;
    }
}