<?php

namespace Yosko\Watamelo;

use LogicException;
use ReflectionClass;
use RuntimeException;

/**
 * Almighty powerful and wonderful routing class
 */
class Router extends AbstractComponent
{
    protected string $routeParamName;
    protected array $routes;
    protected string $file;

    public const METHOD_GET = 'GET';
    public const METHOD_HEAD = 'HEAD';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_CONNECT = 'CONNECT';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_TRACE = 'TRACE';
    public const METHOD_PATCH = 'PATCH';

    public function __construct(AbstractApplication $app)
    {
        parent::__construct($app);

        $this->routes = [];
        $this->routeParamName = $app->routeParamName();
        $this->file = '';
    }

    public function map(string $url, string $controller, string $action = 'index', string $method = 'GET'): Route
    {
        $route = new Route($url, $controller, $action, $method);
        $this->routes[] = $route;
        return $route;
    }

    public function __call(string $name, array $arguments): Route
    {
        $reflection = new ReflectionClass($this);
        $constants = $reflection->getConstants();

        // if class method called is an HTTP method: call map()
        if (isset($constants['METHOD_'.strtoupper($name)])) {
            $arguments[] = strtoupper($name);
            return $this->map(...$arguments);
        } else {
            throw new LogicException(sprintf('Unknown method %s.', $name));
        }
    }

    /**
     * Returns a route based requested URL
     * @param string $url meaningful part of the url
     * @param array $variables customizable parts of the URL
     */
    public function findRoute(?string $url = null, array $variables = []): ?Route
    {
        $foundRoute = null;
        $parameters = [];

        if (is_null($url)) {
            $url = $this->getUrl();
        }

        //check for a predefined route
        foreach ($this->routes as $route) {
            $parameters = $this->matchRoute($url, $route, $variables);
            if (!is_null($parameters)) {
                $foundRoute = $route;
                $foundRoute->requestParams = $parameters;
            }
        }

        //add other parameters given after '?' in a subarray
        $get = $_GET;
        unset($get[$this->routeParamName]);
        if (empty($get) === false) {
            $foundRoute->requestParams['get'] = [];
            foreach ($_GET as $key => $getParam) {
                if ($key != $this->routeParamName) {
                    $foundRoute->requestParams['get'][$key] = $getParam;
                }
            }
        }

        //return found route and parameters
        return $foundRoute;
    }

    public function getUrl(): string
    {
        // base URL as interpreted by this router
        // (must always start with a "/")
        $url = "/";

        //remove '/' at the end of the url
        if (isset($_GET[$this->routeParamName])) {
            $url .= trim($_GET[$this->routeParamName], "/");
        }

        return $url;
    }

    public function matchRoute(string $url, Route $route, array $variables): ?array
    {
        $requiredParams = [];

        //handle parameter types
        $regexp = preg_replace_callback(
            "/:(\w+)\|(\w+):/",
            function ($matches) use (&$requiredParams) {

                $requiredParams[] = $matches[2];

                //handle parameter type
                if ($matches[1] == 'int') {
                    return '(\d+)';
                } elseif ($matches[1] == 'string') {
                    return '(.+)';
                } else {
                    return $matches[0];
                }
            },
            str_replace('.', '\.', $route->url)
        );

        //handle variable parts of path
        $regexp = preg_replace_callback(
            "/%(\w+)%/",
            function ($matches) use ($variables) {
                return $variables[$matches[1]];
            },
            $regexp
        );

        //route doesn't match when including required parameters
        if (preg_match('%^' . $regexp . '(/.*)?$%i', $url, $matches) == false) {
            return null;
        }

        $parameters = [];
        $optionalParamsGiven = '';
        $controller = $route->controller;
        $action = $route->action;

        //remove unnecessary match
        unset($matches[0]);
        //pop optional parameters if they exists
        if (count($matches) > count($requiredParams)) {
            $optionalParamsGiven = array_pop($matches);
        }
        //combine required parameter values and names
        if (!empty($requiredParams)) {
            $parameters = array_combine($requiredParams, $matches);
        }

        //handle additional parameters (constants given via the route definition)
        $parameters = array_merge($parameters, $route->additionalParams);

        //handle optional parameters
        if (!empty($optionalParamsGiven)) {
            //if there's no "/" between required & optional part
            if ($optionalParamsGiven[0] !== "/") {
                return null;
            }

            $optionalParamsGiven = preg_split('%\|%', trim($optionalParamsGiven, '/?'));
            if (count($optionalParamsGiven) == 1 && empty($optionalParamsGiven[0])) {
                unset($optionalParamsGiven[0]);
            }
            $nbOptParam = count($route->optionalParams);

            //route don't really match (maybe has a longer path than the model)
            if (count($optionalParamsGiven) > $nbOptParam) {
                return null;
            }

            //match the remaining ones to optional parameters
            $i = 0;
            foreach ($route->optionalParams as $name => $value) {
                if (isset($optionalParamsGiven[$i])) {
                    $parameters[$name] = $optionalParamsGiven[$i];
                }
                $i++;
            }
        }

        return $parameters;
    }

    /**
     * Returns a controller based on its name
     * @param string $controllerName controller name
     * @return AbstractController controller
     */
    public function getController(string $controllerName): AbstractController
    {
        return new $controllerName($this->app);
    }
}
