<?php

namespace Xplosio\PhpFramework;

class Logger
{
    const EMERGENCY = 1;
    const ALERT = 2;
    const CRITICAL = 3;
    const ERROR = 4;
    const WARNING = 5;
    const NOTICE = 6;
    const INFO = 7;
    const DEBUG = 8;

    protected static $levels = [
        self::EMERGENCY => 'EMERGENCY',
        self::ALERT => 'ALERT',
        self::CRITICAL => 'CRITICAL',
        self::ERROR => 'ERROR',
        self::WARNING => 'WARNING',
        self::NOTICE => 'NOTICE',
        self::INFO => 'INFO',
        self::DEBUG => 'DEBUG'
    ];

    private static $level;

    public static function init()
    {
        self::$level = App::getConfigValue(['logging', 'level'], self::WARNING);
    }

    public static function emergency($message, array $context = null)
    {
        self::log(self::EMERGENCY, $message, $context);
    }

    public static function alert($message, array $context = null)
    {
        self::log(self::ALERT, $message, $context);
    }

    public static function critical($message, array $context = null)
    {
        self::log(self::CRITICAL, $message, $context);
    }

    public static function error($message, array $context = null)
    {
        self::log(self::ERROR, $message, $context);
    }

    public static function warning($message, array $context = null)
    {
        self::log(self::WARNING, $message, $context);
    }

    public static function notice($message, array $context = null)
    {
        self::log(self::NOTICE, $message, $context);
    }

    public static function info($message, array $context = null)
    {
        self::log(self::INFO, $message, $context);
    }

    public static function debug($message, array $context = null)
    {
        self::log(self::DEBUG, $message, $context);
    }

    public static function log($level, $message, array $context = null)
    {
        if ($level > self::$level) {
            return;
        }

        if (is_array($message)) {
            $message = print_r($message, true);
        }

        $message .= is_array($context) ? ': ' . print_r($context, true) : PHP_EOL;

        $filename = sprintf('../app/logs/%s.log', date('Y-m-d'));
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0777, true);
        }

        $line = sprintf('%s %s %s', date('Y.m.d H:i:s'), strtoupper(self::$levels[$level]), $message);
        file_put_contents($filename, $line, FILE_APPEND);
    }
}

Logger::init();
