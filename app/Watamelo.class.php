<?php

namespace Watamelo\App;

use StdClass;
use Watamelo\Controllers\AuthController;
use Watamelo\Lib\Application;
use Watamelo\Lib\Controller;
use Watamelo\Lib\DbFactory;
use Watamelo\Lib\Manager;
use Watamelo\Lib\Router;
use Yosko\Loggable;

require_once(ROOT . '/app/ext/easydump.php');
require_once(ROOT . '/app/ext/yoslogin.lib.php');

/**
 * The application itself, called from the index.php and does everything else
 */
class Watamelo extends Application
{
    protected Manager $configManager;
    protected Controller $authController;
    protected ?Loggable $user = null;
    protected array $userLevels;
    protected string $dbms;
    protected StdClass $dbParams;
    private DataView $dataView;

    /**
     * Prepare the application
     * @param string $appName
     */
    public function __construct(string $appName)
    {
        $this->useDefaultRoutes = true;
        $this->defaultControllerName = "general";

        parent::__construct($appName);

        //init config
        $this->configManager = $this->manager('Config');

        //required to display anything
        $this->initView(
            $this->configManager->get('template'),
            $this->configManager->get('rootUrl'),
            $this->configManager->get('ApacheURLRewriting')
        );

        // Data managers encapsulator for the view
        $this->dataView = new DataView($this);
        $this->view->setParam('dataView', $this->dataView);

        $this->dbms = $this->setDbms();
        $this->dbParams = $this->setDbParams();
        $this->dao = DbFactory::getConnection($this->dbms, $this->dbParams);
    }

    /**
     * Return the Database Management System name
     * @return string name of dbms in PDO style
     *                possible values: sqlite, mysql, postgresql
     */
    public function setDbms(): string
    {
        return 'sqlite';
    }

    /**
     * Return the Database Parameters (connection string)
     * @return StdClass parameters to connect to the database
     */
    public function setDbParams(): StdClass
    {
        $params = new StdClass();
        $params->dbName = $this->configManager->get('sessName') . '.db';
        return $params;
    }

    /**
     * Gives the DBMS name
     * @return string dbms
     */
    public function dbms(): string
    {
        return $this->dbms;
    }

    /**
     * Gives the DB parameters
     * @return StdClass dbParams
     */
    public function dbParams(): StdClass
    {
        return $this->dbParams;
    }

    /**
     * Run the application (call the proper controller and action)
     */
    public function run()
    {

        //prepare router
        $router = new Router($this);
        $controllerName = "";
        $actionName = "";
        $parameters = array();
        $variables = array();
        //$url = "";

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
        $userManager = $this->manager('User');
        $levels = $userManager->getLevels();
        foreach ($levels as $level) {
            $this->userLevels[$level->name] = (int)$level->level;
        }
        $this->view->setParam("userLevels", $this->userLevels);

        //authenticate current user and get his/her/its information
        $this->authController = new AuthController($this);
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

        } elseif ($controller->secureNeeded() && !$this->user->isSecure()) {
            $controllerName = 'auth';
            $actionName = 'secure';
            $controller = $router->getController($controllerName);
        }

        //add config last state to the view
        $this->view->setParam("variables", $variables);
        $this->view->setParam("user", $this->user);
        $this->view->setParam("config", $this->configManager);

        //execute controller/action
        $controller->execute($actionName, $parameters);
    }

    /**
     * Return an error (can be called from within controllers)
     * @param string $error error number (403, 404, etc...)
     */
    public function returnError($error = "")
    {
        $router = new Router($this);

        $controllerName = "error";
        $actionName = $error;

        $controller = $router->getController($controllerName);

        //execute controller/action
        $controller->execute($actionName, array());
    }

    /**
     * Returns the application configuration
     * @return Manager config
     */
    public function config(): Manager
    {
        return $this->configManager;
    }

    /**
     * Returns the application configuration
     * @return Controller config
     */
    public function auth(): Controller
    {
        return $this->authController;
    }

    /**
     * Return current user's information
     * @return ?Loggable user information
     */
    public function user(): ?Loggable
    {
        return $this->user;
    }

    /**
     * Return list of user levels
     * @return array levels
     */
    public function userLevels(): array
    {
        return $this->userLevels;
    }

    public function dataView(): DataView
    {
        return $this->dataView;
    }
}
