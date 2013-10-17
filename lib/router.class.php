<?php

/**
 * Allmighty powerfull and wonderfull routing class
 */
class Router extends ApplicationComponent {
    protected $routes = array();
    protected $file = "";
    protected $useApacheURLRewriting;
    
    public function __construct(Application $app, $useApacheURLRewriting = true) {
        parent::__construct($app);

        $this->useApacheURLRewriting = $useApacheURLRewriting;
        
        //load configuration file
        // $this->file = ROOT.'/data/config/routes.json';
        // if (file_exists( $this->file )) {
        //     $this->routes = json_decode(file_get_contents($this->file), true);
        // }
        $this->file = ROOT.'/data/config/routes.xml';
        if (file_exists( $this->file )) {
            $root = new DOMDocument('1.0', 'utf-8');
            $root->load( $this->file );
            $this->routes = $root->childNodes->item(0)->childNodes;
        }
    }
    
    /**
     * Returns a route based requested URL
     * @param  string $controller name of the found controller (if route found)
     * @param  string $action     name of the found action (if route found)
     * @param  array  $parameters array of corectly mapped parameters (if route found)
     * @return boolean            true if a route was found
     */
    public function getRoute(&$controller, &$action, &$parameters) {
        //remove '/' at beginning & end of the url
        if($this->useApacheURLRewriting && isset($_GET['url']))
            $url = trim($_GET['url'],"/");
        elseif(!$this->useApacheURLRewriting && isset($_GET['p']))
            $url = trim($_GET['p'],"/");
        else
            $url = "";
        
        $foundRoute = false;
        $remainingUrl = "";

        //check for a predifined route
        foreach ($this->routes as $route) {
            if ($route->nodeType != XML_TEXT_NODE) {
                $required = array();
                $regexp = preg_replace_callback(
                    "/:(\w+)\|(\w+):/",
                    function ($matches) use (&$required) {

                        $required[] = $matches[2];

                        //handle parameter type
                        if($matches[1] == 'int') {
                            return '(\d+)';
                        } elseif($matches[1] == 'string') {
                            return '(.+)';
                        } else {
                            return $matches[0];
                        }
                    },
                    $route->getAttribute('path')
                );

                //match route including required parameters
                if( preg_match("%^".$regexp."(.*)$%i", $url, $matches) ) {
                    $foundRoute = true;
                    $parameters = array();
                    $controller = $route->getAttribute('controller');
                    $action = $route->getAttribute('action');

                    //build parameters array. The subarray 'optional' contains optional parameters
                    unset($matches[0]);
                    $optional = array_pop($matches);
                    if(!empty($required)) {
                        $parameters = array_combine($required, $matches);
                    }

                    //handle additional parameters (constants given via the route definition)
                    $additionalParameters = $route->getElementsByTagName('additional');
                    foreach($additionalParameters as $param) {
                        $parameters[$param->getAttribute('name')] = $param->getAttribute('value');
                    }

                    //handle optional parameters
                    $optionalParameters = $route->getElementsByTagName('optional');
                    if(!empty($optional)) {
                        //if there's a "/" between required & optional part
                        if($optional{0} == "/") {
                            $optional = preg_split('%\|%', trim($optional, '/?'));
                            if(count($optional) == 1 && empty($optional[0])) {
                                unset($optional[0]);
                            }
                            $nbOptParam = $optionalParameters->lenght;
                            
                            if(count($optional) <= $nbOptParam) {
                                //match the remaining ones to optional parameters
                                for($i = 0; $i < $nbOptParam; $i++) {
                                    if(isset($optional[$i])) {
                                        $parameters[$optionalParameters->item($i)] = $optional[$i];
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
        foreach($_GET as $key => $getParam) {
            if($key != 'url') {
                $parameters['get'][$key] = $getParam;
            }
        }
        
        //return true if route found
        return $foundRoute;
    }

    /**
     * Returns a controller based on its name
     * @param  string $controllerName controller name
     * @return object                 controller
     */
    public function getController($controllerName) {
        $classname = ucfirst($controllerName).'Controller';
        $controller = new $classname($this->app());
        return $controller;
    }
}

?>