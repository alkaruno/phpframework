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

    abstract public function handle();

    protected function loadModule($module)
    {
        //Dispatcher::loadConfig($module);
        require_once '../app/lib/' . $module . '/_autoload.php';
    }
}