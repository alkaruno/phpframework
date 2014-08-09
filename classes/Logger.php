<?php

class Logger
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    public function emergency($message, array $context = null)
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
        $loggerLevel = isset(App::$config['logging']['level']) ? App::$config['logging']['level'] : self::WARNING;

        $class = new ReflectionClass('Logger');
        foreach ($class->getConstants() as $const => $value) {
            if ($value == $level) {
                self::internalLog($level, $message, $context);
                return;
            }
            if ($value == $loggerLevel) {
                return;
            }
        }
    }

    private static function internalLog($level, $message, $context)
    {
        if (is_array($message)) {
            $message = print_r($message, true);
        }

        $message .= is_array($context) ? ': ' . print_r($context, true) : PHP_EOL;

        $filename = sprintf('../app/logs/%s.log', date('Y-m-d'));
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0777, true);
        }

        file_put_contents(
            $filename,
            sprintf('%s %s %s', date('Y.m.d H:i:s'), strtoupper($level), $message),
            FILE_APPEND
        );
    }
}
