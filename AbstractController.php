<?php

namespace Yosko\Watamelo;

use Yosko\Watamelo\Http\Request;

/**
 * Base for all controllers
 */
abstract class AbstractController
{
    protected string $action = '';
    protected array $parameters = [];
    protected array $actions;
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Execute the given action with the given parameters
     * @param string $action action name
     * @param array $parameters request parameters
     */
    public function execute(string $action, array $parameters)
    {
        if (empty($action)) {
            $action = "index";
        }

        // remember action and parameters
        $this->action = $action;
        // TODO: still use $this->parameters?
        $this->parameters = is_array($parameters) ? $parameters : [];

        // execute action
        $this->$action(...$parameters);
    }

    /**
     * Default action
     */
    public function index()
    {
    }
}
