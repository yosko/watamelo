<?php

namespace Yosko\Watamelo;

use DOMDocument;
use DOMNodeList;
use LogicException;

/**
 * Almighty powerful and wonderful routing class
 */
class Router extends ApplicationComponent
{
    protected DOMNodeList $routes;
    protected string $file = "";

    public function __construct(AbstractApplication $app)
    {
        parent::__construct($app);

        //load configuration file
        $this->file = ROOT . '/'.$this->app()->configPath().'/routes.xml';
        if (file_exists($this->file) == false) {
            throw new LogicException(sprintf('Configuration file "%s" not found', $this->file));
        }

        $root = new DOMDocument('1.0', 'UTF-8');
        $root->load($this->file);

        //no need to validate every time if in production environment
        if (DEVELOPMENT_ENVIRONMENT && !$root->validate()) {
            trigger_error("Failed to validate route definitions", E_USER_ERROR);
        }

        //only keep the route elements (and their children)
        $this->routes = $root->getElementsByTagName('route');
    }

    /**
     * Returns a route based requested URL
     * @param string $controller name of the found controller (if route found)
     * @param string $action name of the found action (if route found)
     * @param array $parameters array of correctly mapped parameters (if route found)
     * @param string $url meaningful part of the url
     * @param array $variables array of variable parts of path defined on app level
     * @return bool            true if a route was found
     */
    public function getRoute(string &$controller, string &$action, array &$parameters, string &$url, array $variables): bool
    {
        if (is_null($url)) {
            $url = $this->getUrl();
        }

        $foundRoute = false;

        //check for a predefined route
        foreach ($this->routes as $route) {
            if ($route->nodeType != XML_TEXT_NODE) {
                $required = array();

                //handle parameter types
                $regexp = preg_replace_callback(
                    "/:(\w+)\|(\w+):/",
                    function ($matches) use (&$required) {

                        $required[] = $matches[2];

                        //handle parameter type
                        if ($matches[1] == 'int') {
                            return '(\d+)';
                        } elseif ($matches[1] == 'string') {
                            return '(.+)';
                        } else {
                            return $matches[0];
                        }
                    },
                    str_replace('.', '\.', $route->getAttribute('path'))
                );

                //handle variable parts of path
                $regexp = preg_replace_callback(
                    "/%(\w+)%/",
                    function ($matches) use ($variables) {
                        return $variables[$matches[1]];
                    },
                    $regexp
                );

                //match route including required parameters
                if (preg_match('%^' . $regexp . '(/.*)?$%i', $url, $matches)) {
                    $foundRoute = true;
                    $parameters = array();
                    $optional = '';
                    $controller = $route->getAttribute('controller');
                    $action = $route->getAttribute('action');

                    //remove unnecessary match
                    unset($matches[0]);
                    //pop optional parameters if they exists
                    if (count($matches) > count($required)) {
                        $optional = array_pop($matches);
                    }
                    //combine required parameter values and names
                    if (!empty($required)) {
                        $parameters = array_combine($required, $matches);
                    }

                    //handle additional parameters (constants given via the route definition)
                    $additionalParameters = $route->getElementsByTagName('additional');
                    foreach ($additionalParameters as $param) {
                        $parameters[$param->getAttribute('name')] = $param->getAttribute('value');
                    }

                    //handle optional parameters
                    $optionalParameters = $route->getElementsByTagName('optional');
                    if (!empty($optional)) {
                        //if there's a "/" between required & optional part
                        if ($optional[0] == "/") {
                            $optional = preg_split('%\|%', trim($optional, '/?'));
                            if (count($optional) == 1 && empty($optional[0])) {
                                unset($optional[0]);
                            }
                            $nbOptParam = $optionalParameters->length;

                            if (count($optional) <= $nbOptParam) {
                                //match the remaining ones to optional parameters
                                for ($i = 0; $i < $nbOptParam; $i++) {
                                    if (isset($optional[$i])) {
                                        $parameters[$optionalParameters->item($i)->getAttribute('name')] = $optional[$i];
                                    }
                                }
                            } else {
                                //route don't really match (maybe has a longer path than the)
                                $foundRoute = false;
                            }
                        } else {
                            $foundRoute = false;
                        }
                    }
                }
            }
        }

        //add other parameters given after '?' in a subarray
        $parameters['get'] = array();
        foreach ($_GET as $key => $getParam) {
            if ($key != 'url') {
                $parameters['get'][$key] = $getParam;
            }
        }

        //return true if route found
        return $foundRoute;
    }

    public function getUrl(): string
    {
        //remove '/' at beginning & end of the url
        if (isset($_GET[$this->app()->getParamName()])) {
            $url = trim($_GET[$this->app()->getParamName()], "/");
        } else {
            $url = "";
        }

        return $url;
    }

    /**
     * Returns a controller based on its name
     * @param string $controllerName controller name
     * @return object                 controller
     */
    public function getController(string $controllerName): object
    {
        $classname = ucfirst($controllerName) . 'Controller';
        if(substr($controllerName, 0,1) !== '\\') {
            $classname = '\\'.$this->app()->getConcreteNamespace().'\\Controllers\\' . $classname;
        }

        return new $classname($this->app());
    }
}
