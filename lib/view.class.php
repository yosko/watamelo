<?php
namespace Watamelo\Lib;

define( 'RESPONSE_CSV', 'csv' );
define( 'RESPONSE_RSS', 'rss' );
define( 'RESPONSE_ATOM', 'atom' );
define( 'RESPONSE_FILE', 'file' );
define( 'RESPONSE_HTML', 'html' );
define( 'RESPONSE_IMG_JPEG', 'jpeg' );
define( 'RESPONSE_JSON', 'json' );
define( 'RESPONSE_MAIL', 'mail' );

/**
 * View manager
 * Class that handle the output of the application
 */
class View extends ApplicationComponent
{
    protected $params;
    protected $rootUrl;
    protected $baseUrl;
    protected $currentUrl;
    protected $templateName;
    protected $template;
    protected $templateUrl;
    protected $templatePath;
    protected $ApacheURLRewriting;

    public function __construct(Application $app, $template, $rootUrl, $ApacheURLRewriting)
    {
        parent::__construct($app);

        $this->params = array();
        $this->rootUrl = $rootUrl;
        $this->templateName = $template;
        $this->ApacheURLRewriting = $ApacheURLRewriting;

        //template config
        if ($this->templateName===false) { $this->templateName = "default"; }

        if ($this->rootUrl === false) {
        	$protocol = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on'
        		|| !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https'
        		?'https://'
        		: "http://";
            $this->rootUrl = $protocol.$_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']),'/').'/';
            $this->setParam( "templateUrl", $this->rootUrl.'tpl/'.$this->templateName.'/' );
        }

        $this->templateUrl = $this->rootUrl.'tpl/'.$this->templateName.'/';
        $this->templatePath = ROOT.DIRECTORY_SEPARATOR.'tpl'.DIRECTORY_SEPARATOR.$this->templateName.DIRECTORY_SEPARATOR;

        //if there is no URL Rewriting, the route will be put in the $_GET['p']
        $this->baseUrl = $this->rootUrl;
        $this->baseUrl .= !$this->ApacheURLRewriting?'?'.$this->app()->getParamName().'=':'';

        $this->currentUrl = (isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http').'://'.$_SERVER['SERVER_NAME'].(isset($_SERVER['REDIRECT_URL'])?$_SERVER['REDIRECT_URL']:'');

        $this->setParam( "templateUrl", $this->templateUrl );
        $this->setParam( "rootUrl", $this->rootUrl );
        $this->setParam( "baseUrl", $this->baseUrl );
        $this->setParam( 'templateUrl', $this->templateUrl );
    }

    /**
     * Returns the template
     * @return object template
     */
    public function template()
    {
        return $this->template;
    }

    /**
     * Return the currently used root url
     * @return string url
     */
    public function rootUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Return the currently used base url
     * @return string url
     */
    public function baseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Returns the template url
     * @return string template url
     */
    public function templateUrl()
    {
        return $this->templateUrl;
    }

    /**
     * Add or update a parameter to the view
     * @param string $name  parameter name
     * @param misc   $value parameter value
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Add or update a parameter by reference to the view
     * @param string $name  reference name
     * @param misc   $value parameter value
     */
    public function setReference($name, &$value)
    {
        $this->params[$name] = &$value;
    }

    /**
     * Get a parameter asigned to the view
     * @param  string $name  parameter name
     * @return misc          parameter value
     */
    public function getParam($name)
    {
        return $this->params[$name];
    }

    /**
     * Get all parameters asigned to the view
     * @return array list of parameters
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Assign all parameters to the view, then render it
     * @param string  $name         view name
     * @param boolean $directResult false to get result in the return
     */
    public function renderView($name, $directResult = true, $templatePath = '')
    {
        //file path
        if (empty($templatePath)) {
            $templatePath = $this->templatePath;
        }
        $useTemplatePath = $templatePath;
        $templatePath = $this->templatePath;
        $runtimeTemplateFile = $useTemplatePath.$name.'.tpl.php';

        //read the file
        if (file_exists($runtimeTemplateFile)) {
            //import the parameters into the current context
            extract($this->params);

            ob_start();
            include $runtimeTemplateFile;
            $response = ob_get_clean();

        //file should always exists for direct results
        } elseif ($directResult) {
            throw new Exception(sprintf('Template not found: "%s"', $name));

        //return empty if file not found
        } else {
            $response = '';
        }

        if ($directResult) {
            echo $response;
            exit;
        } else {
            return $response;
        }
    }

    /**
     * Assign all parameters to the view, then render it
     * @param string  $name         view name
     * @param boolean $directResult false to get result in the return
     */
    public function renderCss($name, $directResult = true)
    {
        //import the parameters into the current context
        extract($this->params);

        $templatePath = $this->templatePath;

        header('Content-Type: text/css; charset: UTF-8');
        header('Cache-Control: must-revalidate');
        header('Expires: ' . gmdate('D, d M Y H:i:s',time() + 3600) . ' GMT');

        ob_start();
        include $templatePath.$name.'.css.php';
        $response = ob_get_clean();

        if ($directResult) {
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
    public function renderFeed($feed, $type=RESPONSE_RSS)
    {
        $viewName = $type == RESPONSE_ATOM?RESPONSE_ATOM:RESPONSE_RSS;

        //the view is defined on framework level
        $this->templatePath = "lib/views/";

        if ($type == RESPONSE_ATOM) {
            header('Content-Type: application/atom+xml');
        } elseif ($type == RESPONSE_RSS) {
            header('Content-Type: application/rss+xml');
        } else {
            header('Content-Type: text/xml');
        }

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
     *                              - 'last-modified': date of last edition on page/file,
     *                                in Unix timestamp format, using filemtime()
     *                              - 'length': Http response length
     */
    public function renderData($data, $responseType = RESPONSE_JSON, $options = array())
    {
        ob_start();

        //handle client cache if a last-modified date is given
        $showData = true;
        if (isset($options['last-modified'])) {
            // Getting headers sent by the client.
            $headers = apache_request_headers();

            // Checking if the client is validating his cache and if it is current.
            if (isset($headers['If-Modified-Since'])
                && strtotime($headers['If-Modified-Since']) == $options['last-modified']
            ) {
                // Client's cache IS current, so we just respond '304 Not Modified'.
                header('Last-Modified: '.gmdate('D, d M Y H:i:s', $options['last-modified']).' GMT', true, 304);
                $showData = false;
            } else {
                // Image not cached or cache outdated, we respond '200 OK' and output the image.
                header('Last-Modified: '.gmdate('D, d M Y H:i:s', $options['last-modified']).' GMT', true, 200);
            }
        }

        if (isset($options['length'])) {
            header('Content-Length: '.$options['length']);
        }

        if ($showData) {
	        //format response and headers
	        if ($responseType == RESPONSE_JSON) {
	            header('Content-type: application/json');
                if (version_compare(PHP_VERSION, '5.4.0') >= 0)
                    echo json_encode($data, JSON_PRETTY_PRINT);
                else
                    echo json_encode($data);
	        } elseif ($responseType == RESPONSE_CSV) {
	            header('Content-type: text/csv');

	            $header = array();
	            foreach ($data as $key => $row) {
	                //headers
	                if (empty($header)) {
	                    $header = array_keys((array)$row);
	                    if (!isset($options['header']) || $options['header'] !== false) {
	                        echo implode(",", $header)."\n";
	                    }
	                }

	                $result='';
	                foreach ($row as $key => $value) {
                        $row = (array)$row;
	                    if (is_numeric($value)) {
	                        $result .= $value.',';
	                    } else {
	                        $result .= '"'.str_replace( '"', '\"', htmlspecialchars_decode($row[$key]) ).'"';
	                    }
	                }

	                $result = rtrim($result, ',')."\n";
	                echo $result;
                }
            } elseif ($responseType == RESPONSE_IMG_JPEG) {
                header('Content-type: image/jpeg');
                echo $data;
            } else {
                header("Content-type: application/octet-stream");
                echo $data;
            }
        }

        $response = ob_get_clean();

        if (isset($options['fileName'])) {
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
    public function renderMail($name)
    {
        return $this->renderView($name, false);
    }
}
