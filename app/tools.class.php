<?php

require_once( ROOT.'/lib/autoload.php');

/**
 * https://github.com/yosko/easydump
 * use it for debugging your app by displaying your variables
 */
require_once( ROOT.'/app/ext/easydump.php');

/**
 * Utility functions (static methods)
 */
class Tools {
    private static $startTime;

    /**
     * Check whether a var (string or other) contains an integer
     * @param  misc    $value    the value to check
     * @param  integer $min      minimum value (or false to avoid restriction)
     * @param  integer $max      maximum value (or false to avoid restriction)
     * @return boolean           result of the check
     */
    public static function isInt($value, $min = false, $max = false) {
        $options = array();
        if($min !== false) { $options["min_range"] = $min; }
        if($max !== false) { $options["max_range"] = $max; }
        return filter_var(
            $value,
            FILTER_VALIDATE_INT,
            array("options" => $options)
        ) !== false;
    }

    /**
     * Test if given url is correctly formatted. /!\ Adds a 'http://' if needed
     * @param  string  $url Url passed by reference
     * @return boolean
     */
    public static function validateUrl(&$url) {
        if(!empty($url) && !preg_match("%^https?://%i", $url)) {
            $url .= 'http://';
        }
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    public static function validatePath($path) {
        return (preg_match("%^[a-z0-9_-]+$%", $path) != false);
    }

    public static function validateDate($date, $format = 'Y-m-d H:i:s') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    /**
     * Checks whether a password is strong enough
     * @param  string  $password         the password to check
     * @param  integer $minLength        the minimum length required
     * @param  integer $reqDiffCharTypes the number of different types of characters required
     *                                   eg: lower case, upper case, digits, symbols
     * @return boolean                   true if password checks all requirements
     */
    public static function checkPasswordFormat($password, $minLength = 6, $reqDiffCharTypes = 2) {
        $diffCharTypes = 0;

        //lower case
        if(preg_match("%[a-z]%", $password)) {
            $diffCharTypes++;
        }

        //upper case
        if(preg_match("%[A-Z]%", $password)) {
            $diffCharTypes++;
        }

        //digits
        if(preg_match("%\d%", $password)) {
            $diffCharTypes++;
        }

        //symbols
        if(preg_match("%[-!$\%^&*()_+|~=`{}\[\]:\";'<>?,.\/@]%", $password)) {
            $diffCharTypes++;
        }

        return (strlen($password) >= $minLength && $diffCharTypes >= $reqDiffCharTypes);
    }

    /**
     * Checks whether a login respects a specific format
     * @param  string  $login     the login to check
     * @param  integer $minLength minimum length required
     * @param  integer $maxLength maximum length required
     * @return boolean            true if login checks requirements
     */
    public static function checkLoginFormat($login, $minLength = 3, $maxLength = 100) {
        //only letters (lower or upper) and the symbols - and _
        return (preg_match("%^[a-z0-9_-]{".$minLength.",".$maxLength."}$%", $login) != false);
    }

    /**
     * Checks whether an email is valid
     * @param  [type] $email [description]
     * @return [type]        [description]
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
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

    /**
     * Make sure htmlspecialchars uses the right encoding
     * @param  string  $value text to convert
     * @return string         text converted
     */
    public static function htmlspecialchars($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * check if string starts with given substring
     */
    public static function startsWith($haystack, $needle) {
        return (substr($haystack, 0, strlen($needle)) === $needle);
    }

    /**
     * Starts the time measurement chrono
     */
    public static function startChrono() {
        self::$startTime = microtime(true);
    }

    /**
     * Reads the time measurement chrono
     * @return string Formated time in ms
     */
    public static function readChrono() {
        return number_format(microtime(true)-self::$startTime, 6);
    }

    /**
     * Format a date string coming from sqlite
     * @param  string  $date          sqlite date (yyyy-mm-dd hh:ii:ss)
     * @param  string  $format        see format definition for date() or DateTime::createFromFormat() functions
     *                                http://www.php.net/manual/en/function.date.php
     * @param  boolean $useMonthNames if true, month number will be replaced by its name
     * @return string                 formatted date
     */
    public static function formatSqliteDate($date, $format = "d/m/Y", $useMonthNames = false) {
        if(!$useMonthNames) {
            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $date);
            $result = $dateTime->format($format);

            return $result;
        } else {
            $values = preg_split( "/(-| |:)/", $date );
            $formattedDate = $format;

            $patterns = array(
                "/Y/",
                "/m/",
                "/d/",
                "/H/",
                "/i/",
                "/s/",
                "/P/"
            );
            $replacements = array(
                $values[0],
                Tools::getMonthName($values[1]),
                $values[2],
                $values[3],
                $values[4],
                $values[5],
                date('P')
            );

            return preg_replace($patterns, $replacements, $formattedDate);
        }
    }
}

?>