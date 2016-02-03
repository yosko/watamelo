<?php
namespace Watamelo\Utils;

require_once( ROOT.'/lib/autoload.php');

/**
 * https://github.com/yosko/easydump
 * use it for debugging your app by displaying your variables
 */
require_once( ROOT.'/app/ext/easydump.php');

/**
 * Utility functions (static methods)
 */
class Tools
{
    const PAGINATION_CURRENT = "current";
    const PAGINATION_LINK = "link";
    const PAGINATION_FIRST = "first";
    const PAGINATION_LAST = "last";
    private static $startTime;

    /**
     * Convert "/" directory separator to "\" on windows when working with paths
     * @param  [type] $path [description]
     * @return [type]       [description]
     */
    public static function convertPath($path)
    {
        if (DIRECTORY_SEPARATOR == "\\") {
            $path = str_replace("/","\\",$path);
        }
        return $path;
    }

    /**
     * Check whether a var (string or other) contains an integer
     * @param  misc    $value    the value to check
     * @param  integer $min      minimum value (or false to avoid restriction)
     * @param  integer $max      maximum value (or false to avoid restriction)
     * @return boolean           result of the check
     */
    public static function isInt($value, $min = false, $max = false)
    {
        $options = array();
        if ($min !== false) { $options["min_range"] = $min; }
        if ($max !== false) { $options["max_range"] = $max; }
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
    public static function validateUrl(&$url)
    {
        if (!empty($url) && !preg_match("%^https?://%i", $url)) {
            $url .= 'http://';
        }
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Test if given string is a valid path
     * @param  string  $path path to control
     * @return boolean
     */
    public static function validatePath($path)
    {
        return (preg_match("%^[a-z0-9_-]+$%", $path) != false);
    }

    /**
     * Test if given date is correctly formatted.
     * @param  string  $url    Date to control
     * @param  string  $format Format the date should respect
     * @return boolean
     */
    public static function validateDate($date, $format = 'Y-m-d H:i:s')
    {
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
    public static function checkPasswordFormat($password, $minLength = 6, $reqDiffCharTypes = 2)
    {
        $diffCharTypes = 0;

        //lower case
        if (preg_match("%[a-z]%", $password)) {
            $diffCharTypes++;
        }

        //upper case
        if (preg_match("%[A-Z]%", $password)) {
            $diffCharTypes++;
        }

        //digits
        if (preg_match("%\d%", $password)) {
            $diffCharTypes++;
        }

        //symbols
        if (preg_match("%[-!$\%^&*()_+|~=`{}\[\]:\";'<>?,.\/@]%", $password)) {
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
    public static function checkLoginFormat($login, $minLength = 3, $maxLength = 100)
    {
        //only letters (lower or upper) and the symbols . and - and _
        return (preg_match("%^[a-zA-Z0-9_\-\.]{".$minLength.",".$maxLength."}$%", $login) != false);
    }

    public static function checkPhoneFormat($phone)
    {
        //only numbers and separators (spaces or . or - or _) and an optional leading +
        return (preg_match("%^\+?[0-9 _\-\.]{1,50}$%", $phone) != false);
    }

    /**
     * Checks whether an email is valid
     * @param  [type] $email [description]
     * @return [type]        [description]
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Make sure htmlentities uses the right encoding
     * @param  string  $value text to convert
     * @return string         text converted
     */
    public static function htmlentities($value)
    {
        return htmlentities($value, ENT_NOQUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Make sure html_entity_decode uses the right encoding
     * @param  string  $value text to convert
     * @return string         text converted
     */
    public static function html_entity_decode($value)
    {
        return html_entity_decode($value, ENT_NOQUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Find the best available language for the visitor
     * Taken and adapted from http://php.net/http_negotiate_language
     * @param  array  $available list of available languages (first one is considered default)
     * @return string            language that is considered the best match for this visitor
     */
    public static function negotiateLanguage( $available )
    {
        $accepted = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';

        preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
                   "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
                   $accepted, $hits, PREG_SET_ORDER);

        $bestlang = $available[0];
        $bestqval = 0;
        foreach ($hits as $arr) {
            $langprefix = strtolower($arr[1]);
            if (!empty($arr[3])) {
                $langrange = strtolower($arr[3]);
                $language = $langprefix . "-" . $langrange;
            }
            else $language = $langprefix;
            $qvalue = 1.0;
            if (!empty($arr[5]))
                $qvalue = floatval($arr[5]);

            // find q-maximal language
            if (in_array($language, $available) && $qvalue > $bestqval) {
                $bestlang = $language;
                $bestqval = $qvalue;
            // if no direct hit, try the prefix only but decrease q-value by 10% (as http_negotiate_language does)
            } elseif (in_array($langprefix, $available) && $qvalue*0.9 > $bestqval) {
                $bestlang = $langprefix;
                $bestqval = $qvalue*0.9;
            }
        }
        return $bestlang;
    }

    /**
     * Make sure htmlspecialchars uses the right encoding
     * @param  string  $value text to convert
     * @return string         text converted
     */
    public static function htmlspecialchars($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * check if string starts with given substring
     */
    public static function startsWith($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * check if string ends with given substring
     */
    public static function endsWith($haystack, $needle)
    {
        return substr($haystack, - strlen($needle), strlen($needle)) === $needle;
    }

    /**
     * Starts the time measurement chrono
     */
    public static function startChrono()
    {
        self::$startTime = microtime(true);
    }

    /**
     * Reads the time measurement chrono
     * @return string Formated time in ms
     */
    public static function readChrono()
    {
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
    public static function formatSqliteDate($date, $format = "d/m/Y", $useMonthNames = false)
    {
        if (!$useMonthNames) {
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

    /**
    * Generate an array of pagination link where the key is a page number
    * and the value is a type of link (current page, normal link, first/last page link)
    * @param integer $currentPage the current displayed page
    * @param integer $totalPages the total number of pages (can be replaced by the two following params)
    * @param integer $itemPerPage the number of item displayed on each page
    * @param integer $totalItems the total number of existing items
    * @param integer $nbPagesAround the maximum number of links (excluding first/last) that should be displayed before or after the current page
    * @return array the pagination array (key = page to link to, value = type of link)
    */
    public static function generatePagination($currentPage, $totalPages = 0, $itemPerPage = 0, $totalItems = 0, $nbPagesAround = 2)
    {
        $pagination = array();

        if ($totalPages == 0) {
            if ($itemPerPage == 0 || $totalItems == 0) {
                return false;
            } else {
                $totalPages = (int)ceil($totalItems / $itemPerPage);
            }
        }

        if ($currentPage > $nbPagesAround + 2) {
            $pagination[1] = self::PAGINATION_FIRST;
        } elseif ($currentPage > $nbPagesAround + 1) {
            $pagination[1] = self::PAGINATION_LINK;
        }
        for ($i = $currentPage - $nbPagesAround; $i < $currentPage; $i++) {
            if ($i > 1 || $i == 1 && $currentPage <= $nbPagesAround + 1) {
                $pagination[$i] = self::PAGINATION_LINK;
            }
        }
        $pagination[$currentPage] = self::PAGINATION_CURRENT;
        for ($i = $currentPage + 1; $i < $currentPage + $nbPagesAround + 1; $i++) {
            if ($i < $totalPages || $i == $totalPages && $currentPage >= $totalPages - $nbPagesAround) {
                $pagination[$i] = self::PAGINATION_LINK;
            }
        }
        if ($currentPage < $totalPages - $nbPagesAround - 1) {
            $pagination[$totalPages] = self::PAGINATION_LAST;
        } elseif ($currentPage < $totalPages - $nbPagesAround) {
            $pagination[$totalPages] = self::PAGINATION_LINK;
        }
        // ksort($pagination);

        return $pagination;
    }

    /**
     * Simplify a fraction (divise both numbers by their greitest common divisor)
     */
    public static function simplify($numerator, $denominator)
    {
        $g = Tools::gcd($numerator, $denominator);
        return array('numerator' => $numerator/$g, 'denominator' => $denominator/$g);
    }

    /**
     * Find the GCD (greatest common divisor) of the two given numbers
     */
    public static function gcd($a, $b)
    {
        $a = abs($a); $b = abs($b);
        if ( $a < $b) list($b,$a) = Array($a,$b);
        if ( $b == 0) return $a;
        $r = $a % $b;
        while ($r > 0) {
            $a = $b;
            $b = $r;
            $r = $a % $b;
        }
        return $b;
    }

    /**
     * Returns the number of days in a given month
     * Used in place of PHP's cal_days_in_month() that might be absent of installation
     */
    public static function cal_days_in_month($month, $year)
    {
        return date('t', mktime(0, 0, 0, $month+1, 0, $year));
    }

    /**
     * Delete a directory. Can be recursive
    * @param string $dir       path to directory
    * @param string $recursive true to delete recursively
     */
    public static function rmdir($dir, $recursive = false)
    {
        if (is_dir($dir)) {
            if ($recursive === false) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (filetype($dir."/".$object) == "dir")
                            rrmdir($dir."/".$object);
                        else
                            unlink($dir."/".$object);
                    }
                }
                reset($objects);
            }
            return rmdir($dir);
        } else {
            return false;
        }
    }

    /**
     * Dumps variables in a log file
     * Based on EasyDump
     */
    public static function dumpLog()
    {
        $args = func_get_args();
        array_unshift($args, date('Y-m-d H:i:s'));

        EasyDump::$config['showCall'] = false;
        EasyDump::$config['showVarNames'] = false;

        ob_start();
        call_user_func_array( array( 'EasyDump', 'debug' ), $args );
        $output = ob_get_clean();

        EasyDump::$config['showCall'] = true;
        EasyDump::$config['showVarNames'] = false;

        return file_put_contents(DUMP_PATH, $output, FILE_APPEND);
    }

    /**
     * Empty the dump log file
     * Based on EasyDump
     */
    public static function emptyDumpLog()
    {
        return file_put_contents(DUMP_PATH, '');
    }

    /**
     * Send an email in HTML format
     * Subject used is the first <h1> of the message
     * @param  string  $to      destination email
     * @param  string  $from    source email
     * @param  string  $message email content (in html)
     * @param  string  $cc      possible cc destination emails
     * @param  string  $bcc     possible bcc destination emails
     * @return boolean          true if email successfully accepted for delivery
     */
    public static function sendHTMLMail($to, $from, $subject, $message, $cc = '', $bcc = '')
    {
        $headers = "From: " . $from . "\r\n";
        $headers .= "Reply-To: ". $from . "\r\n";
        if (!empty($cc))
            $headers .= "CC: " . $cc . "\r\n";
        if (!empty($bcc))
            $headers .= "BCC: " . $bcc . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        return mail($to, $subject, $message, $headers);
    }

    /**
     * Format a floating number for display
     * @param  string  $bcc     possible bcc destination emails
     * @return boolean          true if email successfully accepted for delivery
     */
    public static function formatFloatForDisplay($value, $digits = 2, $hideZeros = true)
    {
        $value = round($value, $digits);
        if ($hideZeros && $value == round($value, 0)) {
            $value = (int)$value;
        }
        $value = str_replace('.', ',', $value);
        return $value;
    }
}
