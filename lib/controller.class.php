<?php

namespace Watamelo\Controllers;

/**
 * Abstract class
 * Base for all controllers
 */
abstract class Controller extends \Watamelo\App\ApplicationComponent {
    protected $action = '';
    protected $parameters = array();
    protected $actions;

    /**
     * Execute the given action with the given parameters
     * @param  string $action     action name
     * @param  array  $parameters request parameters
     */
    public function execute($action, $parameters) {
        if(empty($action)) {
            $action = "index";
        }
        $this->setAction($action);
        $this->setParameters($parameters);

        //make the URL parameters accessible within the view
        $this->app()->view()->setReference( "parameters", $this->parameters );

        $method = 'execute'.ucfirst($this->action);

        $this->$method();
    }

    /**
     * Returns the response type for the current action
     * @return string the response type (base on "RESPONS_xxx" constants)
     */
    public function responseType() {
        if(isset($this->actions[$this->action]) && isset($this->actions[$this->action]['responseType'])) {
            $responseType = $this->actions[$this->action]['responseType'];
        } else {
            $responseType = RESPONSE_HTML;
        }
        return $responseType;
    }

    /**
     * Set the current action to the given action
     * @param string $action action name
     */
    public function setAction($action) {
        $this->action = $action;
    }

    /**
     * Set the request parameters
     * @param array $parameters request parameters
     */
    public function setParameters($parameters) {
        if(is_array($parameters))
            $this->parameters = $parameters;
        else
            $this->parameters = array();
    }

    /**
     * Default action
     */
    public function executeIndex() {}
}
