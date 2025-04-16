<?php

namespace Watamelo\Framework\Http\Router;

class ResolvedRoute
{
    public $route;
    public $arguments;

    public function __construct(Route $route, array $arguments)
    {
        $this->route = $route;
        $this->arguments = $arguments;
    }
}
