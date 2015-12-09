<?php

namespace Xplosio\PhpFramework;

use Exception;

class Error
{
    public static function handle()
    {
        $args = func_get_args();

        if (count($args) === 5) {
            list($code, $text, $file, $line, $info) = $args;
            if (is_array($info)) {
                $info = print_r($info, true);
            }
            $code = 500;
        } else {
            /** @var Exception $e */
            $e = $args[0];
            $code = $e->getCode();
            $text = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $info = $e->getTraceAsString();
        }

        $errors = [
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error'
        ];

        if (!array_key_exists($code, $errors)) {
            $code = 500;
        }

        if (!headers_sent()) {
            header('HTTP/1.1 ' . $errors[$code], true, $code);
        }

        $message = "{$code} {$text} {$file}:{$line}";
        if ($info !== null) {
            $message .= "\n{$info}";
        }

        Logger::error($message);

        $data = [
            'code' => $code,
            'title' => $errors[$code],
            'message' => $message,
            'debug' => App::getConfigValue('debug', false)
        ];

        $errorView = App::getConfigValue(['views', 'error'], null);
        $viewsPath = App::getConfigValue(['views', 'views_path'], '../app/views');

        if ($errorView !== null && is_readable($viewsPath . DIRECTORY_SEPARATOR . $errorView)) {
            App::render($errorView, $data);
        } else {
            extract($data);
            include App::$folder . '/views/error.php';
        }

        exit;
    }
}
