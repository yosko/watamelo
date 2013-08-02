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

    public function __construct(Application $app, $template, $baseUrl) {
        parent::__construct($app);

        $this->params = array();
        $this->baseUrl = $baseUrl;
        $this->templateName = $template;
        
        //RainTPL config
        if($this->templateName===false) { $this->templateName = "default"; }
        raintpl::configure("base_url", null );
        raintpl::configure("tpl_dir", "tpl/".$this->templateName."/" );
        raintpl::configure("cache_dir", "tmp/cache/" );
        raintpl::configure("path_replace", false );
        $this->template = new RainTPL;
        
        if($this->baseUrl === false) {
            $this->baseUrl = 'http://'.$_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']).'/';
            $this->setParam( "baseUrl", $this->baseUrl );
            $this->setParam( "templateUrl", $this->baseUrl.'tpl/'.$this->templateName.'/' );
        }
    }

    /**
     * Returns the raintpl template
     * @return object template
     */
    public function template() {
        return $this->template;
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
     * Return the currently used base url
     * @return string url
     */
    public function baseUrl() {
        return $this->baseUrl;
    }

    /**
     * Assign all parameters to the view, then render it
     * @param string $name view name
     */
    public function renderView($name) {
        foreach($this->params as $key => $value) {
            $this->template->assign($key, $value);
        }

        ob_start();

        $this->template->draw($name);

        $response = ob_get_clean();
        echo $response;
        exit;
    }

    /**
     * Send a data response with the given data and response type
     * @param  array  $data         the data to return
     * @param  string $responseType the type of response to use
     */
    public function renderData($data, $responseType = RESPONSE_JSON) {
        ob_start();

        //format response and headers
        if($responseType == RESPONSE_JSON) {
            echo json_encode($data);
        }

        $response = ob_get_clean();
        echo $response;
    }

    /**
     * Assaign all parameters to the view and returns its content
     * @param  string $name view name
     * @return string       view html content
     */
    public function renderMail($name) {
        foreach($this->params as $key => $value) {
            $this->template->assign($key, $value);
        }

        ob_start();

        $this->template->draw($name);

        $response = ob_get_clean();
        return $response;
    }
}

?>