<?php

/**
 * Yoslogin - Copyright 2013 Yosko (www.yosko.net)
 * 
 * This file is part of Yoslogin.
 * 
 * Yoslogin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Yoslogin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with Yoslogin. If not, see <http://www.gnu.org/licenses/>.
 * 
 */
namespace Yosko;

/**
 * Utility class to handle login verification and sessions
 * 
 * You need to implement the abstract method to handle the way you retrieve
 * user information
 */
class YosLogin {
    protected $sessionName;
    protected $LTSessionName;
    protected $LTDuration;
    protected $ltCookie;
    protected $allowLocalIp;
    protected $actibateLog;
    protected $useLTSessions;
    protected $version;
    protected $getUserCallback;
    protected $ltSessionCallbacks;
    protected $activateLog;
    protected $logFile;
    protected $redirectionPage;
    
    /**
     * Initialize the session handler
     * @param string $sessionName     base name for the PHP and the long-term sessions
     * @param misc   $getUserCallback callback to get a user from its login
     * @param int    $allowLocalIp    true to handle properly localhost & 127.0.0.1 (but a bit less secure: for dev/debug purpose only)
     * @param int    $logFile         name and path to a log file to keep trace of every action
     */
    public function __construct($sessionName, $getUserCallback, $redirectionPage = '', $allowLocalIp=false, $logFile='') {
        $this->version = 'v4';
        $this->useLTSessions = false;

        $this->sessionName = $sessionName;
        $this->getUserCallback = $getUserCallback;
        $this->redirectionPage = empty($redirectionPage)?$_SERVER['PHP_SELF']:$redirectionPage;
        $this->allowLocalIp = $allowLocalIp;
        $this->logFile = $logFile;
        $this->activateLog = !empty($this->logFile);
    }

    /**
     * Gives the current version number of YosLogin
     * @return string version number (v1, v2, v3...)
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * Define the callback for when YosLogin will need to get a user
     * @param  misc $callback function name
     *                        or array with object + method name
     *                        or arrawy with class name + method name
     */
    public function getUserCallback($callback) {
        $this->getUserCallback = $callback;
    }

    /**
     * -------------------------------------------------------------------------
     * USER MANAGER
     * -------------------------------------------------------------------------
     */

    /**
     * Get the user informations. Used to check the password
     * @param  string $login login sent via login form
     * @return array()       required items: array("login" => <login>, "password" => <password hash>)
     *                       or empty array if user not found
     */
    protected function getUser($login) {
        return call_user_func($this->getUserCallback, $login);
    }

    /**
     * -------------------------------------------------------------------------
     * SESSION MANAGER
     * -------------------------------------------------------------------------
     */

    /**
     * Define the callbacks for long-term session functions
     * @param  misc $callback         array of callbacks in that order:
     *                                - setLTSession($login, $sid, $value)
     *                                - getLTSession($cookieValue)
     *                                - unsetLTSession($cookieValue)
     *                                - unsetLTSessions($login)
     *                                - flushOldLTSessions()
     * @param int    $LTDuration      duration (in seconds) for long-term sessions
     */
    public function ltSessionConfig($callbacks, $LTDuration) {
        $this->useLTSessions = true;
        $this->LTSessionName = $this->sessionName.'lt';

        //long-term session params
        $this->ltSessionCallbacks = $callbacks;
        $this->LTDuration = $LTDuration;

        $this->ltCookie = $this->loadLtCookie();
    }

    /**
     * Initialize and configure the PHP (short-term) session
     */
    protected function initPHPSession() {
        if(!$this->isPHPSessionStarted()) {
            //force cookie path
            $cookie=session_get_cookie_params();
            $cookieDir = (dirname($_SERVER['SCRIPT_NAME'])!='/') ? dirname($_SERVER['SCRIPT_NAME']) : '';
            session_set_cookie_params($cookie['lifetime'], $cookieDir, $_SERVER['SERVER_NAME']);

            // If allowed, shorten the PHP session to 10 minutes
            ini_set('session.gc_maxlifetime', 600);
            // Use cookies to store session.
            ini_set('session.use_cookies', 1);
            // Force cookies for session (phpsessionID forbidden in URL)
            ini_set('session.use_only_cookies', 1);
            // Prevent php to use sessionID in URL if cookies are disabled.
            ini_set('session.use_trans_sid', false);

            //Session management
            session_name($this->sessionName);
            session_start();
        }
    }

    /**
     * Avoid calling initPHPSession() multiple times by checking if session is already started
     * @return boolean session status
     */
    protected function isPHPSessionStarted() {
        if ( php_sapi_name() !== 'cli' ) {
            if ( version_compare(phpversion(), '5.4.0', '>=') ) {
                return session_status() === PHP_SESSION_ACTIVE ? true : false;
            } else {
                return session_id() === '' ? false : true;
            }
        }
        return false;
    }

    protected function unsetSessionVar($var) {
        if(isset($_SESSION[$var]))
            unset($_SESSION[$var]);
    }

    /**
     * Save the long term session for the given user and id
     * @param string $login  user login
     * @param string $sid    long-term session id (stored in a cookie too)
     * @param array() $value optional: array of data you want to keep in long-term session on server side
     */
    protected function setLTSession($login, $sid, $value) {
        if(isset($this->ltSessionCallbacks['setLTSession']))
            return call_user_func($this->ltSessionCallbacks['setLTSession'], $login, $sid, $value);
    }

    /**
     * Retrieve a long-term session based on the cookie value
     * @param  string $cookieValue the concatenation of <login>_<id> used in the cookie value
     * @return array()             optional: array of data stored in the session (empty if no data)
     *                             or false if long-term session not found or expired
     */
    protected function getLTSession($cookieValue) {
        $cookieValues = explode('_', $cookieValue, 2);
        if(isset($this->ltSessionCallbacks['getLTSession']))
            return call_user_func($this->ltSessionCallbacks['getLTSession'], $cookieValues[0], $cookieValues[1]);
    }

    /**
     * Remove a long-term session based on the cookie value
     * @param  string $cookieValue the concatenation of <login>_<id> used in the cookie value
     */
    protected function unsetLTSession($cookieValue) {
        $cookieValues = explode('_', $cookieValue, 2);
        if(isset($this->ltSessionCallbacks['unsetLTSession']))
            return call_user_func($this->ltSessionCallbacks['unsetLTSession'], $cookieValues[0], $cookieValues[1]);
    }

    /**
     * Remove all existing long-term sessions for a given user
     * @param  string $login user login
     */
    protected function unsetLTSessions($login) {
        if(isset($this->ltSessionCallbacks['unsetLTSessions']))
            return call_user_func($this->ltSessionCallbacks['unsetLTSessions'], $login);
    }

    /**
     * Remove all expired or exceeding long-term sessions
     */
    protected function flushOldLTSessions() {
        if(isset($this->ltSessionCallbacks['flushOldLTSessions']))
            return call_user_func($this->ltSessionCallbacks['flushOldLTSessions']);
    }

    /**
     * -------------------------------------------------------------------------
     * COOKIE MANAGER
     * -------------------------------------------------------------------------
     */

    /**
     * Set the long-term cookie on client side
     * @param string $login user login
     * @param string $sid    session id
     */
    protected function setLTCookie($login, $sid) {
        $this->ltCookie['login'] = $login;
        $this->ltCookie['sid'] = $sid;

        //set or update the long term session on client-side
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
     * Delete the long-term cookie on client side
     */
    protected function unsetLTCookie() {
        //delete long-term cookie client-side
        setcookie(
            $this->LTSessionName,
            null,
            time()-31536000,
            dirname($_SERVER['SCRIPT_NAME']).'/',
            '',
            false,
            true
        );
        $this->ltCookie = false;
    }

    /**
     * Load long-term cookie informations
     */
    protected function loadLTCookie() {
        if( isset($_COOKIE[$this->LTSessionName]) ) {
            $this->ltCookie = array();
            $cookieValues = explode('_', $_COOKIE[$this->LTSessionName], 2);
            $this->ltCookie['login'] = $cookieValues[0];
            $this->ltCookie['sid'] = $cookieValues[1];
        }
    }

    /**
     * Test if the client has a long-term cookie set
     * @return bool if the cookie exists or not
     */
    protected function issetLTCookie() {
        return (isset($this->ltCookie) && !empty($this->ltCookie));
    }

    /**
     * -------------------------------------------------------------------------
     * AUTHENTICATION CONTROLLER
     * -------------------------------------------------------------------------
     */

    /**
     * For dev/debug purpose only: turn the current session to "non-secure"
     */
    public function unsecure() {
        $this->initPHPSession();
        $_SESSION['secure'] = false;

        //to avoid any problem when using the browser's back button
        header('Location: '.$this->redirectionPage);
    }

    /**
     * Log the user out and redirect him
     */
    public function logOut() {
        $userName = '';
        $this->initPHPSession();

        //determine user name
        if(isset($_SESSION['login'])) {
            $userName = $_SESSION['login'];
        } elseif($this->useLTSessions && $this->issetLTCookie()) {
            $userName = $this->ltCookie['login'];
        }

        //if user wasn't automatically logged out before asking to log out
        if(!empty($userName)) {
            //unset long-term session
            if($this->useLTSessions)
                $this->unsetLTSessions($userName);
            $this->unsetLTCookie();
        }

        //unset PHP session
        $this->unsetSessionVar('sid');
        $this->unsetSessionVar('login');
        $this->unsetSessionVar('secure');
        $cookieDir = (dirname($_SERVER['SCRIPT_NAME'])!='/') ? dirname($_SERVER['SCRIPT_NAME']) : '';
        session_set_cookie_params(time()-31536000, $cookieDir, $_SERVER['SERVER_NAME']);

        if($this->activateLog) { YosLoginTools::log($this->logFile, 'manual logout '.$userName); }

        if($this->isPHPSessionStarted()) {
            session_destroy();
        }

        //to avoid any problem when using the browser's back button
        header('Location: '.$this->redirectionPage);
        exit;
    }

    /**
     * Try to log the user in
     * @param  string  $login      login sent via sign in form
     * @param  string  $password   clea password sent via sign in form
     * @param  boolean $rememberMe wether we should use a long-term session or not
     * @return array()             user informations (from getUser()) + the values of 'isLoggedIn' and optionally 'error'
     */
    public function logIn($login, $password, $rememberMe = false) {
        $user = array();
        $this->initPHPSession();

        //find user
        $user = $this->getUser($login);

        //check user/password
        if(empty($user)) {
            $user = array();
            $user['error']['unknownLogin'] = true;
            $user['isLoggedIn'] = false;
        } elseif(!YosLoginTools::checkPassword($password, $user['password'])) {
            $user['error']['wrongPassword'] = true;
            $user['isLoggedIn'] = false;
        } else {
            //set session
            $_SESSION['login'] = $user['login'];
            $_SESSION['ip'] = YosLoginTools::getIpAddress($this->allowLocalIp);
            $_SESSION['secure'] = true; //session is scure, for now
            $user['secure'] = $_SESSION['secure'];
            $user['isLoggedIn'] = true;

            if($this->activateLog) { YosLoginTools::log($this->logFile, 'manual login '.$login); }

            //also create a long-term session
            if($rememberMe && $this->useLTSessions) {
                $_SESSION['sid'] = YosLoginTools::generateRandomString(42, true);

                if(!empty($_SESSION['sid'])) {
                    $this->setLTCookie($_SESSION['login'], $_SESSION['sid']);
                    $this->setLTSession($this->ltCookie['login'], $this->ltCookie['sid'], array());

                    //maintenance: delete old sessions
                    $this->flushOldLTSessions();
                } else {
                    //make sure there is no lt sid set
                    $this->unsetSessionVar('sid');
                }
            }

            //to avoid any problem when using the browser's back button
            header('Location: '.$this->redirectionPage);
            exit;
        }

        //wrong login or password: return user with errors
        return $user;
    }

    /**
     * Try to authenticate the user (check if already logged in or not)
     * @param  string $password if user reentered his/her password (example: for admin actions)
     * @return array()          user informations (from getUser()) + the values of 'isLoggedIn'
     */
    public function authUser($password = '') {
        $user = array();
        $this->initPHPSession();

        //user has a PHP session
        if(isset($_SESSION['login']) && isset($_COOKIE[$this->sessionName])) {
            $user = $this->getUser($_SESSION['login']);
            $user['isLoggedIn'] = true;

            if($this->activateLog) { YosLoginTools::log($this->logFile, 'user '.$_SESSION['login'].' is authenticated'); }

            //if ip change, the session isn't secure anymore, even if legitimate
            //  it might be because the user was given a new one
            //  or because if a session hijacking
            if(!isset($_SESSION['ip']) || $_SESSION['ip'] != YosLoginTools::getIpAddress($this->allowLocalIp)) {
                $_SESSION['secure'] = false;
                if($this->activateLog) { YosLoginTools::log($this->logFile, 'note: lost secure access'); }
            }

        //user has LT cookie but no PHP session
        } elseif (isset($_COOKIE[$this->LTSessionName])) {
            //TODO: check if LT session exists on server-side
            $LTSession = $this->useLTSessions?$this->getLTSession($_COOKIE[$this->LTSessionName]):false;

            if($LTSession !== false) {
                //set php session
                $cookieValues = explode('_', $_COOKIE[$this->LTSessionName], 2);
                $_SESSION['login'] = $cookieValues[0];
                $_SESSION['secure'] = false;    //supposedly not secure anymore
                $user = $this->getUser($_SESSION['login']);
                $user['isLoggedIn'] = true;

                //regenerate long-term session
                $this->unsetLTSession($_COOKIE[$this->LTSessionName]);
                $_SESSION['sid']=YosLoginTools::generateRandomString(42, true);
                $this->setLTCookie($_SESSION['login'], $_SESSION['sid']);
                $this->setLTSession($this->ltCookie['login'], $this->ltCookie['sid'], array());

                if($this->activateLog) { YosLoginTools::log($this->logFile, 'reload PHP session for '.$_SESSION['login'], $LTSession); }

            } else {
                if($this->activateLog) { YosLoginTools::log($this->logFile, 'lost both sessions, even if lt cookie exists'); }

                //delete long-term cookie
                $this->unsetLTCookie();

                header('Location: '.$this->redirectionPage);
                exit;
            }

        //user isn't logged in: anonymous
        } else {
            if($this->activateLog) { YosLoginTools::log($this->logFile, 'not logged in'); }
            $user['isLoggedIn'] = false;
        }

        //if a password was given, check it
        if($user['isLoggedIn']) {
            if(!empty($password)) {
                if(YosLoginTools::checkPassword($password, $user['password'])) {
                    $_SESSION['ip'] = YosLoginTools::getIpAddress($this->allowLocalIp);
                    $_SESSION['secure'] = true;

                    if($this->activateLog) { YosLoginTools::log($this->logFile, 'securing access for '.$_SESSION['login']); }

                    header('Location: '.$this->redirectionPage);
                    exit;
                } else {
                    $user['error']['wrongPassword'] = true;
                }
            }
            $user['secure'] = $_SESSION['secure'];
        }

        return $user;
    }
}

/**
 * Useful generic utility functions used in YosLogin
 */
class YosLoginTools {
    /**
     * Generate a random 42 long string with [ . - A-Z a-z 0-9]
     * @param  int    $length        length of the returned string
     * @param  bool   $pathCompliant true to replace '/' with '-'
     *                               false for use in Blowfish salt
     * @return string                the random string
     *                               or an empty string if no id was generated
     */
    public static function generateRandomString($length = 22, $pathCompliant = false) {
        //number of bytes needed to generate a $length long string
        $nbBytes = ceil($length * 6 / 8);

        //$random = file_get_contents('/dev/urandom', false, null, 0, 31);
        $randomBytes = self::secure_random_bytes($nbBytes);
        
        //format the resulting string
        $randomString = base64_encode($randomBytes);
        if(strlen($randomString) >= $length) {
            $randomString = str_replace('+', '.', $randomString);
            if($pathCompliant === true)
                $randomString = str_replace('/', '-', $randomString);
            $randomString = substr($randomString, 0, $length);      //keep only what we need
            //$randomString = str_replace('=', '', $randomString);    //remove trailing '='
        } else {
            $randomString = '';
        }

        return $randomString;
    }

    /**
     * Generate a PHP 5.3+ blowfish-compatible salt
     * @return string the salt
     *                or false if salt malformed
     */
    public static function generateSalt() {
        $random = self::generateRandomString();

        if( version_compare(PHP_VERSION, '5.3.7') >= 0 ) {
            $salt = '$2y$10$';
        } else {
            //PHP 5.3 but < 5.3.7
            $salt = '$2a$10$';
        }
        $salt .= $random;

        //if for some reason the string is not long enough
        if ( strlen($salt) >= 29 )
            return $salt;
        else
            return false;
    }

    /**
     * Hash the given password with a random generated salt using Blowfish
     * @param  string $password the password (clear)
     * @return string           the salt and the hashed password concatenated
     */
    public static function hashPassword($password) {
        $hash = '';

        $salt = self::generateSalt();

        //if salt generation failed
        if($salt !== false) {
            $hash = crypt($password, $salt);
            return $hash;
        } else {
            return false;
        }
    }

    /**
     * Check if the password matches the hash
     * @param  string $password the password to check (clear)
     * @param  string $hash     the salt and the right hashed password concatenated
     * @return bool             true if the passwords match
     */
    public static function checkPassword($password, $hash) {
        //crypt with blowfish takes approximately 0.05 s
        $newHash = crypt($password, $hash);

        return ($newHash == $hash);
    }

    /**
     * get client IP from the best source possible (even through a server proxy)
     * based on: http://stackoverflow.com/questions/1634782/what-is-the-most-accurate-way-to-retrieve-a-users-correct-ip-address-in-php
     * @return string ip address (ipv4 or ipv6)
     */
    public static function getIpAddress($local=false) {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe
                    if ($local === true && filter_var($ip, FILTER_VALIDATE_IP) !== false
                        || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false
                    ){
                        return $ip;
                    }
                }
            }
        }
    }

    public static function log($file, $message, $ltSession = false, $ip = false) {
        $date = date("Y-m-d H:i:s");
        $message = $message." on ".$_SERVER['REQUEST_URI'];
        if(isset($_SESSION))
            $message .= "\n\tSESSION: ".json_encode($_SESSION);
        if(isset($_COOKIE))
            $message .= "\n\tCOOKIE: ".json_encode($_COOKIE);
        if($ltSession !== false)
            $message .= "\n\tLT SESSION: ".json_encode($ltSession);
        if($ip !== false)
            $message .= "\n\tIP: ".$ip;

        if(!file_exists($file)) {
            touch($file);
        }

        $handle = fopen($file, 'a+');
        if($handle) {
            fwrite($handle, $date." - ".$message."\r\n");
        }
        fclose($handle);
    }

    /*
     * Author:
     * George Argyros <argyros.george@gmail.com>
     *
     * Copyright (c) 2012, George Argyros
     * All rights reserved.
     *
     * License:
     * New BSD License
     *
     * Original source:
     * https://github.com/GeorgeArgyros/Secure-random-bytes-in-PHP/
     *
     * Redistribution and use in source and binary forms, with or without
     * modification, are permitted provided that the following conditions are met:
     *    * Redistributions of source code must retain the above copyright
     *      notice, this list of conditions and the following disclaimer.
     *    * Redistributions in binary form must reproduce the above copyright
     *      notice, this list of conditions and the following disclaimer in the
     *      documentation and/or other materials provided with the distribution.
     *    * Neither the name of the <organization> nor the
     *      names of its contributors may be used to endorse or promote products
     *      derived from this software without specific prior written permission.
     *
     * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
     * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
     * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
     * DISCLAIMED. IN NO EVENT SHALL GEORGE ARGYROS BE LIABLE FOR ANY
     * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
     * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
     * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
     * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
     * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
     * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
     *
     *
     *
     * The function is providing, at least at the systems tested :), 
     * $len bytes of entropy under any PHP installation or operating system.
     * The execution time should be at most 10-20 ms in any system.
     */
    public static function secure_random_bytes($len = 10) {
        /*
         * Our primary choice for a cryptographic strong randomness function is
         * openssl_random_pseudo_bytes.
         */
        $SSLstr = '4'; // http://xkcd.com/221/
        if (function_exists('openssl_random_pseudo_bytes')
            && (version_compare(PHP_VERSION, '5.3.4') >= 0
            || substr(PHP_OS, 0, 3) !== 'WIN')
        ) {
            $SSLstr = openssl_random_pseudo_bytes($len, $strong);
            if ($strong)
                return $SSLstr;
        }

        /*
         * If mcrypt extension is available then we use it to gather entropy from
         * the operating system's PRNG. This is better than reading /dev/urandom
         * directly since it avoids reading larger blocks of data than needed.
         * Older versions of mcrypt_create_iv may be broken or take too much time
         * to finish so we only use this function with PHP 5.3 and above.
         */
        if (function_exists('mcrypt_create_iv')
            && (version_compare(PHP_VERSION, '5.3.0') >= 0
            || substr(PHP_OS, 0, 3) !== 'WIN')
        ) {
            $str = mcrypt_create_iv($len, MCRYPT_DEV_URANDOM);
            if ($str !== false)
                return $str;
        }

        /*
         * No build-in crypto randomness function found. We collect any entropy
         * available in the PHP core PRNGs along with some filesystem info and memory
         * stats. To make this data cryptographically strong we add data either from
         * /dev/urandom or if its unavailable, we gather entropy by measuring the
         * time needed to compute a number of SHA-1 hashes.
         */
        $str = '';
        $bits_per_round = 2; // bits of entropy collected in each clock drift round
        $msec_per_round = 400; // expected running time of each round in microseconds
        $hash_len = 20; // SHA-1 Hash length
        $total = $len; // total bytes of entropy to collect

        $handle = @fopen('/dev/urandom', 'rb');
        if ($handle && function_exists('stream_set_read_buffer'))
            @stream_set_read_buffer($handle, 0);

        do {
            $bytes = ($total > $hash_len)? $hash_len : $total;
            $total -= $bytes;

            //collect any entropy available from the PHP system and filesystem
            $entropy = rand() . uniqid(mt_rand(), true) . $SSLstr;
            $entropy .= implode('', @fstat(@fopen( __FILE__, 'r')));
            $entropy .= memory_get_usage();
            if ($handle) {
                $entropy .= @fread($handle, $bytes);
            } else {
                // Measure the time that the operations will take on average
                for ($i = 0; $i < 3; $i ++) {
                    $c1 = microtime(true);
                    $var = sha1(mt_rand());
                    for ($j = 0; $j < 50; $j++) {
                        $var = sha1($var);
                    }
                    $c2 = microtime(true);
                    $entropy .= $c1 . $c2;
                }

                // Based on the above measurement determine the total rounds
                // in order to bound the total running time.   
                $rounds = (int)($msec_per_round*50 / (int)(($c2-$c1)*1000000));

                // Take the additional measurements. On average we can expect
                // at least $bits_per_round bits of entropy from each measurement.
                $iter = $bytes*(int)(ceil(8 / $bits_per_round));
                    for ($i = 0; $i < $iter; $i ++) {
                    $c1 = microtime();
                    $var = sha1(mt_rand());
                    for ($j = 0; $j < $rounds; $j++) {
                        $var = sha1($var);
                    }
                    $c2 = microtime();
                    $entropy .= $c1 . $c2;
                }
            }
            // We assume sha1 is a deterministic extractor for the $entropy variable.
            $str .= sha1($entropy, true);
        } while ($len > strlen($str));

        if ($handle) 
            @fclose($handle);

        return substr($str, 0, $len);
    }
}

?>