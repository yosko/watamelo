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