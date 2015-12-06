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
            $info = null; // FIXME
            if (is_array($info)) {
                $info = print_r($info, true);
            }
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

        $errors = [
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error'
        ];

        if (!isset($errors[$code])) {
            $code = 500;
        }

        if (!headers_sent()) {
            header('HTTP/1.1 ' . isset($errors[$code]) ? $errors[$code] : '', true, $code);
        }

        $message = "{$code} {$text} {$file}:{$line}";
        if ($info !== null) {
            $message .= "\n{$info}";
        }

        $data = array(
            'code' => $code,
            'title' => $errors[$code],
            'message' => $message,
            'debug' => isset(App::$config['debug']) && App::$config['debug']
        );

        Logger::error($message);

        if (isset(App::$config['errorView']) && is_readable(App::$config['views_path'] . DIRECTORY_SEPARATOR . App::$config['errorView'])) {
            /** @var $request Request */
            $request = App::$request;
            foreach ($data as $key => $value) {
                $request->set($key, $value);
            }
            App::render(App::$config['errorView'], $request->getAttributes());
        } else {
            extract($data);
            include App::$folder . '/views/error.php';
        }

        exit;
    }
}
