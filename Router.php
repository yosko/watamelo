<?php

namespace Yosko\Watamelo;

use BadMethodCallException;
use LogicException;
use RuntimeException;
use Yosko\Watamelo\Http\Methods;
use Yosko\Watamelo\Http\Request;

/**
 * Almighty powerful and wonderful routing class
 */
class Router
{
    protected array $routes;
    protected Route $defaultRoute;
    protected string $file;
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->routes = [];
        $this->file = '';
    }

    public function map(
        string $method,
        string $url,
        string|object $handler,
        string $action = 'index',
        array $additional = [],
        array $optional = []
    ): Route {
        $route = new Route($method, $url, $handler, $action, $additional, $optional);
        $this->routes[] = $route;
        return $route;
    }

    public function __call(string $name, array $arguments): Route
    {
        $method = strtoupper($name);
        if (in_array($method, array_column(Methods::cases(), 'name'))) {
            return $this->map($method, ...$arguments);
        } else {
            throw new BadMethodCallException("Unsupported HTTP method: {$method}");
        }
    }

    public function mapDefault(string|object $handler, string $action = 'index'): Route
    {
        $this->defaultRoute = new Route('', '', $handler, $action);
        return $this->defaultRoute;
    }

    public function dispatch(): void
    {
        $route = $this->findRoute();
        $route->follow();
    }

    /**
     * Returns a route based requested URL
     * @param string $url meaningful part of the url
     */
    public function findRoute(?string $url = null): ExecutableRoute
    {
        $foundRoute = null;
        $parameters = [];

        if (is_null($url)) {
            $url = $this->request->getPath();
        }
        $method = $_SERVER['REQUEST_METHOD'];

        //check for a predefined route
        foreach ($this->routes as $route) {
            $parameters = $this->matchRoute($method, $url, $route);
            if (!is_null($parameters)) {
                $foundRoute = $route;
                // TODO: let Request handle most of the parameters?
                $foundRoute->foundParams = $parameters;
            }
        }

        if (is_null($foundRoute)) {
            $foundRoute = $this->defaultRoute;
        }
        if (is_null($foundRoute)) {
            throw new \RuntimeException('no available route found for this URL');
        }

        // arguments destined to the action method
        $arguments = $foundRoute->foundParams;

        //return found route and parameters
        return new ExecutableRoute($foundRoute, $arguments, $this->request);
    }

    public function matchRoute(string $method, string $url, Route $route): ?array
    {
        if ($method != $route->method) {
            return null;
        }

        //route doesn't match when including required parameters
        if (preg_match('%^' . $route->urlRegexp . '(/.*)?$%i', $url, $matches) == false) {
            return null;
        }

        $parameters = [];
        $optionalParamsGiven = '';

        //remove unnecessary match
        unset($matches[0]);

        //pop optional parameters if they exists
        if (count($matches) > count($route->requiredParams)) {
            $optionalParamsGiven = array_pop($matches);
        }
        //combine required parameter values and names
        if (!empty($route->requiredParams)) {
            $parameters = array_combine($route->requiredParams, $matches);
        }

        //check required parameters
        $types = $route->requiredParamsTypes();
        foreach ($route->requiredParams as $requiredParam) {
            if (!isset($types[$requiredParam])) {
                throw new LogicException(sprintf('Required URL parameter "%s" not found in method "%s"\'s arguments', $requiredParam, $route->action));
            }

            $invalidValue = false;
            switch ($types[$requiredParam]) {
                case 'string':
                    // nothing special to do: this case always works
                    break;
                case 'int':
                    if (ctype_digit($parameters[$requiredParam]))
                        $parameters[$requiredParam] = (int)$parameters[$requiredParam];
                    else
                        $invalidValue = true;
                    break;
                case 'float':
                    if (is_numeric($parameters[$requiredParam]))
                        $parameters[$requiredParam] = (float)$parameters[$requiredParam];
                    else
                        $invalidValue = true;
                    break;
                case 'bool':
                    $filteredValue = filter_var($parameters[$requiredParam], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if (is_null($filteredValue))
                        $invalidValue = true;
                    else
                        $parameters[$requiredParam] = $filteredValue;
                    break;
                // case 'enum':
                //     if ()
                //     break;

                default:
                    throw new LogicException(sprintf('Unsupported "%s" type for required URL parameter "%s" (defined in method "%s")', $types[$requiredParam], $requiredParam, $route->action));
                    break;
            }

            if ($invalidValue) {
                throw new RuntimeException(sprintf('Invalid value "%s" for required parameter "%s" of type "%s"', $parameters[$requiredParam], $requiredParam, $types[$requiredParam]));
            }
        }

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

        //handle additional parameters (constants given via the route definition)
        $parameters = array_merge($parameters, $route->additionalParams);

        return $parameters;
    }
}
