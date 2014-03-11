<?php

/**
 * Manage PHP sessions and cookie based long term sessions
 */
class SessionManager extends Manager  {
    protected $LTDir;
    protected $nbLTSession;
    protected $LTDuration;

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
     * LONG-TERM SESSION
     */

    /**
     * Set configuration for long-term session handling
     * @param string $key   key
     * @param misc   $value value
     */
    public function setLTConfig($LTDir = 'tmp/sessions/', $nbLTSession = 200, $LTDuration = 2592000) {
        $this->LTDir = $LTDir;
        $this->nbLTSession = $nbLTSession;
        $this->LTDuration = $LTDuration;
    }

    /**
     * Set the long-term session
     * @param array $values possible values to save in session
     */
    public function setLTSession($login, $sid, $value) {
        //create the session directory if needed
        if(!file_exists($this->LTDir)) { mkdir($this->LTDir, 0700, true); }

        $fp = fopen($this->LTDir.$login.'_'.$sid.'.ses', 'w');
        fwrite($fp, gzdeflate(json_encode($value)));
        fclose($fp);
    }
    
    /**
     * Get the long-term session values or false if no session is found
     * @param  string $login login
     * @param  string $sid   session id
     * @return array         possible values stored in session or empty array
     *                       false if no session found
     */
    public function getLTSession($login, $sid) {
        $value = false;
        $file = $this->LTDir.$login.'_'.$sid.'.ses';
        if (file_exists($file)) {
            
            //unset long-term session if expired
            if(filemtime($file)+$this->LTDuration <= time()) {
                $this->unsetLTSession($login, $sid);
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
     * Delete a long-term session on server side
     * @param  string $login login
     * @param  string $sid   session id
     */
    public function unsetLTSession($login, $sid) {
        $filePath = $this->LTDir.$login.'_'.$sid.'.ses';
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    /**
     * Delete all long-term sessions of a user
     * if no user is given, the current user's sessions are deleted
     * @param  string $userLogin only delete a specific user's sessions
     */
    public function unsetLTSessions($login) {
        $files = glob( $this->LTDir.$login.'_*', GLOB_MARK );
        foreach( $files as $file ) {
            unlink( $file );
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
}

?>