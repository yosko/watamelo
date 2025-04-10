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
        $handler = $this->route->handler;
        $action = $this->route->action;

        if (is_string($handler)) {
            $classInstance = new $handler($this->httpRequest);
        } elseif (is_object($handler)) {
            $classInstance = $handler;
        } else {
            throw new \LogicException('Handler must be a class name or an instance');
        }
        $classInstance->$action(...$this->arguments);
    }
}
