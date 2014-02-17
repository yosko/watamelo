<?php

require_once(ROOT.'/app/ext/yoslogin.lib.php');

/**
 * Authentication handler
 */
class AuthController extends Controller {
    protected $currentUser;
    protected $logger;
    
    public function __construct(Application $app) {
        parent::__construct($app);

        //get managers
        $userManager = $this->app()->getManagerOf('user');
        $sessionManager = $this->app()->getManagerOf('session');
        $sessionName = $this->app()->config()->get('sess.name');
        // redirect $this->app()->view()->rootUrl()
        
        $this->logger = new \Yosko\YosLogin(
            'exampleSessionName',
            array($userManager, 'getForAuthentication'),
            $this->app()->view()->rootUrl(),
            DEVELOPMENT_ENVIRONMENT,
            DEVELOPMENT_ENVIRONMENT?ROOT.'/tmp/logs/auth.log':''
        );

        $this->logger->ltSessionConfig(
            array(
                'setLTSession' => array($sessionManager, 'setLTSession'),
                'getLTSession' => array($sessionManager, 'getLTSession'),
                'unsetLTSession' => array($sessionManager, 'unsetLTSession'),
                'unsetLTSessions' => array($sessionManager, 'unsetLTSessions'),
                'flushOldLTSessions' => array($sessionManager, 'flushOldLTSessions')
            ),
            $sessionManager::$LTDuration
        );
        
        $this->currentUser = $this->app()->user();

        $this->actions = array(
            "secure" => array(
                "level" => $this->userLevels['user']
            ),
            "unsecure" => array(
                "level" => $this->userLevels['admin']
            ),
            "jsonAuthError" => array(
                "responseType" => RESPONSE_JSON
            ),
            "jsonSecureError" => array(
                "responseType" => RESPONSE_JSON
            )
        );
    }

    /**
     * Authenticate user (whether there is a login/secure form sent or not)
     * This method will be called by the application BEFORE any other controller/action
     * @return array user informations if found. Always include the 'level' key
     */
    public function authenticateUser() {
        if(isset($_POST['login']) && isset($_POST['password'])) {
            $remember = isset($_POST['remember']);
            $this->currentUser = $this->logger->logIn($_POST['login'], $_POST['password'], $remember);
        } elseif(isset($_POST['password'])) {
            $this->currentUser = $this->logger->authUser($_POST['password']);
        } else {
            $this->currentUser = $this->logger->authUser();
        }

        if($this->currentUser['isLoggedIn'] === false) {
            $this->currentUser['level'] = $this->userLevels['visitor'];
        }
        
        //TODO send values and errors to the view if needed
        // $this->app()->view()->setParam( "values", $values );
        // $this->app()->view()->setParam( "errors", $errors );
        $this->app()->view()->setParam( "currentUser", $this->currentUser );
        return $this->currentUser;
    }
    
    /**
     * Show login form for unauthenticated users
     */
    public function executeIndex() {
        if($this->currentUser['level'] >= $this->userLevels['user']) {
            header( 'Location: '.$this->app()->view()->rootUrl() );
        } else {
            if(isset($this->parameters['login'])) {
                $this->app()->view()->setParam( "values", array("login" => $this->parameters['login']) );
            }
            $this->app()->view()->renderView( "auth.login.form" );
        }
    }
    
    /**
     * Show login form for unauthenticated users
     */
    public function executeLogin() {
        $this->executeIndex();
    }
    
    /**
     * Show the secure form (asks password for already authenticated users)
     */
    public function executeSecure() {
        $this->app()->view()->renderView( "auth.secure.form" );
    }
    
    /**
     * Logs current user out
     */
    public function executeLogout() {
        $this->logger->logOut();
        header( 'Location: '.$this->app()->view()->rootUrl() );
    }
    
    /**
     * For DEBUG purpose!
     * Render the current user authentication "unsecure" so that the
     * "secure form" is shown again without having to manually delete any cookie
     */
    public function executeUnsecure() {
        $this->logger->unsecure();
        header( 'Location: '.$this->app()->view()->baseUrl().'admin' );
    }
    
    /**
     * For JSON requests only
     * The JSON response will be an authentication error
     */
    public function executeJsonAuthError() {
        $data = array();
        $data['errors'] = array('authNeeded' => true);
        
        $this->app()->view()->renderData($data, RESPONSE_JSON);
    }
    
    /**
     * For JSON requests only
     * The JSON response will be a secure authentication error
     */
    public function executeJsonSecureError() {
        $data = array();
        $data['errors'] = array('secureNeeded' => true);
        
        $this->app()->view()->renderData($data, RESPONSE_JSON);
    }
}

?>