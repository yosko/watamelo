<?php

namespace Watamelo\Framework\Http\Handler;

use Watamelo\Component\Http\Request;
use Watamelo\Framework\Http\Router\Route;

interface HandlerInvokerInterface
{
    public function follow(Route $route, array $arguments, Request $httpRequest): void;
}
