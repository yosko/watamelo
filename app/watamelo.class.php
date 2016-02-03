<?php
namespace Watamelo\App;

require_once(ROOT.'/app/ext/easydump.php');
require_once(ROOT.'/app/ext/yoslogin.lib.php');

/**
 * The application itself, called from the index.php and does everything else
 */
class Watamelo extends \Watamelo\Lib\Application
{
    protected $configManager;
    protected $authController;
    protected $user;
    protected $userLevels;
    protected $dbms;
    protected $dbParams;

    /**
     * Prepare the application
     */
    public function __construct($appName)
    {
        $this->useDefaultRoutes = true;
        $this->defaultControllerName = "general";

        parent::__construct($appName);

        //init config
        $this->configManager = $this->getManagerOf('config');

        //required to display anything
        $this->initView(
            $this->configManager->get('template'),
            $this->configManager->get('rootUrl'),
            $this->configManager->get('ApacheURLRewriting')
        );

        $this->dbms = $this->setDbms();
        $this->dbParams = $this->setDbParams();
        $this->dao = \Watamelo\Lib\DbFactory::getConnexion($this->dbms, $this->dbParams);
    }

    /**
     * Return the Database Management System name
     * @return string name of dbms in PDO style
     *                possible values: sqlite, mysql, postgresql
     */
    public function setDbms()
    {
        return 'sqlite';
    }

    /**
     * Gives the DBMS name
     * @return string dbms
     */
    public function dbms()
    {
        return $this->dbms;
    }

    /**
     * Return the Database Parameters (connection string)
     * @return StdClass parameters to connect to the database
     */
    public function setDbParams()
    {
        $params = new \StdClass;
        $params->dbName = $this->configManager->get('sessName').'.db';
        return $params;
    }

    /**
     * Gives the DB parameters
     * @return StdClass dbParams
     */
    public function dbParams()
    {
        return $this->dbParams;
    }

    /**
     * Run the application (call the proper controller and action)
     */
    public function run()
    {

        //prepare router
        $router = new \Watamelo\Lib\Router($this);
        $controllerName = "";
        $actionName = "";
        $parameters = array();
        $variables = array();
        $url = "";

        //if you don't use ApacheUrlRewriting, you can optionally define the name
        //of the $_GET parameter to use for your route
        // $router->setGetParamName('url');

        //example of part of a path that can be defined on the app level
        //here to avoid using an easy to find admin section
        $variables['admin'] = 'custom-admin-path';
        $url = $router->getUrl();

        //find route for the requested URL
        if (!$router->getRoute($controllerName, $actionName, $parameters, $url, $variables)) {

            //if route not found, redirect to a 404 error
            $controllerName = 'error';
            $actionName = '404';
        }

        //get user levels and add it to the view
        $userManager = $this->getManagerOf('user');
        $levels = $userManager->getLevels();
        foreach ($levels as $level) {
            $this->userLevels[$level->name] = (int)$level->level;
        }
        $this->view->setParam( "userLevels", $this->userLevels );

        //authenticate current user and get his/her/its informations
        $this->authController = new \Watamelo\Controllers\AuthController($this);
        $this->user = $this->authController->authenticateUser();

        //get controller corresponding to the user request
        $controller = $router->getController($controllerName);
        $controller->setAction($actionName);

        //if user should be authenticated, redirect him to the login page
        if ($this->user->level < $controller->userLevelNeeded()
            && $this->user->level < $this->userLevels['user']) {
            $controllerName = 'auth';
            $actionName = 'login';
            $controller = $router->getController($controllerName);

        //if the user is authenticated but doesn't have the right level of permission
        } elseif ($this->user->level < $controller->userLevelNeeded()) {
            $controllerName = 'error';
            $actionName = '403';
            $controller = $router->getController($controllerName);

        } elseif ($controller->secureNeeded() && !$this->user->secure) {
            $controllerName = 'auth';
            $actionName = 'secure';
            $controller = $router->getController($controllerName);
        }

        //add config last state to the view
        $this->view->setParam( "variables", $variables );
        $this->view->setParam( "user", $this->user );
        $this->view->setParam( "config", $this->configManager );

        //execute controller/action
        $controller->execute($actionName, $parameters);
    }

    /**
     * Return an error (can be called from within controllers)
     * @param  string $error error number (403, 404, etc...)
     */
    public function returnError($error="")
    {
        $router = new \Watamelo\Lib\Router($this);

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
    public function config()
    {
        return $this->configManager;
    }

    /**
     * Returns the application configuration
     * @return array config
     */
    public function auth()
    {
        return $this->authController;
    }

    /**
     * Return current user's information
     * @return array user information
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * Return list of user levels
     * @return array levels
     */
    public function userLevels()
    {
        return $this->userLevels;
    }
}
