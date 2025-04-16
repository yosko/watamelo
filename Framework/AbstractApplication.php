<?php

namespace Watamelo\Framework;

use Exception;
use Watamelo\Component\Http\Request;
use Watamelo\Framework\Http\Handler\ExceptionHandler;
use Watamelo\Framework\Http\Router\Router;

define('WATAMELO_VERSION', '1.1');

/**
 * Abstract class
 * Main application, will be called from index.php
 */
abstract class AbstractApplication
{
    protected ?string $tplPath;
    protected string $configPath;
    protected ?string $root;
    protected Request $request;
    protected ExceptionHandler $exceptionHandler;
    protected View $view;
    protected bool $useDefaultRoutes = true;
    protected array $managers = [];


    public function __construct(string $configPath = 'Config', ?string $root = null, bool $devEnv = true)
    {
        $this->request = new Request();

        // set the root path (filesystem path to the app)
        $this->root = $root ?? '';

        //handle errors and warnings
        $this->setErrorReporting($devEnv);
        $this->exceptionHandler = new ExceptionHandler();

        $errorFile = $this->root . 'tmp/logs/error.log';
        ini_set('log_errors', 'On');
        ini_set('error_log', $errorFile);
        $this->purgeLogs($errorFile);

        $this->configPath = trim($configPath, '/');
        $this->tplPath = null;
    }

    /**
     * Purge the old logs
     * TODO: move this feature to another Composer package?
     * @param string $errorFile path to main error log
     */
    protected function purgeLogs(string $errorFile)
    {
        $today = date('Y-m-d');
        $errorFileYesterday = $this->root . 'tmp/logs/error-' . date('Y-m-d', strtotime($today . ' -1 day')) . '.log';
        $errorFileAWeekAgo = $this->root . 'tmp/logs/error-' . date('Y-m-d', strtotime($today . ' -8 day')) . '.log';
        if (file_exists($errorFile) && !file_exists($errorFileYesterday) && file_exists($errorFile) && filesize($errorFile) > 0) {
            rename(
                $errorFile,
                $errorFileYesterday
            );
            if (file_exists($errorFileAWeekAgo)) {
                unlink($errorFileAWeekAgo);
            }
        }
    }

    /**
     * Set the error reporting level
     * @param bool $isDebug true if the application should display errors
     * @return void
     */
    public function setErrorReporting($isDebug)
    {
        error_reporting(E_ALL);
        if ($isDebug) {
            ini_set('display_errors', 'On');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', 'Off');
        }
    }

    public function setTplPath(?string $tplPath)
    {
        $this->tplPath = $tplPath;
    }

    /**
     * Run the application (initialize then execute)
     */
    public function run()
    {
        $router = new Router($this->request);
        $this->init($router);
        $this->initView($this->tplPath);
        $this->execute($router);
    }

    /**
     * Initialise the application (routes, common settings, etc.)
     */
    public abstract function init(Router $router);

    /**
     * Execute the application (will call the right class/controller and action)
     */
    public abstract function execute(Router $router);


    public function getConcreteNamespace()
    {
        return (new \ReflectionClass($this))->getNamespaceName();
    }

    /**
     * Initialise the View object
     * @param ?string $tplPath
     * @param bool $ApacheURLRewriting
     */
    public function initView(?string $tplPath = null)
    {
        $this->view = new View($this->request->getBasePath(), $this->root, $tplPath);
        $this->exceptionHandler->setView($this->view);
    }

    public function configPath(): string
    {
        return $this->configPath;
    }

    /**
     * Returns the application flag 'useDefaultRoutes'
     * @return bool useDefaultRoutes
     */
    public function useDefaultRoutes(): bool
    {
        return $this->useDefaultRoutes;
    }

    /**
     * Explicitly log errors/exceptions that where already caught
     * @param Exception $e
     * @param string $string
     * @return bool
     */
    public function logException(Exception $e, string $string = ''): bool
    {
        return error_log(
            ' (manually logged) ' . $e->getMessage() . (empty($string) ? '' : ' [' . $string . ']')
        );
    }

    /**
     * Returns the application view
     * @return object view
     */
    public function view()
    {
        return $this->view;
    }
}
