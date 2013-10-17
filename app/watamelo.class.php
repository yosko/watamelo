<?php

require_once( ROOT.'/lib/autoload.php');
require_once( ROOT.'/app/tools.class.php');
/**
 * The application itself, called from the index.php and does everything else
 */
class Watamelo extends Application {
    protected $configManager, $user, $userLevels;
    
    /**
     * Prepare the application
     */
    public function __construct() {
        $this->useDefaultRoutes = true;
        $this->defaultControllerName = "general";

        parent::__construct();

        //init config
        $this->configManager = $this->managers->getManagerOf('config');

        //required to display anything
        $this->initView(
            $this->configManager->get('template'),
            $this->configManager->get('rootUrl'),
            $this->configManager->get('ApacheURLRewriting')
        );

        //get user levels and add it to the view
        $userManager = $this->managers->getManagerOf('user');
        $levels = $userManager->getLevels();
        foreach($levels as $level) {
            $this->userLevels[$level['name']] = (int)$level['level'];
        }
        $this->view->setParam( "userLevels", $this->userLevels );
    }

    /**
     * Run the application (call the proper controller and action)
     */
    public function run() {
        //authenticate current user and get his/her/its informations
        $authController = new AuthController($this);
        $this->user = $authController->authenticateUser();
        
        //prepare router
        //note: you can replace the call to ApacheURLRewriting by a boolean value
        //to indicate if you can/want to use URL rewriting or not
        $router = new Router($this, $this->configManager->get('ApacheURLRewriting'));
        $controllerName = "";
        $actionName = "";
        $parameters = array();
        
        //find route for the requested URL
        if(!$router->getRoute($controllerName, $actionName, $parameters)) {

            //if route not found, redirect to a 404 error
            Tools::log('404');

            $controllerName = 'error';
            $actionName = '404';
        }
        
        //get controller corresponding to the user request
        $controller = $router->getController($controllerName);
        $controller->setAction($actionName);

        //add config last state to the view
        $this->view->setParam( "config", $this->configManager->getAll() );
        
        //execute controller/action
        $controller->execute($actionName, $parameters);
    }

    /**
     * Return an error (can be called from within controllers)
     * @param  string $error error number (403, 404, etc...)
     */
    public function returnError($error="") {
        $router = new Router($this);

        Tools::log($error);

        $controllerName = "error";
        $actionName = $error;

        $controller = $router->getController($controllerName);
        
        //execute controller/action
        $controller->execute($actionName, array());
    }

    /**
     * Returns the application configuration
     * @return array config
     */
    public function config() {
        return $this->configManager;
    }
    
    /**
     * Return current user's information
     * @return array user information
     */
    public function user() {
        return $this->user;
    }

    /**
     * Return list of user levels
     * @return array levels
     */
    public function userLevels() {
        return $this->userLevels;
    }
}

?>