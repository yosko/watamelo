<?php

namespace Watamelo\Lib;

/**
 * Abstract class
 * base for every application components
 */
abstract class ApplicationComponent
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function app(): Application
    {
        return $this->app;
    }
}
