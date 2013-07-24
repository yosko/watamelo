<?php

/**
 * Manage PHP sessions and cookie based long term sessions
 */
class SessionManager extends Manager  {
    protected $PHPSessionName = "";
    protected $LTSessionName = "";
    protected $config;
    protected $LTDir;
    protected $LTDuration;
    protected $nbLTSession;
    
    /**
     * Start the PHP session and prepare elements for the long-term session
     * @param  string $sessionName name of the session (used in cookie names)
     * @param  array  $config      session configuration
     */
    public function start($sessionName, $config) {
        $this->PHPSessionName = $sessionName;
        $this->LTSessionName = $sessionName.'lt';
        $this->config = $config;

        $this->LTDir = $this->config->get('sess.lt.dir');
        //default session cache folder
        if($this->LTDir === false) {
            $this->LTDir = 'tmp/sessions/';
        }

        $this->LTDuration = $this->config->get('sess.lt.duration');
        //default session duration: a month
        if($this->LTDuration === false) {
            $this->LTDuration = 2592000;
        }

        $this->nbLTSession = $this->config->get('sess.lt.nbMax');
        //default number of lt sessions in parallel
        if($this->nbLTSession === false) {
            $this->nbLTSession = 200;
        }
        
        //force cookie path
        $cookie=session_get_cookie_params();
        $cookieDir = (dirname($_SERVER['SCRIPT_NAME'])!='/') ? dirname($_SERVER['SCRIPT_NAME']) : '';
        session_set_cookie_params($cookie['lifetime'], $cookieDir, $_SERVER['SERVER_NAME']);
        
        ini_set('session.gc_maxlifetime', 600);  // shorten the PHP session to 10 minutes
        ini_set('session.use_cookies', 1);       // Use cookies to store session.
        ini_set('session.use_only_cookies', 1);  // Force cookies for session (phpsessionID forbidden in URL)
        ini_set('session.use_trans_sid', false); // Prevent php to use sessionID in URL if cookies are disabled.
        
        //Session management
        session_name($this->PHPSessionName);
        session_start();
    }

    /**
     * SESSION VALUE
     */

    /**
     * Set a value in PHP session
     * @param string $key   key
     * @param misc   $value value
     */
    public function setValue($key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a value from PHP session
     * @param  string $key key
     * @return misc        value
     */
    public function getValue($key) {
        return (isset($_SESSION[$key]))?$_SESSION[$key]:false;
    }

    /**
     * Remove a value from PHP session
     * @param  string $key key
     */
    public function unsetValue($key) {
        unset($_SESSION[$key]);
    }

    /**
     * Get AND remove at the same time a value from PHP session
     * @param  string $key key
     * @return misc        value
     */
    public function retrieveValue($key) {
        $value = $this->getValue($key);
        $this->unsetValue($key);
        return $value;
    }

    /**
     * LONG-TERM COOKIE
     */

    /**
     * Get the value of the long-term cookie (which is the long-term session id)
     * @return misc cookie value
     */
    public function getLTCookie() {
        return $_COOKIE[$this->LTSessionName];
    }

    /**
     * Set the long-term cookie on client side
     * @param srting $login user login
     * @param srting $id    long-term session id
     */
    public function setLTCookie($login, $id) {
        setcookie(
            $this->LTSessionName,
            $login.'_'.$sid,
            time()+$this->LTDuration,
            dirname($_SERVER['SCRIPT_NAME']).'/',
            '',
            false,
            true
        );
    }

    /**
     * Check whether the long-term cookie is set
     * @return boolean true if the cookie exists
     */
    public function issetLTCookie() {
        return isset($_COOKIE[$this->LTSessionName]);
    }

    /**
     * Remove the long-term cookie
     */
    public function unsetLTCookie() {
        setcookie(
            $this->LTSessionName,
            null,
            time()-31536000,
            dirname($_SERVER['SCRIPT_NAME']).'/',
            '',
            false,
            true
        );
    }

    /**
     * LONG-TERM SESSION
     */

    /**
     * Set the long-term session
     * @param array $values possible values to save in session
     */
    public function setLTSession($values=array()) {
        $login = $this->getValue('login');
        $sid = $this->getValue('sid');
        
        //client-side
        $this->setLTCookie($login, $id);

        //server-side
        $fp = fopen($this->LTDir.$login.'_'.$sid.'.ses', 'w');
        fwrite($fp, gzdeflate(json_encode($values)));
        fclose($fp);
    }

    /**
     * Get the long-term session values or false if no session is found
     * @param  string $cookieValue long-term session id (and long-term cookie value)
     * @return array               possible values stored in session or empty array
     *                             false if no session found
     */
    public function getLTSession($cookieValue='') {
        $value = false;

        //default: use current user cookie value
        if(empty($cookieValue)) {
            $cookieValue = $this->LTDir.$this->getLTCookie();
        }

        $file = $cookieValue.'.ses';
        if (file_exists($file)) {
            
            //unset long-term session if expired
            if(filemtime($file)+$this->LTDuration <= time()) {
                $this->unsetLTSession($cookieValue);
                $value = false;
            } else {
                $value = json_decode(gzinflate(file_get_contents($file)), true);
                //update last access time on file
                touch($file);
            }
        }
        return $value;
    }

    /**
     * Check if a long-term session is set (on client & server side)
     * @return boolean true if session found on both side
     */
    public function issetLTSession() {
        return (
            $this->issetLTCookie()
            && file_exists($this->LTDir.$this->getLTCookie().'.ses')
        );
    }

    /**
     * Delete a long-term session on server side
     * @param  string $cookieValue long-term session id
     */
    public function unsetLTSession($cookieValue) {
        $filePath = $this->LTDir.$this->getLTCookie().'.ses';
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Delete all long-term sessions of a user
     * if no user is given, the current user's sessions are deleted
     * @param  string $userLogin only delete a specific user's sessions
     */
    public function unsetLTSessions($userLogin='') {        
        //for the current user: logging out
        if(empty($userLogin)) {
            $userLogin = $this->getValue('login');
            $this->unsetLTCookie();
        }

        if(!empty($userLogin)) {
            $files = glob( $this->LTDir.$userLogin.'_*', GLOB_MARK );
            foreach( $files as $file ) {
                unlink( $file );
            }
        }
    }

    /**
     * Delete all expired or exceeding long-term sessions
     */
    public function flushOldLTSessions() {
        $dir = $this->LTDir;
        
        //list all the session files
        $files = array();
        if ($dh = opendir($dir)) {
            while ($file = readdir($dh)) {
                if(!is_dir($dir.$file)) {
                    if ($file != "." && $file != "..") {
                        $files[$file] = filemtime($dir.$file);
                    }
                }
            }
            closedir($dh);
        }
        
        //sort files by date (descending)
        arsort($files);
        
        //check each file
        $i = 1;
        foreach($files as $file => $date) {
            if ($i > $this->nbLTSession || $date+$this->LTDuration <= time()) {
                $this->unsetLTSession(basename($file));
            }
            ++$i;
        } 
    }

    /**
     * GLOBAL SESSION
     */

    /**
     * Delete a session (PHP & long-term) on both sides
     */
    public function unsetSession() {
        $this->unsetLTSessions();
        $this->unsetPHPSession();
    }

    /**
     * PHP SESSION
     */

    /**
     * Delete the current user's PHP session
     */
    public function unsetPHPSession() {
        $cookieDir = (dirname($_SERVER['SCRIPT_NAME'])!='/') ? dirname($_SERVER['SCRIPT_NAME']) : '';
        session_set_cookie_params(time()-31536000, $cookieDir, $_SERVER['SERVER_NAME']);

        unset($_SESSION);
        session_unset();
        session_destroy();
    }

    /**
     * Check if PHP session exists for current user
     * @return boolean true if user name is stored in session
     */
    public function issetPHPSession() {
        return (
            $this->getValue('login') !== false
            && isset($_COOKIE[$this->PHPSessionName])
        );
    }
}

?>