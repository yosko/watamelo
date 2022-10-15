<?php

namespace Yosko\Watamelo;

/**
 * Abstract class
 * base for every application components
 */
abstract class AbstractComponent
{
    protected AbstractApplication $app;

    public function __construct(AbstractApplication $app)
    {
        $this->app = $app;
    }

    public function app(): AbstractApplication
    {
        return $this->app;
    }
}
