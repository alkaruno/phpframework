<?php

class Logger
{
    private static $levels = array('trace', 'debug', 'info', 'warn', 'error', 'fatal');

    public static function log($message, $level = 'info')
    {
        if (!in_array($level, self::$levels)) {
            throw new Exception('Illegal logger level: ' . $level, 500);
        }

        if (is_array($message)) {
            $message = print_r($message, true);
        }

        $filename = '../app/logs/' . date('Y-m-d') . '.log';

        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0777, true);
        }

        $message = date('Y.m.d H:i:s') . ' ' . str_pad('[' . strtoupper($level) . ']', 7) . ' ' . $message; // TODO заменить на sprintf
        file_put_contents($filename, $message . PHP_EOL, FILE_APPEND);
    }

    /**
     * TODO хоть и лаконично, но не очень хорошо, потому как нет автокомлита и можно ошибиться в написании метода
     *
     * @param $name
     * @param $arguments
     */
    function __call($name, $arguments)
    {
        if (in_array($name, self::$levels) && count($arguments) == 1) {
            $this->log($arguments[0], $name);
        }
    }
}