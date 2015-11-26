<?php

namespace Xplosio\PhpFramework;

abstract class RouterController extends Controller
{
    public function handle()
    {
        $routes = $this->getRoutes();
        if ($routes !== null) {
            list($method, $params) = App::route($this->request->getUri(), $routes);
            if (is_callable(array($this, $method))) {
                return call_user_func_array(array($this, $method), $params);
            }
        }

        throw new \InvalidArgumentException('Handler not found', 404);
    }

    abstract protected function getRoutes();
}
