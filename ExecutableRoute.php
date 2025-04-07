<?php

namespace Yosko\Watamelo;

use Yosko\Watamelo\Http\Request;

class ExecutableRoute
{
    public $route;
    public $arguments;
    public Request $httpRequest;

    public function __construct(Route $route, array $arguments, Request $httpRequest)
    {
        $this->route = $route;
        $this->arguments = $arguments;
        $this->httpRequest = $httpRequest;
    }

    public function follow() {
        $className = $this->route->class;
        $action = $this->route->action;

        $classInstance = new $className($this->httpRequest);
        $classInstance->$action(...$this->arguments);
    }
}
