<?php

namespace Watamelo\Framework\Http\Handler;

use Watamelo\Component\Http\Request;
use Watamelo\Framework\Http\Router\Route;

class HandlerInvoker implements HandlerInvokerInterface
{
    public function __construct() {}

    public function follow(Route $route, array $arguments, Request $httpRequest): void
    {
        $handler = $route->handler;
        $action = $route->action;

        if (is_string($handler)) {
            $classInstance = new $handler($httpRequest);
        } elseif (is_object($handler)) {
            $classInstance = $handler;
        } else {
            throw new \LogicException('Handler must be a class name or an instance');
        }
        // @todo action should probably return a Response object instead of echoing directly
        $classInstance->$action(...$arguments);
    }
}
