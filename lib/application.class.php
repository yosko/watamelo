<?php

require_once( ROOT.'/lib/ext/rain.tpl.class.php');

/**
 * https://github.com/GeorgeArgyros/Secure-random-bytes-in-PHP
 * used in tools, essentially for SessionManager and UserManager
 */
require_once( ROOT.'/lib/ext/srand.php');

/**
 * Abstract class
 * Main application, will be called from index.php
 */
abstract class Application {
    protected $appName = '';
    protected $view;
    protected $managers = null;
    protected $useDefaultRoutes = true;
    protected $defaultControllerName = "";

    public function __construct() {
        //handle errors and warnings
        if (DEVELOPMENT_ENVIRONMENT == true) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors','On');
        } else {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors','Off');
            ini_set('log_errors', 'On');
            ini_set('error_log', ROOT.'/tmp/logs/error.log');
        }
        
        $this->appName = get_called_class();
        $this->managers = new Managers('sqlite', strtolower($this->appName));
    }
    
    /**
     * Run the application (will call the right controller and action)
     */
    abstract public function run();
    
    /**
     * Initialise the View object
     */
    public function initView($template, $rootUrl, $ApacheURLRewriting) {
        $this->view = new View($this, $template, $rootUrl, $ApacheURLRewriting);
        $this->view->setParam( "isDevelopmentEnvironment", DEVELOPMENT_ENVIRONMENT );
        $this->view->setParam( "appVersion", VERSION );
    }
    
    /**
     * Returns the application name
     * @return string name
     */
    public function appName() {
        return $this->appName;
    }
    
    /**
     * Returns the application view
     * @return object view
     */
    public function view() {
        return $this->view;
    }
    
    /**
     * Returns the application managers (manager of managers)
     * @return object managers
     */
    public function managers() {
        return $this->managers;
    }
    
    /**
     * Returns the application flag "useDefaultRoutes"
     * @return boolean useDefaultRoutes
     */
    public function useDefaultRoutes() {
        return $this->useDefaultRoutes;
    }
    
    /**
     * Returns the application default controller name
     * @return string defaultControllerName
     */
    public function defaultControllerName() {
        return $this->defaultControllerName;
    }
}

?>