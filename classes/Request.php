<?php

class Request
{
    const FLASH_MESSAGE_ATTRIBUTE = 'framework.flash_message';

    private $uri;
    private $data;

    /**
     * @var Session
     */
    private $session;

    function __construct()
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
        return isset($this->data[$name]) ? $this->data[$name] : NULL;
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
        $GLOBALS['app']['errors'][$field] = $error;
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

    public function getSession()
    {
        if ($this->session == null) {
            $this->session = new Session();
        }

        return $this->session;
    }

    public function getCookie($name)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
    }

    public function setCookie($name, $value, $period, $path = '/', $domain = null)
    {
        setcookie($name, $value, time() + $period, $path, $domain);
    }

    public function removeCookie($name)
    {
        setcookie($name, '', 0, '/');
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
}