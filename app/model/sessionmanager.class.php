<?php

/**
 * Manage PHP sessions and cookie based long term sessions
 */
class SessionManager extends Manager  {
    protected static $LTDir = 'tmp/sessions/';
    protected static $nbLTSession = 200;
    public static $LTDuration = 2592000;

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

    public function setLTSession($login, $sid, $value) {
        //create the session directory if needed
        if(!file_exists(self::$LTDir)) { mkdir(self::$LTDir, 0700, true); }

        $fp = fopen(self::$LTDir.$login.'_'.$sid.'.ses', 'w');
        fwrite($fp, gzdeflate(json_encode($value)));
        fclose($fp);
    }
    
    public function getLTSession($login, $sid) {
        $value = false;
        $file = self::$LTDir.$login.'_'.$sid.'.ses';
        if (file_exists($file)) {
            
            //unset long-term session if expired
            if(filemtime($file)+self::$LTDuration <= time()) {
                $this->unsetLTSession($login, $sid);
                $value = false;
            } else {
                $value = json_decode(gzinflate(file_get_contents($file)), true);
                //update last access time on file
                touch($file);
            }
        }
        return($value);
    }
    
    //unset a specific LT session
    public function unsetLTSession($login, $sid) {
        $filePath = self::$LTDir.$login.'_'.$sid.'.ses';
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    //unset all server-side LT session for this user
    public function unsetLTSessions($login) {
        $files = glob( self::$LTDir.$login.'_*', GLOB_MARK );
        foreach( $files as $file ) {
            unlink( $file );
        }
    }
    
    public function flushOldLTSessions() {
        $dir = self::$LTDir;
        
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
            if ($i > self::$nbLTSession || $date+self::$LTDuration <= time()) {
                $this->unsetLTSession(basename($file));
            }
            ++$i;
        } 
    }
}

?>