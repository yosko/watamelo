<?php

namespace Yosko\Watamelo;

class ExecutableRoute
{
    public $route;
    public $arguments;
    public $httpRequest;

    public function __construct(Route $route, array $arguments, HttpRequest $httpRequest)
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
