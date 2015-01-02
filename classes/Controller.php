<?php

namespace Xplosio\PhpFramework;

abstract class Controller
{
    /**
     * @var Request
     */
    protected $request;

    public function __construct(Request $request)
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

    public function handle()
    {
        return null;
    }

    public function getRequest()
    {
        return $this->request;
    }
}