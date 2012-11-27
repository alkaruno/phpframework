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
            404 => '404 Not Found',
            500 => 'Internal Server Error'
        );

        $title = isset($errors[$code]) ? $errors[$code] : null;

        if ($title !== null && !headers_sent()) {
            header('HTTP/1.1 ' . isset($errors[$code]) ? $errors[$code] : '', true, $code);
        }

        $message = sprintf('%s %s %s:%s<pre>%s</pre>', $code, $text, $file, $line, $info);
        $data = array('code' => $code, 'title' => $title, 'message' => $message, 'debug' => isset(Dispatcher::$config['env']['debug']) && Dispatcher::$config['env']['debug']);

        Logger::log($message, 'error');

        if (is_readable('../app/views/error.tpl')) {
            Dispatcher::showView('error.tpl', array('code' => $code, 'message' => $message, 'debug' => isset(Dispatcher::$config['env']['debug']) && Dispatcher::$config['env']['debug']));
        } else {
            extract($data);
            include FRAMEWORK_HOME . '/views/error.php';
        }

        exit;
    }
}