<?php

define('WATAMELO_VERSION', '0.7');

/**
 * Abstract class
 * Main application, will be called from index.php
 */
abstract class Application {
    protected $appName = '';
    protected $view;
    protected $useDefaultRoutes = true;
    protected $defaultControllerName = "";
    protected $dao = null;
    protected $managers = array();
    protected $getParamName;


    public function __construct($appName = '') {
        //handle errors and warnings
        error_reporting(E_ALL | E_STRICT);
        if (DEVELOPMENT_ENVIRONMENT == true) {
            ini_set('display_errors','On');
        } else {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors','Off');
        }
        ini_set('log_errors', 'On');
        ini_set('error_log', ROOT.'/tmp/logs/error.log');
        
        $this->getParamName = 'url';
        $this->appName = empty($appName)?get_called_class():$appName;
    }
    
    /**
     * Run the application (will call the right controller and action)
     */
    abstract public function run();

    /**
     * Returns a manager (loads it if not already loaded)
     * @param  string $module manager name (case insensitive)
     * @return object         manager
     */
    public function getManagerOf($module) {
        if (!is_string($module) || empty($module)) {
            throw new InvalidArgumentException('Invalid module');
        }
        
        if (!isset($this->managers[$module])) {
            $manager = $module.'manager';
            $this->managers[$module] = new $manager($this, $this->dao);
        }
        
        return $this->managers[$module];
    }
    
    /**
     * Initialise the View object
     */
    public function initView($template, $rootUrl, $ApacheURLRewriting) {
        $this->view = new View($this, $template, $rootUrl, $ApacheURLRewriting);
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
     * Returns the application parameter name used in $_GET
     * @return string name
     */
    public function getParamName() {
        return $this->getParamName;
    }
    
    /**
     * Set the application parameter name used in $_GET
     */
    public function setGetParamName($getParamName) {
        $this->getParamName = $getParamName;
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