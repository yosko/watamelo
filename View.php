<?php

namespace Yosko\Watamelo;

use DOMDocument;
use LogicException;

/**
 * View manager
 * Class that handle the output of the application
 */
class View
{
    protected array $params;
    protected string $rootUrl;
    protected string $baseUrl;
    protected string $currentUrl;
    protected string $tplSubdir;
    protected object $template;
    protected string $templateUrl;
    protected string $templatePath;
    protected bool $ApacheURLRewriting;

    public function __construct(string $rootUrl, string $rootPath, string $tplSubdir = '')
    {
        $this->params = [];
        $this->rootUrl = $rootUrl;
        $this->tplSubdir = $tplSubdir;
        $this->ApacheURLRewriting = true;

        // template subdirectory: make sure it ends with a /
        $this->tplSubdir = $this->tplSubdir !== '' ? rtrim($this->tplSubdir, '/') . '/' : '';

        // TODO: check if really useful
        if (empty($this->rootUrl)) {
            $protocol = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on'
                || !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https'
                ? 'https://'
                : "http://";
            $this->rootUrl = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
            $this->setParam("templateUrl", $this->rootUrl . 'App/Templates/' . $this->tplSubdir);
        }

        // TODO: are all this variables really needed?
        $this->templateUrl = $this->rootUrl . 'App/Templates/' . $this->tplSubdir;
        $this->templatePath = $rootPath . '/App/Templates/' . $this->tplSubdir;

        //if there is no URL Rewriting, the route will be put in the $_GET['p']
        $this->baseUrl = $this->rootUrl ?: dirname($_SERVER['PHP_SELF']) . '/';

        $this->currentUrl = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . $_SERVER['SERVER_NAME'] . (isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : '');

        $this->setParam("templateUrl", $this->templateUrl);
        $this->setParam("rootUrl", $this->rootUrl);
        $this->setParam("baseUrl", $this->baseUrl);
        $this->setParam('currentUrl', $this->currentUrl);
    }

    /**
     * Add or update a parameter to the view
     * @param string $name parameter name
     * @param mixed $value parameter value
     */
    public function setParam(string $name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Build a route based on the base URL and the given format and includes the arguments (based on sprintf() )
     * @param string $routeFormat
     * @param mixed ...$args
     * @return string
     */
    public function buildRoute(string $routeFormat, ...$args): string
    {
        return $this->baseUrl . sprintf(...func_get_args());
    }

    /**
     * Assign all parameters to the view, then render it
     * @param string $name view name
     * @param string $customPath
     * @return false|string
     */
    public function render(string $name, string $customPath = '')
    {
        // path for the main template for this app
        $templatePath = $this->templatePath;

        // if a custom path is given, use it to load the template
        // (but keep the main template path for other includes)
        if (empty($customPath) == false) {
            $this->setParam('customPath', $customPath);
        }

        if (substr($name, 0, 1) == '/') {
            $runtimeTemplateFile = $name . '.tpl.php';
        } else {
            $runtimeTemplateFile = (empty($customPath) ? $templatePath : $customPath) . $name . '.tpl.php';
        }

        //read the file
        if (file_exists($runtimeTemplateFile)) {
            //import the parameters into the current context
            $this->setParam('self', $name);
            $this->setParam('dom', new DOMDocument());
            extract($this->params);

            ob_start();
            include $runtimeTemplateFile;
            $response = ob_get_clean();

        } else {
            throw new LogicException(sprintf('Template not found: "%s"', $name));
        }

        return $response;
    }
}
