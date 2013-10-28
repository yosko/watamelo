<?php

require_once( ROOT.'/lib/autoload.php');

/**
 * https://github.com/GeorgeArgyros/Secure-random-bytes-in-PHP
 * used to generate random strings, essentially for SessionManager and UserManager
 */
require_once( ROOT.'/app/ext/srand.php');

/**
 * Utility functions (static methods)
 */
class Tools {
    /**
     * Generate a random 42 long string with [ . - A-Z a-z 0-9]
     * @param  int     $length        length of the returned string
     * @param  boolean $pathCompliant true to replace '/' with '-'
     *                                false for use in Blowfish salt
     * @return string                 the random string
     *                                or an empty string if no id was generated
     */
    public static function generateRandomString($length = 22, $pathCompliant = false) {
        //number of bytes needed to generate a $length long string
        $nbBytes = ceil($length * 6 / 8);

        //$random = file_get_contents('/dev/urandom', false, null, 0, 31);
        $randomBytes = secure_random_bytes($nbBytes);
        
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
        $random = Tools::generateRandomString();
        
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

        $salt = Tools::generateSalt();

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
     * @param  string  $password the password to check (clear)
     * @param  string  $hash     the salt and the right hashed password concatenated
     * @return boolean           true if the passwords match
     */
    public static function checkPassword($password, $hash) {
        //crypt with blowfish takes approximately 0.05 s
        $newHash = crypt($password, $hash);

        return ($newHash == $hash);
    }

    /**
     * get client IP from the best source possible (even through a server proxy)
     * based on: http://stackoverflow.com/questions/1634782/what-is-the-most-accurate-way-to-retrieve-a-users-correct-ip-address-in-php
     * @param  boolean $local whether to authorize local IPs such as 127.0.0.1
     * @return string         ip address (ipv4 or ipv6)
     */
    public static function getIpAddress($local = false) {
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

    public static function log($error) {
        $file = ROOT."/tmp/logs/error.log";
        $date = date("Y-m-d H:i:s");
        $message = $error." on ".$_SERVER['REQUEST_URI'].
                (isset($_SERVER['HTTP_REFERER'])?" (source: ".$_SERVER['HTTP_REFERER']:"")
                .", POST: ".json_encode($_POST)
                .", GET: ".json_encode($_GET).")";
        
        if(!file_exists($file)) {
            touch($file);
        }

        $handle = fopen($file, 'a+');
        if($handle) {
            fwrite($handle, $date." - ".$message."\r\n");
        }
        fclose($handle);
    }

    /**
     * For debug purpose only
     * @param  [misc] $variable the variable to dump
     */
    /**
     * For debug purpose only
     * @param  misc    $variable the variable to dump
     * @param  boolean $exit     whether the program should stop right after the dump
     */
    public static function debug($variable, $exit = false) {
        if(DEVELOPMENT_ENVIRONMENT) {
            echo '<pre>';
            var_dump($variable);
            echo '</pre>';
            if($exit)
                exit;
        }
    }

    /**
     * Make sure htmlentities uses the right encoding
     * @param  string  $value text to convert
     * @return string         text converted
     */
    public static function htmlentities($value) {
        return htmlentities($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Make sure html_entity_decode uses the right encoding
     * @param  string  $value text to convert
     * @return string         text converted
     */
    public static function html_entity_decode($value) {
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }
}

?>