<?php

namespace Watamelo\Framework\Http\Router;

readonly class ResolvedRoute
{
    public function __construct(
        public Route $route,
        public array $arguments
    ) {}
}
