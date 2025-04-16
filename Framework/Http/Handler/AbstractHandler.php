<?php

namespace Watamelo\Framework\Http\Handler;

use Watamelo\Component\Http\Request;

/**
 * Base for all HTTP request handlers
 */
abstract class AbstractHandler
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
