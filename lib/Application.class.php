<?php

namespace Watamelo\Lib;

use Exception;
use InvalidArgumentException;
use PDO;
use Throwable;

define('WATAMELO_VERSION', '0.12');

/**
 * Abstract class
 * Main application, will be called from index.php
 */
abstract class Application
{
    protected string $appName = '';
    protected View $view;
    protected bool $useDefaultRoutes = true;
    protected string $defaultControllerName = '';
    protected ?PDO $dao = null;
    protected array $managers = [];
    protected string $getParamName;


    public function __construct($appName = '')
    {
        //handle errors and warnings
        $this->setErrorReporting(DEVELOPMENT_ENVIRONMENT);

        set_exception_handler(array($this, 'exceptionHandler'));

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

        $this->getParamName = 'url';
        $this->appName = empty($appName) ? get_called_class() : $appName;
    }

    public function setErrorReporting($isDebug)
    {
        error_reporting(E_ALL | E_STRICT);
        if ($isDebug) {
            ini_set('display_errors', 'On');
        } else {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Run the application (will call the right controller and action)
     */
    abstract public function run();

    /**
     * Returns a manager (loads it if not already loaded)
     * @param string $module manager name (case insensitive)
     * @return Manager
     */
    public function manager(string $module): Manager
    {
        if (!is_string($module) || empty($module)) {
            throw new InvalidArgumentException('Invalid module');
        }

        if (!isset($this->managers[$module])) {
            $manager = '\\Watamelo\\Managers\\' . $module . 'Manager';
            $this->managers[$module] = new $manager($this);
        }

        return $this->managers[$module];
    }

    /**
     * Initialise the View object
     * @param string $template
     * @param string $rootUrl
     * @param bool $ApacheURLRewriting
     */
    public function initView(string $template, string $rootUrl, bool $ApacheURLRewriting)
    {
        $this->view = new View($this, $template, $rootUrl, $ApacheURLRewriting);
    }

    /**
     * Returns the application name
     * @return string name
     */
    public function appName(): string
    {
        return $this->appName;
    }

    /**
     * Returns the application data access object
     * @return PDO name
     */
    public function dao(): ?PDO
    {
        return $this->dao;
    }

    /**
     * Returns the application parameter name used in $_GET
     * @return string name
     */
    public function getParamName(): string
    {
        return $this->getParamName;
    }

    /**
     * Set the application parameter name used in $_GET
     * @param string $getParamName
     */
    public function setGetParamName(string $getParamName)
    {
        $this->getParamName = $getParamName;
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
     * Returns the application default controller name
     * @return string defaultControllerName
     */
    public function defaultControllerName(): string
    {
        return $this->defaultControllerName;
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
     * Display exceptions and errors in a nicely manner
     * @param Throwable $e
     * @throws Exception
     */
    public function exceptionHandler(Throwable $e)
    {
        $this->view()->setParam('exception', $e);
        echo $this->view()->renderView('exception', false);
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