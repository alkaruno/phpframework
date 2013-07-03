<?php

class Logger
{
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    public static function alert($message)
    {
        self::log($message, self::ALERT);
    }

    public static function critical($message)
    {
        self::log($message, self::CRITICAL);
    }

    public static function error($message)
    {
        self::log($message, self::ERROR);
    }

    public static function warning($message)
    {
        self::log($message, self::WARNING);
    }

    public static function notice($message)
    {
        self::log($message, self::NOTICE);
    }

    public static function info($message)
    {
        self::log($message, self::INFO);
    }

    public static function debug($message)
    {
        self::log($message, self::DEBUG);
    }

    public static function log($message, $level = self::INFO)
    {
        if (!in_array($level, array(self::ALERT, self::CRITICAL, self::ERROR, self::ERROR, self::WARNING, self::NOTICE, self::INFO, self::DEBUG))) {
            throw new Exception('Illegal logger level: ' . $level, 500);
        }

        if (is_array($message)) {
            $message = print_r($message, true);
        }

        $filename = sprintf('../app/logs/%s.log', date('Y-m-d'));

        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0777, true);
        }

        file_put_contents($filename, sprintf('%s %s %s%s', date('Y.m.d H:i:s'), strtoupper($level), $message, PHP_EOL), FILE_APPEND);
    }
}