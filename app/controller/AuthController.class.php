<?php
namespace Watamelo\Controllers;

/**
 * Authentication handler
 */
class AuthController extends \Watamelo\Controllers\WatameloController
{
    protected $currentUser;
    protected $logger;

    public function __construct(\Watamelo\Lib\Application $app)
    {
        parent::__construct($app);

        //get managers
        $userManager = $this->app()->manager('User');
        $sessionManager = $this->app()->manager('Session');
        $sessionName = $this->app()->config()->get('sessName');
        $redirectUrl = isset($_POST['password'])?$_SERVER['REQUEST_URI']:$this->app()->view()->rootUrl();

        $this->logger = new \Yosko\YosLogin(
            $sessionName,
            array($userManager, 'getForAuthentication')
            // , DEVELOPMENT_ENVIRONMENT?ROOT.'/tmp/logs/auth.log':''
        );

        $this->logger->setRedirectionPage($redirectUrl);
        $this->logger->setAllowLocalIp(DEVELOPMENT_ENVIRONMENT);

        $this->logger->ltSessionConfig(
            array(
                'setLTSession' => array($sessionManager, 'setLTSession'),
                'getLTSession' => array($sessionManager, 'getLTSession'),
                'unsetLTSession' => array($sessionManager, 'unsetLTSession'),
                'unsetLTSessions' => array($sessionManager, 'unsetLTSessions'),
                'flushOldLTSessions' => array($sessionManager, 'flushOldLTSessions')
            ),
            $this->app()->config()->get('sessLtDuration')
        );

        $sessionManager->setLTConfig(
            $this->app()->config()->get('sessLtDir'),
            $this->app()->config()->get('sessLtNbMax'),
            $this->app()->config()->get('sessLtDuration')
        );

        $this->currentUser = $this->app()->user();

        $this->actions = array(
            "secure" => array(
                "level" => isset($this->userLevels['user'])?$this->userLevels['user']:1
            ),
            "unsecure" => array(
                "level" => isset($this->userLevels['admin'])?$this->userLevels['admin']:10
            ),
            "jsonAuthError" => array(
                "responseType" => RESPONSE_JSON
            ),
            "jsonSecureError" => array(
                "responseType" => RESPONSE_JSON
            )
        );
    }

    public function logger()
    {
        return $this->logger;
    }

    public function userLevelNeeded()
    {
        return $this->userLevels['visitor'];
    }

    public function secureNeeded()
    {
        return false;
    }

    /**
     * Authenticate user (whether there is a login/secure form sent or not)
     * This method will be called by the application BEFORE any other controller/action
     * @return array user informations if found. Always include the 'level' key
     */
    public function authenticateUser()
    {
        $values = array();
        $errors = array();

        if (isset($_POST['login']) && isset($_POST['password'])) {
            $values['login'] = $_POST['login'];
            $values['password'] = $_POST['password'];
            $values['remember'] = isset($_POST['remember']);
            $this->currentUser = $this->logger->logIn($values['login'], $values['password'], $values['remember']);
        } elseif (isset($_POST['password'])) {
            $this->currentUser = $this->logger->authUser($_POST['password']);
        } else {
            $this->currentUser = $this->logger->authUser();
        }

        if ($this->currentUser->isLoggedIn === false) {
            $this->currentUser->level = $this->userLevels['visitor'];
        }

        if (isset($this->currentUser->errors))
            $errors = $this->currentUser->errors;
        $this->app()->view()->setParam( "values", $values );
        $this->app()->view()->setParam( "errors", $errors );
        $this->app()->view()->setParam( "currentUser", $this->currentUser );
        return $this->currentUser;
    }

    /**
     * Show login form for unauthenticated users
     */
    public function executeIndex()
    {
        if ($this->currentUser->level >= $this->userLevels['user']) {
            header( 'Location: '.$this->app()->view()->rootUrl() );
        } else {
            if (isset($this->parameters['login'])) {
                $this->app()->view()->setParam( "values", array("login" => $this->parameters['login']) );
            }
            $this->app()->view()->renderView( "auth.login.form" );
        }
    }

    /**
     * Show login form for unauthenticated users
     */
    public function executeLogin()
    {
        $this->executeIndex();
    }

    /**
     * Show the secure form (asks password for already authenticated users)
     */
    public function executeSecure()
    {
        $this->app()->view()->renderView( "auth.secure.form" );
    }

    /**
     * Logs current user out
     */
    public function executeLogout()
    {
        $this->logger->logOut();
        header( 'Location: '.$this->app()->view()->rootUrl() );
    }

    /**
     * For DEBUG purpose!
     * Render the current user authentication "unsecure" so that the
     * "secure form" is shown again without having to manually delete any cookie
     */
    public function executeUnsecure()
    {
        $this->logger->unsecure();
        header( 'Location: '.$this->app()->view()->baseUrl().'admin' );
    }

    /**
     * For JSON requests only
     * The JSON response will be an authentication error
     */
    public function executeJsonAuthError()
    {
        $data = array();
        $data['errors'] = array('authNeeded' => true);

        $this->app()->view()->renderData($data, RESPONSE_JSON);
    }

    /**
     * For JSON requests only
     * The JSON response will be a secure authentication error
     */
    public function executeJsonSecureError()
    {
        $data = array();
        $data['errors'] = array('secureNeeded' => true);

        $this->app()->view()->renderData($data, RESPONSE_JSON);
    }
}
