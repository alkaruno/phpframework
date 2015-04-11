<?php

namespace Xplosio\PhpFramework;

class Request
{
    const FLASH_MESSAGE_ATTRIBUTE = 'framework.flash_message';

    private $uri;
    private $data;

    public function __construct()
    {
        $data = parse_url($_SERVER['REQUEST_URI']);
        $this->uri = $data['path'];

        $GLOBALS['app']['errors'] = array();
    }

    public function set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function addError($field, $error)
    {
        $GLOBALS['app']['errors'][$field] = $error; // TODO некрасиво надо складывать в реквест по хитрому полю
    }

    public function addErrors($errors)
    {
        foreach ($errors as $field => $error) {
            $this->addError($field, $error);
        }
    }

    public function hasErrors()
    {
        return count($GLOBALS['app']['errors']) > 0;
    }

    public function getErrors()
    {
        return $this->hasErrors() ? $GLOBALS['app']['errors'] : NULL;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    public function setFlashMessage($message)
    {
        $this->getSession()->set(self::FLASH_MESSAGE_ATTRIBUTE, $message);
    }

    public function getFlashMessage()
    {
        $message = $this->getSession()->get(self::FLASH_MESSAGE_ATTRIBUTE);
        if ($message != null) {
            $this->getSession()->remove(self::FLASH_MESSAGE_ATTRIBUTE);
        }
        return $message;
    }

    public function getParameter($name, $default = null)
    {
        return isset($_GET[$name]) ? $_GET[$name] : $default;
    }

    public function postParameter($name, $default = null)
    {
        return isset($_POST[$name]) ? $_POST[$name] : $default;
    }

    public static function getParam($name, $default = null)
    {
        return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
    }

    public static function isGet()
    {
        return self::isMethod('GET');
    }

    public static function isPost()
    {
        return self::isMethod('POST');
    }

    public static function isMethod($method)
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($method);
    }
}
