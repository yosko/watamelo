<?php

namespace Yosko\Watamelo;

/**
 * Base for all controllers
 */
abstract class AbstractController extends AbstractComponent
{
    protected string $action = '';
    protected array $parameters = [];
    protected array $actions;

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
        $this->setAction($action);
        $this->setParameters($parameters);

        //make the URL parameters accessible within the view
        $this->app()->view()->setReference("parameters", $this->parameters);

        $method = 'execute' . ucfirst($this->action);

        $this->$method();
    }

    /**
     * Set the current action to the given action
     * @param string $action action name
     */
    public function setAction(string $action)
    {
        $this->action = $action;
    }

    /**
     * Set the request parameters
     * @param array $parameters request parameters
     */
    public function setParameters(array $parameters)
    {
        if (is_array($parameters)) {
            $this->parameters = $parameters;
        } else {
            $this->parameters = array();
        }
    }

    /**
     * Returns the response type for the current action
     * @return string the response type (base on "RESPONSE_xxx" constants)
     */
    public function responseType(): string
    {
        if (isset($this->actions[$this->action]) && isset($this->actions[$this->action]['responseType'])) {
            $responseType = $this->actions[$this->action]['responseType'];
        } else {
            $responseType = RESPONSE_HTML;
        }
        return $responseType;
    }

    /**
     * Default action
     */
    public function executeIndex()
    {
    }
}
