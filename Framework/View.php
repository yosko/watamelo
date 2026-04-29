<?php

namespace Watamelo\Framework;

use DOMDocument;
use LogicException;
use Watamelo\Component\Http\Request;

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
    protected string $tplPath;
    protected object $template;
    protected string $templateUrl;
    protected string $templatePath;
    protected Request $request;

    public function __construct(Request $request, string $rootPath, ?string $tplPath = null)
    {
        $this->params = [];
        $this->request = $request;

        // base URL for the client
        $this->rootUrl = $request->getRootUrl();

        // template directory
        if (is_null($tplPath)) {
            $this->tplPath = 'src/Templates/';
        } else {
            // make sure it ends with a /
            $this->tplPath = $tplPath !== '' ? rtrim($tplPath, '/') . '/' : '';
        }

        $this->templateUrl = rtrim($this->rootUrl, '/') . '/' . $this->tplPath;
        $this->templatePath = $rootPath . $this->tplPath;

        // base URL for route building (always with trailing slash)
        $this->baseUrl = rtrim($this->rootUrl, '/') . '/';

        // full rewritten URL (Apache internal path)
        $this->currentUrl = $request->getRewrittenUrl();

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
