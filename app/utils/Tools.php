<?php

namespace Watamelo\Utils;

use DateTime;
use EasyDump;

/**
 * Utility functions (static methods)
 */
class Tools
{
    public const DUMP_PATH = 'tmp/logs/dump.html';
    public const PAGINATION_CURRENT = "current";
    public const PAGINATION_LINK = "link";
    public const PAGINATION_FIRST = "first";
    public const PAGINATION_LAST = "last";
    private static float $startTime;

    /**
     * Convert "/" directory separator to "\" on windows when working with paths
     * @param string $path
     * @return string
     */
    public static function convertPath(string $path): string
    {
        if (DIRECTORY_SEPARATOR == "\\") {
            $path = str_replace("/", "\\", $path);
        }
        return $path;
    }

    /**
     * Check whether a var (string or other) contains an integer
     * @param mixed $value the value to check
     * @param int|false $min minimum value (or false to avoid restriction)
     * @param int|false $max maximum value (or false to avoid restriction)
     * @return bool           result of the check
     */
    public static function isInt($value, $min = false, $max = false): bool
    {
        $options = array();
        if ($min !== false) {
            $options["min_range"] = $min;
        }
        if ($max !== false) {
            $options["max_range"] = $max;
        }
        return filter_var(
                $value,
                FILTER_VALIDATE_INT,
                array("options" => $options)
            ) !== false;
    }

    /**
     * Test if given url is correctly formatted. /!\ Adds a 'http://' if needed
     * @param string $url Url passed by reference
     * @return bool
     */
    public static function validateUrl(string &$url): bool
    {
        if (!empty($url) && !preg_match("%^https?://%i", $url)) {
            $url .= 'http://';
        }
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Test if given string is a valid path
     * @param string $path path to control
     * @return bool
     */
    public static function validatePath(string $path): bool
    {
        return (preg_match("%^[a-z0-9_-]+$%", $path) != false);
    }

    /**
     * Test if given date is correctly formatted.
     * @param $date
     * @param string $format Format the date should respect
     * @return bool
     */
    public static function validateDate(string $date, string $format = 'Y-m-d H:i:s'): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    /**
     * Checks whether a password is strong enough
     * @param string $password the password to check
     * @param int $minLength the minimum length required
     * @param int $reqDiffCharTypes the number of different types of characters required
     *                                   eg: lower case, upper case, digits, symbols
     * @return bool                   true if password checks all requirements
     */
    public static function checkPasswordFormat(string $password, int $minLength = 6, int $reqDiffCharTypes = 2): bool
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
        if (preg_match("%[-!$\%^&*()_+|~=`{}\[\]:\";'<>?,./@]%", $password)) {
            $diffCharTypes++;
        }

        return (strlen($password) >= $minLength && $diffCharTypes >= $reqDiffCharTypes);
    }

    /**
     * Checks whether a login respects a specific format
     * @param string $login the login to check
     * @param int $minLength minimum length required
     * @param int $maxLength maximum length required
     * @return bool            true if login checks requirements
     */
    public static function checkLoginFormat(string $login, int $minLength = 3, int $maxLength = 100): bool
    {
        //only letters (lower or upper) and the symbols . and - and _
        return (preg_match("%^[a-zA-Z0-9_\-.]{" . $minLength . "," . $maxLength . "}$%", $login) != false);
    }

    public static function checkPhoneFormat($phone): bool
    {
        //only numbers and separators (spaces or . or - or _) and an optional leading +
        return (preg_match("%^\+?[0-9 _\-.]{1,50}$%", $phone) != false);
    }

    /**
     * Checks whether an email is valid
     * @param string $email
     * @return mixed [type]        [description]
     */
    public static function validateEmail(string $email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Make sure htmlentities uses the right encoding
     * @param string $value text to convert
     * @return string         text converted
     */
    public static function htmlentities(string $value): string
    {
        return htmlentities($value, ENT_NOQUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Make sure html_entity_decode uses the right encoding
     * @param string $value text to convert
     * @return string         text converted
     */
    public static function html_entity_decode(string $value): string
    {
        return html_entity_decode($value, ENT_NOQUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Find the best available language for the visitor
     * Taken and adapted from http://php.net/http_negotiate_language
     * @param array $available list of available languages (first one is considered default)
     * @return string            language that is considered the best match for this visitor
     */
    public static function negotiateLanguage(array $available): string
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
            } else {
                $language = $langprefix;
            }
            $qvalue = 1.0;
            if (!empty($arr[5])) {
                $qvalue = floatval($arr[5]);
            }

            // find q-maximal language
            if (in_array($language, $available) && $qvalue > $bestqval) {
                $bestlang = $language;
                $bestqval = $qvalue;
                // if no direct hit, try the prefix only but decrease q-value by 10% (as http_negotiate_language does)
            } elseif (in_array($langprefix, $available) && $qvalue * 0.9 > $bestqval) {
                $bestlang = $langprefix;
                $bestqval = $qvalue * 0.9;
            }
        }
        return $bestlang;
    }

    /**
     * check if string starts with given substring
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * check if string ends with given substring
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, -strlen($needle), strlen($needle)) === $needle;
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
     * @return string formatted time in ms
     */
    public static function readChrono(): string
    {
        return number_format(microtime(true) - self::$startTime, 6);
    }

    /**
     * Generate an array of pagination link where the key is a page number
     * and the value is a type of link (current page, normal link, first/last page link)
     * @param int $currentPage the current displayed page
     * @param int $totalPages the total number of pages (can be replaced by the two following params)
     * @param int $itemPerPage the number of item displayed on each page
     * @param int $totalItems the total number of existing items
     * @param int $nbPagesAround the maximum number of links (excluding first/last) that should be displayed before or after the current page
     * @return array|false the pagination array (key = page to link to, value = type of link)
     */
    public static function generatePagination(
        int $currentPage,
        int $totalPages = 0,
        int $itemPerPage = 0,
        int $totalItems = 0,
        int $nbPagesAround = 2
    )
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
     * Simplify a fraction (divide both numbers by their greatest common divisor)
     * @param int $numerator
     * @param int $denominator
     * @return float[]|int[]
     */
    public static function simplify(int $numerator, int $denominator): array
    {
        $g = Tools::gcd($numerator, $denominator);
        return array('numerator' => $numerator / $g, 'denominator' => $denominator / $g);
    }

    /**
     * Find the GCD (greatest common divisor) of the two given numbers
     * @param int $a
     * @param int $b
     * @return int
     */
    public static function gcd(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);
        if ($a < $b) {
            list($b, $a) = array($a, $b);
        }
        if ($b == 0) {
            return $a;
        }
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
     * @param int $month
     * @param int $year
     * @return false|string
     */
    public static function cal_days_in_month(int $month, int $year)
    {
        return date('t', mktime(0, 0, 0, $month + 1, 0, $year));
    }

    /**
     * Delete a directory. Can be recursive
     * @param string $dir path to directory
     * @param bool $recursive true to delete recursively
     * @return bool success status
     */
    public static function rmdir(string $dir, bool $recursive = false): bool
    {
        if (is_dir($dir)) {
            if ($recursive === false) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (filetype($dir . "/" . $object) == "dir") {
                            rmdir($dir . "/" . $object);
                        } else {
                            unlink($dir . "/" . $object);
                        }
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
        call_user_func_array(array('EasyDump', 'debug'), $args);
        $output = ob_get_clean();

        EasyDump::$config['showCall'] = true;
        EasyDump::$config['showVarNames'] = false;

        return file_put_contents(self::DUMP_PATH, $output, FILE_APPEND);
    }

    /**
     * Empty the dump log file
     * Based on EasyDump
     */
    public static function emptyDumpLog()
    {
        return file_put_contents(self::DUMP_PATH, '');
    }

    /**
     * Send an email in HTML format
     * Subject used is the first <h1> of the message
     * @param string $to destination email
     * @param string $from source email
     * @param string $subject
     * @param string $message email content (in html)
     * @param string $cc possible cc destination emails
     * @param string $bcc possible bcc destination emails
     * @return bool          true if email successfully accepted for delivery
     */
    public static function sendHTMLMail(string $to, string $from, string $subject, string $message, string $cc = '', string $bcc = ''): bool
    {
        $headers = "From: " . $from . "\r\n";
        $headers .= "Reply-To: " . $from . "\r\n";
        if (!empty($cc)) {
            $headers .= "CC: " . $cc . "\r\n";
        }
        if (!empty($bcc)) {
            $headers .= "BCC: " . $bcc . "\r\n";
        }
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        return mail($to, $subject, $message, $headers);
    }

    /**
     * Format a floating number for display
     * @param $value
     * @param int $digits
     * @param bool $hideZeros
     * @return int|string|string[]
     */
    public static function formatFloatForDisplay(float $value, int $digits = 2, bool $hideZeros = true)
    {
        $value = round($value, $digits);
        if ($hideZeros && $value == round($value)) {
            $value = (int)$value;
        }
        $value = str_replace('.', ',', $value);
        return $value;
    }

    public static function compareFloats(float $a, float $b, $epsilon = PHP_FLOAT_EPSILON): bool
    {
        return abs($a - $b) < $epsilon;
    }
}
