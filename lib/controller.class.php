<?php

/**
 * Abstract class
 * Base for all controllers
 */
abstract class Controller extends ApplicationComponent {
    protected $action = '';
    protected $parameters = array();
    protected $userLevels;
    protected $actions;
    // protected $actions = array(
    //     "" => array(
    //         "authNeeded" => false,
    //         "secureNeeded" => false,
    //         "adminNeeded" => false,
    //         "responseType" => RESPONSE_HTML
    //     )
    // );

    public function __construct(Application $app) {
        parent::__construct($app);
        $this->userLevels = $this->app->userLevels();
    }

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

        $method = 'execute'.ucfirst($this->action);

        $this->$method();
    }
    
    /**
     * Return the user level required for the current action
     * Based on the $userLevels array defined in application, from database
     * @return integer the minimum user level required for this action
     */
    public function userLevelNeeded() {
        if(isset($this->actions[$this->action]) && isset($this->actions[$this->action]['level'])) {
            $level = $this->actions[$this->action]['level'];
        } else {
            $level = $this->userLevels['visitor'];
        }
        return $level;
    }

    /**
     * Check wether the current action needs a secure authentication
     * @return boolean true if action needs a secure authentication
     *                 false by default
     */
    public function secureNeeded() {
        if(isset($this->actions[$this->action]) && isset($this->actions[$this->action]['secureNeeded'])) {
            $secureNeeded = $this->actions[$this->action]['secureNeeded'];
        } else {
            $secureNeeded = false;
        }

        return $secureNeeded;
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
    abstract function executeIndex();
}

?>