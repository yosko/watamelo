<?php

define( 'RESPONSE_CSV', 'csv' );
define( 'RESPONSE_FILE', 'file' );
define( 'RESPONSE_HTML', 'html' );
define( 'RESPONSE_IMG', 'img' );
define( 'RESPONSE_JSON', 'json' );
define( 'RESPONSE_MAIL', 'mail' );

/**
 * View manager
 * Class that handle the output of the application
 */
class View extends ApplicationComponent {
    protected $params;
    protected $baseUrl;
    protected $templateName;
    protected $template;
    protected $templateUrl;
    protected $ApacheURLRewriting;

    public function __construct(Application $app, $template, $rootUrl, $ApacheURLRewriting) {
        parent::__construct($app);

        $this->params = array();
        $this->rootUrl = $rootUrl;
        $this->templateName = $template;
        $this->ApacheURLRewriting = $ApacheURLRewriting;
        
        //RainTPL config
        if($this->templateName===false) { $this->templateName = "default"; }
        raintpl::configure("base_url", null );
        raintpl::configure("tpl_dir", "tpl/".$this->templateName."/" );
        raintpl::configure("cache_dir", "tmp/cache/" );
        raintpl::configure("path_replace", false );
        $this->template = new RainTPL;
        
        if($this->rootUrl === false) {
            $this->rootUrl = 'http://'.$_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']).'/';
        }
        
        $this->templateUrl = $this->rootUrl.'tpl/'.$this->templateName.'/';

        //if there is no URL Rewriting, the route will be put in the $_GET['p']
        $this->baseUrl = $this->rootUrl;
        $this->baseUrl .= (!$this->ApacheURLRewriting)?'?p=':'';

        $this->setParam( "templateUrl", $this->templateUrl );
        $this->setParam( "rootUrl", $this->rootUrl );
        $this->setParam( "baseUrl", $this->baseUrl );
    }

    /**
     * Returns the raintpl template
     * @return object template
     */
    public function template() {
        return $this->template;
    }

    /**
     * Return the currently used root url
     * @return string url
     */
    public function rootUrl() {
        return $this->baseUrl;
    }

    /**
     * Return the currently used base url
     * @return string url
     */
    public function baseUrl() {
        return $this->baseUrl;
    }

    /**
     * Returns the raintpl template
     * @return object template
     */
    public function templateUrl() {
        return $this->templateUrl;
    }

    /**
     * Add or update a parameter to the view
     * @param string $name  parameter name
     * @param misc   $value parameter value
     */
    public function setParam($name, $value) {
        $this->params[$name] = $value;
    }

    /**
     * Get a parameter asigned to the view
     * @param  string $name  parameter name
     * @return misc          parameter value
     */
    public function getParam($name) {
        return $this->params[$name];
    }

    /**
     * Assign all parameters to the view, then render it
     * @param string  $name         view name
     * @param boolean $directResult false to get result in the return
     */
    public function renderView($name, $directResult = true) {
        foreach($this->params as $key => $value) {
            $this->template->assign($key, $value);
        }

        ob_start();

        $this->template->draw($name);

        $response = ob_get_clean();

        if($directResult) {
            echo $response;
            exit;
        } else {
            return $response;
        }
    }

    /**
     * Render a rss/atom feed with the given falues
     * @param string $feed values for feed items
     * @param string $type rss (default) or atom
     */
    public function renderFeed($feed, $type='rss') {
        $viewName = ($type == 'atom')?'atom':'rss';

        //the view is defined on framework level
        raintpl::configure("tpl_dir", "lib/views/" );

        header('Content-Type: text/xml');

        //TODO: basic check on $feed

        //TODO: order by date desc

        $this->setParam('feed', $feed);
        $this->renderView($viewName);
    }

    /**
     * Send a data response with the given data and response type
     * @param  array  $data         the data to return
     * @param  string $responseType the type of response to use
     * @param  array  $options      possible options:
     *                              - 'fileName'='filname.extension' to make it a downloadable file
     *                              - 'header'=false to hide CSV header line
     */
    public function renderData($data, $responseType = RESPONSE_JSON, $options = array()) {
        ob_start();

        //format response and headers
        if($responseType == RESPONSE_JSON) {
            header('Content-type: application/json');
            echo json_encode($data);
        } elseif($responseType == RESPONSE_CSV) {
            header('Content-type: text/csv');

            $header = array();
            foreach($data as $key => $row) {
                //headers
                if(empty($header)) {
                    $header = array_keys($row);
                    if(!isset($options['header']) || $options['header'] !== false) {
                        echo implode(",", $header)."\n";
                    }
                }
                
                $result='';
                foreach($row as $key => $value) {
                    if(is_numeric($value)) {
                        $result .= $value.',';
                    } else {
                        $result .= '"'.str_replace( '"', '\"', htmlspecialchars_decode($row[$key]) ).'"';
                    }
                }
                
                $result = rtrim($result, ',')."\n";
                echo $result;
            }
        }

        $response = ob_get_clean();
        
        if(isset($options['fileName'])) {
            header('Content-disposition: attachment; filename='.$options['fileName']);
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        echo $response;
    }

    /**
     * Assaign all parameters to the view and returns its content
     * @param  string $name view name
     * @return string       view html content
     */
    public function renderMail($name) {
        return $this->renderView($name, false);
    }
}

?>