<?php

namespace Yosko\Watamelo;

use Exception;

define('WATAMELO_VERSION', '1.0');

/**
 * Abstract class
 * Main application, will be called from index.php
 */
abstract class AbstractApplication
{
    protected string $appName;
    protected string $configPath;
    protected ExceptionHandler $exceptionHandler;
    protected View $view;
    protected bool $useDefaultRoutes = true;
    protected array $managers = [];


    public function __construct(string $appName = '', string $configPath = 'Config')
    {
        //handle errors and warnings
        $this->setErrorReporting(DEVELOPMENT_ENVIRONMENT);
        $this->exceptionHandler = new ExceptionHandler();

        $errorFile = ROOT . '/tmp/logs/error.log';
        ini_set('log_errors', 'On');
        ini_set('error_log', $errorFile);

        //purge old logs
        $today = date('Y-m-d');
        $errorFileYesterday = ROOT . '/tmp/logs/error-' . date('Y-m-d', strtotime($today . ' -1 day')) . '.log';
        $errorFileAWeekAgo = ROOT . '/tmp/logs/error-' . date('Y-m-d', strtotime($today . ' -8 day')) . '.log';
        if (file_exists($errorFile) && !file_exists($errorFileYesterday) && file_exists($errorFile) && filesize($errorFile) > 0) {
            rename(
                $errorFile,
                $errorFileYesterday
            );
            if (file_exists($errorFileAWeekAgo)) {
                unlink($errorFileAWeekAgo);
            }
        }

        $this->appName = empty($appName) ? get_called_class() : $appName;
        $this->configPath = trim($configPath, '/');
    }

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

    /**
     * Run the application (will call the right class/controller and action)
     */
    abstract public function run();

    public function getConcreteNamespace()
    {
        return (new \ReflectionClass($this))->getNamespaceName();
    }

    /**
     * Initialise the View object
     * @param string $template
     * @param string $rootUrl
     * @param bool $ApacheURLRewriting
     */
    public function initView(string $template, string $rootUrl, bool $ApacheURLRewriting)
    {
        $this->view = new View(
            $template,
            $rootUrl,
            $ApacheURLRewriting
        );
        $this->exceptionHandler->setView($this->view);
    }

    /**
     * Returns the application name
     * @return string name
     */
    public function appName(): string
    {
        return $this->appName;
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
