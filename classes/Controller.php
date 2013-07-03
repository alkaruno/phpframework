<?php

abstract class Controller
{
    /**
     * @var Request
     */
    protected $request;

    function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function set($name, $value)
    {
        $this->request->set($name, $value);
    }

    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    public function handle() {
        ;
    }
}