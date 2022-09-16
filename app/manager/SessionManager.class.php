<?php

namespace Watamelo\Managers;

use Watamelo\Lib\Manager;

/**
 * Manage PHP sessions and cookie based long term sessions
 */
class SessionManager extends Manager
{
    protected string $LTDir;
    protected int $nbLTSession;
    protected int $LTDuration;

    /**
     * COOKIES
     */

    /**
     * @param string $key
     */
    public function unsetCookie(string $key)
    {
        $this->setCookie($key, '', time() - 86400);
    }

    /**
     * Set a value for a cookie
     * @param string $key cookie name
     * @param string $value cookie value
     * @param int $duration lifetime of the cookie (in seconds)
     */
    public function setCookie(string $key, string $value, int $duration)
    {
        $dirname = dirname($_SERVER['SCRIPT_NAME']);
        if (substr($dirname, -1) != '/') {
            $dirname .= '/';
        }
        setcookie(
            $key,
            $value,
            time() + $duration,
            $dirname,
            '',
            false,
            true
        );
    }

    /**
     * Get the value of a cookie
     * @param string $key cookie name
     * @return string|false cookie value (a string) or false if cookie not found
     */
    public function getCookie(string $key)
    {
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : false;
    }

    /**
     * SESSION VALUE
     */

    /**
     * Set a value in PHP session
     * @param string $key key
     * @param mixed $value value
     */
    public function setValue(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get all PHP session variables
     * @return mixed        value
     */
    public function getAll(): array
    {
        return isset($_SESSION) ? $_SESSION : array();
    }

    /**
     * Get AND remove at the same time a value from PHP session
     * @param string $key key
     * @return mixed        value
     */
    public function retrieveValue(string $key): bool
    {
        $value = $this->getValue($key);
        $this->unsetValue($key);
        return $value;
    }

    /**
     * Get a value from PHP session
     * @param string $key key
     * @return mixed        value
     */
    public function getValue(string $key): bool
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : false;
    }

    /**
     * Remove a value from PHP session
     * @param string $key key
     */
    public function unsetValue(string $key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * LONG-TERM SESSION
     */

    /**
     * Set configuration for long-term session handling
     * @param string $LTDir local directory
     * @param int $nbLTSession maximum number of active long-term sessions
     * @param int $LTDuration maximum duration of a long-term session
     */
    public function setLTConfig($LTDir = 'tmp/sessions/', $nbLTSession = 200, $LTDuration = 2592000)
    {
        if (!file_exists($LTDir)) {
            mkdir($LTDir, 0755, true);
        }
        $this->LTDir = $LTDir;
        $this->nbLTSession = $nbLTSession;
        $this->LTDuration = $LTDuration;
    }

    /**
     * Set the long-term session
     * @param string $login
     * @param string $sid
     * @param mixed $values
     */
    public function setLTSession(string $login, string $sid, $values)
    {
        //create the session directory if needed
        if (!file_exists($this->LTDir)) {
            mkdir($this->LTDir, 0700, true);
        }

        $fp = fopen($this->LTDir . $login . '_' . $sid . '.ses', 'w');
        fwrite($fp, gzdeflate(json_encode($values)));
        fclose($fp);
    }

    /**
     * Get the long-term session values or false if no session is found
     * @param string $login login
     * @param string $sid session id
     * @return array         possible values stored in session or empty array
     *                       false if no session found
     */
    public function getLTSession(string $login, string $sid)
    {
        $value = false;
        $file = $this->LTDir . $login . '_' . $sid . '.ses';
        if (file_exists($file)) {

            //unset long-term session if expired
            if (filemtime($file) + $this->LTDuration <= time()) {
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
     * @param string $login login
     * @param string|false $sid session id
     */
    public function unsetLTSession(string $login, $sid = false)
    {
        if ($sid !== false) {
            $login .= '_' . $sid;
        }
        $filePath = $this->LTDir . $login . '.ses';
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Delete all long-term sessions of a user
     * if no user is given, the current user's sessions are deleted
     * @param string $login only delete a specific user's sessions
     */
    public function unsetLTSessions(string $login)
    {
        $files = glob($this->LTDir . $login . '_*', GLOB_MARK);
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    /**
     * Delete all expired or exceeding long-term sessions
     */
    public function flushOldLTSessions()
    {
        $dir = $this->LTDir;

        //list all the session files
        $files = array();
        if ($dh = opendir($dir)) {
            while ($file = readdir($dh)) {
                if (!is_dir($dir . $file)) {
                    if ($file != "." && $file != "..") {
                        $files[$file] = filemtime($dir . $file);
                    }
                }
            }
            closedir($dh);
        }

        //sort files by date (descending)
        arsort($files);

        //check each file
        $i = 1;
        foreach ($files as $file => $date) {
            if ($i > $this->nbLTSession || $date + $this->LTDuration <= time()) {
                $this->unsetLTSession(basename($file));
            }
            ++$i;
        }
    }
}
