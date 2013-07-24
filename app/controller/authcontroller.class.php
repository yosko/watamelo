<?php

/**
 * Authentication handler
 */
class AuthController extends Controller {
    public function __construct(Application $app) {
        parent::__construct($app);

        $this->actions = array(
            "secure" => array(
                "level" => $this->userLevels['user']
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
        $user = array();
        $values = array();
        $errors = array();
        $sessionName = $this->app()->config()->get('sess.name');
        if(empty($sessionName))
            $sessionName = 'watamelo';

        //get managers
        $userManager = $this->app()->managers()->getManagerOf('user');
        $sessionManager = $this->app()->managers()->getManagerOf('session');
        
        //initialize PHP session
        $sessionManager->start($sessionName, $this->app()->config());
        
        //if user is trying to log in
        if(isset($_POST['submitLogin']) && isset($_POST['login']) && isset($_POST['password'])) {
            $values = array();
            $errors = array();
            
            $values['login'] = trim($_POST['login']);
            $values['password'] = trim($_POST['password']);
            $values['remember'] = isset($_POST['remember']);

            //find user for this login
            $user = $userManager->getByLogin($values['login'], true);

            //check user/password
            if($user === false) {
                $user = array();
                $user['level'] = $this->userLevels['visitor'];
                $errors['unknownLogin'] = true;
            } elseif(!empty($user['activation'])) {
                $user['level'] = $this->userLevels['visitor'];
                $errors['notActivated'] = true;
            } elseif(!Tools::checkPassword($values['password'], $user['password'])) {
                $user['level'] = $this->userLevels['visitor'];
                $errors['wrongPassword'] = true;
            } else {
                //set session
                $sessionManager->setValue('login', $user['login']);
                $sessionManager->setValue('ip', Tools::getIpAddress(DEVELOPMENT_ENVIRONMENT));
                $sessionManager->setValue('secure', true);
                $user['secure'] = true;

                //also create a long-term session
                if($values['remember']) {
                    $sessionManager->setValue('sid', Tools::generateRandomString(42, true));

                    $sid = $sessionManager->getValue('sid');
                    if(!empty($sid)) {
                        $sessionManager->setLTSession(
                            $sessionManager->getValue('login'),
                            $sessionManager->getValue('sid'),
                            array()
                        );
                        
                        //maintenance: delete old sessions
                        $sessionManager->flushOldLTSessions();
                    } else {
                        //make sure there is no lt sid set
                        $sessionManager->unsetValue('sid', true);
                    }
                }
                
                //to avoid any problem when using the browser's back button
                header("Location: $_SERVER[REQUEST_URI]");
            }

        //check whether user is logged in
        } else {
            //user has a PHP session
            if( $sessionManager->issetPHPSession() ) {
                $user = $userManager->getByLogin($sessionManager->getValue('login'), true);

                //if ip change, the session isn't secure anymore, even if legitimate
                //  it might be because the user was given a new one
                //  or because if a session hijacking
                if($sessionManager->getValue('ip') != Tools::getIpAddress(DEVELOPMENT_ENVIRONMENT)) {
                    $sessionManager->setValue('secure', false);
                }
                
            //user has LT cookie but no PHP session
            } elseif ($sessionManager->issetLTSession()) {
                $LTSession = $sessionManager->getLTSession();
                
                if($LTSession !== false) {
                    //set php session
                    $cookieValues = explode('_', $sessionManager->getLTCookie());
                    $sessionManager->setValue('login', $cookieValues[0]);
                    $sessionManager->setValue('secure', false); //supposedly not secure anymore
                    $user = $userManager->getByLogin($cookieValues[0], true);

                    //regenerate long-term session
                    $sessionManager->unsetLTSession($sessionManager->getLTCookie());
                    $sessionManager->setValue('sid', Tools::generateRandomString(42, true));
                    $sessionManager->setLTSession();
                } else {
                    //delete long-term cookie
                    $sessionManager->unsetLTCookie();
                    
                    header( 'Location: '.$this->app()->view()->baseUrl() );
                }
            
            //user isn't logged in: anonymous
            } else {
                $user['level'] = $this->userLevels['visitor'];
            }
            
            //if a password was given, check it
            if($user['level'] >= $this->userLevels['user']) {
                if(isset($_POST['submitSecure']) && isset($_POST['password'])) {
                    if(Tools::checkPassword($_POST['password'], $user['password'])) {
                        $sessionManager->setValue('ip', Tools::getIpAddress(DEVELOPMENT_ENVIRONMENT));
                        $sessionManager->setValue('secure', true);
                        
                        header("Location: $_SERVER[REQUEST_URI]");
                    } else {
                        $errors['wrongPassword'] = true;
                    }
                }
                $user['secure'] = $_SESSION['secure'];
            }
        }

        //for security reasons, don't send the password hash to other classes
        unset($user['password']);
        
        $this->app()->view()->setParam( "values", $values );
        $this->app()->view()->setParam( "errors", $errors );
        $this->app()->view()->setParam( "currentUser", $user );
        return $user;
    }
    
    /**
     * Show login form for unauthenticated users
     */
    public function executeIndex() {
        $user = $this->app()->user();
        if($user['level'] >= $this->userLevels['user']) {
            header( 'Location: '.$this->app()->view()->baseUrl() );
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
        $user = $this->app()->user();
        $this->app()->view()->renderView( "auth.secure.form" );
    }
    
    /**
     * Logs current user out
     */
    public function executeLogout() {
        $sessionManager = $this->app()->managers()->getManagerOf('session');
        $sessionManager->unsetSession();
        header( 'Location: '.$this->app()->view()->baseUrl() );
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