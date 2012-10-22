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
            404 => 'HTTP/1.1 404 Not Found',
            500 => 'HTTP/1.1 Internal Server Error'
        );

        if (isset($errors[$code]) && !headers_sent()) {
            header(isset($errors[$code]) ? $errors[$code] : '', true, $code);
        }

        $message = sprintf('%s %s %s:%s<pre>%s</pre>', $code, $text, $file, $line, $info);
        Dispatcher::showView('error.tpl', array('code' => $code, 'message' => $message, 'debug' => isset(Dispatcher::$config['env']['debug']) && Dispatcher::$config['env']['debug']));
        Logger::log($message, 'error');

        exit;
    }
}