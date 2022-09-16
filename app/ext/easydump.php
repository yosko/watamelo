<?php

/**
 * Utility class to easily and beautifully dump PHP variables
 * the functions d() and dd() where inspired Kint
 *
 * @author      Yosko <contact@yosko.net>
 * @version     0.8
 * @copyright   none: free and opensource
 * @link        https://github.com/yosko/easydump
 */
class EasyDump
{
    //display configuration
    public const IS_STATIC = 16;
    public const IS_PUBLIC = 1;
    public const IS_PROTECTED = 2;
    public const IS_PRIVATE = 4;
    public static array $config = array(
        'showCall' => true,    //true to show file name and line number of each call to EasyDump
        'showTime' => true,    //true to show the execution date, time and microsecond of each call
        'showVarNames' => true,    //true to show names of the given variables
        'showSource' => false,   //true to show the code of each PHP call to EasyDump
        'color' => array(   //default theme based on Earthsong by daylerees
            'text' => '#EBD1B7',
            'border' => '#7A7267',
            'background' => '#36312c',
            'name' => '#F8BB39',
            'type' => '#DB784D',
            'value' => '#95CC5E'
        )
    );

    /**
     * For debug purpose only
     * @param mixed $variables any number of variables of any type
     * @throws Exception
     */
    public static function debug()
    {
        $call = null;
        $trace = debug_backtrace();
        if (self::$config['showCall'] || self::$config['showVarNames'] || self::$config['showSource']) {
            $call = self::readCall($trace);
        }

        echo '<pre class="easydump" style="display: block !important; border: 0.5em solid ' . self::$config['color']['border'] . '; color: ' . self::$config['color']['text'] . '; background-color: ' . self::$config['color']['background'] . '; margin: 0; padding: 0.5em; white-space: pre-wrap;font-family:\'DejaVu Sans Mono\',monospace;font-size:11px;text-align:left;min-width:300px;">';

        //show file and line
        if (self::$config['showCall']) {
            self::showCall($call);
        }

        //show file and line
        if (self::$config['showTime']) {
            echo self::microDateTime() . "\r\n";
        }

        //show PHP source of the call
        if (self::$config['showSource']) {
            self::showSource($call);
        }

        //get the variable names (if available)
        $varNames = [];
        if (self::$config['showVarNames']) {
            $varNames = self::guessVarName($call);
        }

        //show the values (with variable names if available)
        foreach ($trace[0]['args'] as $k => $v) {
            self::showVar((self::$config['showVarNames'] ? $varNames[$k] : $k), $v);
        }

        echo '</pre>';
    }

    /**
     * Read information from the backtrace and the PHP file about the call to EasyDump
     * This function uses SplFileObject, only available on PHP 5.1.0+
     *
     * @param array $trace backtrace executed PHP code
     * @return array        information about the call
     */
    protected static function readCall(array $trace): array
    {
        //echo '<pre>'; var_dump(count($trace), $trace);

        //called de()
        if (count($trace) >= 3
            && $trace[1]['function'] === 'debugExit'
            && $trace[2]['function'] === 'de'
        ) {
            $rank = 2;

            //called EasyDump::debugExit() or d()
        } elseif (count($trace) >= 2
            && ($trace[1]['function'] === 'debugExit'
                || $trace[1]['function'] === 'd')
        ) {
            $rank = 1;

            //called EasyDump::debug()
        } else {
            $rank = 0;
        }
        $line = --$trace[$rank]['line'];
        $file = new SplFileObject($trace[$rank]['file']);
        $file->seek($line);
        $call = trim($file->current());
        $callMultiline = $file->current();

        //read the PHP file backward to the beginning of the call
        $regex = '/' . $trace[$rank]['function'] . '\((.*)\);/';
        while (!preg_match($regex, $call, $match)) {
            $file->seek(--$line);
            $call = trim($file->current()) . $call;
            $callMultiline = $file->current() . $callMultiline;
        }
        $call = $match[1];

        $callMultiline = htmlentities($callMultiline);

        return array(
            'code' => $call,
            'formattedCode' => $callMultiline,
            'rank' => $rank,
            'line' => $line + 1,
            'file' => $trace[$rank]['file']
        );
    }

    /**
     * Display the filename and line number where EasyDump was called
     * @param array $call information about the call
     */
    protected static function showCall(array $call)
    {
        echo "<span style=\"color:" . self::$config['color']['type'] . ";\">File \"" . $call['file'] . "\" line " . $call['line'] . ":</span>\r\n";
    }

    protected static function microDateTime(): string
    {
        list($microSec, $timeStamp) = explode(' ', microtime());
        return date('Y-m-d H:i:s.', $timeStamp) . ($microSec * 1000000);
    }

    /**
     * Display the PHP code where EasyDump was called
     * useful for tracking lots of different calls with values/functions as parameters
     * @param array $call information about the call
     */
    protected static function showSource(array $call)
    {
        echo $call['formattedCode']
            . "\r\n"
            . "<span style=\"color:" . self::$config['color']['type'] . ";\">Results:</span>"
            . "\r\n";
    }

    /**
     * Get the variable names used in the function call
     *
     * @param array $call
     * @return array         list of variable names (if available)
     */
    protected static function guessVarName(array $call): array
    {
        $varNames = array();

        $results = self::parse($call['code']);

        foreach ($results as $k => $v) {
            $processString = trim($v);
            if (preg_match('/^\$/', $processString)) {
                $varNames[] = $processString;
            } elseif (is_numeric($processString)
                || $processString[0] === "'"
                || $processString[0] === '"'
                || strpos($processString, 'array') === 0
            ) {
                //TODO: not working for empty string
                $varNames[] = '[value]';
            } elseif (preg_match('([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)', $processString, $matches)) {
                $varNames[] = $processString;
            } else {
                $varNames[] = '[unknown]';
            }
        }

        return $varNames;
    }

    /**
     * Pars PHP code to extract comma separated elements into an array
     * @param string $code PHP code
     * @return array        list of elements
     */
    protected static function parse(string $code): array
    {
        $names = array();
        $currentName = '';

        $depth = 0;
        $escapeNext = false;
        $delimiter = array(
            '(' => ')',
            '[' => ']',
            '{' => '}',
        );
        $inQuotes = '';
        $inDelimiter = '';
        $len = strlen($code);
        for ($i = 0; $i < $len; $i++) {
            $stackChar = true;
            if (!$escapeNext) {
                //escape char inside a string between single/double quotes
                if (!empty($inQuotes) && $code[$i] === '\\') {
                    $escapeNext = true;
                    //leaving a quoted string
                } elseif (!empty($inQuotes) && $code[$i] === $inQuotes) {
                    $inQuotes = '';
                    //entering a quoted string
                } elseif (empty($inQuotes) && ($code[$i] === '\'' || $code[$i] === '"')) {
                    $inQuotes = $code[$i];
                    //recursive use of delimiter, add a level
                } elseif (!empty($inDelimiter) && $code[$i] === $inDelimiter) {
                    $depth++;
                    //recursive use of delimiter, remove a level
                } elseif (!empty($inDelimiter) && $code[$i] === $delimiter[$inDelimiter]) {
                    $depth--;
                    //leaving the parent delimiter
                    if ($depth === 0) {
                        $inDelimiter = '';
                    }
                    //entering a parent delimiter
                } elseif (empty($inDelimiter) && array_key_exists($code[$i], $delimiter)) {
                    $inDelimiter = $code[$i];
                    $depth++;
                    //a root, breaking comma
                } elseif (empty($inDelimiter) && empty($inQuotes) && $code[$i] === ',') {
                    $names[] = $currentName;
                    $currentName = '';
                    $stackChar = false;
                }
            } else {
                $escapeNext = false;
            }

            //add the char to the currently processed name
            if ($stackChar) {
                $currentName .= $code[$i];
            }
        }

        //add the last name to the array
        $names[] = $currentName;
        return $names;
    }

    /**
     * For debug purpose only, used by debug()
     * Recursive (for arrays) function to display variable in a nice formatted way
     *
     * @param string $name name/value of the variable's index
     * @param mixed $value value to display
     * @param int $level for indentation purpose, used in recursion
     * @param int $modifier IS_PUBLIC, IS_PRIVATE, IS_PROTECTED, IS_STATIC
     * @throws Exception
     */
    protected static function showVar(string $name, $value, $level = 0, $modifier = self::IS_PUBLIC, $declaredType = '')
    {
        // deprecated: used to be an (unused) argument
        $dumpArray = false;

        $indent = "    ";
        for ($lvl = 0; $lvl < $level; $lvl++) {
            echo $indent;
        }
        echo '<span style="color:' . self::$config['color']['name'] . ';">' . ($level === 0 ? $name : (is_string($name) ? '"' . $name . '"' : '[' . $name . ']')) . " </span>";
        echo '<span style="color:' . self::$config['color']['type'] . ';">(';
        if ($modifier) {
            if ($modifier >= self::IS_STATIC) {
                $modifier -= self::IS_STATIC;
                echo 'static ';
            }
            echo $modifier == self::IS_PRIVATE ? 'private ' : ($modifier == self::IS_PROTECTED ? 'protected ' : '');
        }
        echo(is_object($value) ? get_class($value) : ($declaredType ?: gettype($value)));
        echo ")</span>\t= ";
        if (!$dumpArray && $level <= 5 && self::isTraversable($value)) {
            $count = 0;
            if (is_array($value)) {
                echo '[';

                foreach ($value as $k => $v) {
                    $count++;
                }
            } else { // object
                echo '{';

                $ref = new ReflectionObject($value);
                foreach ($ref->getProperties() as $prop) {
                    $count++;
                }
            }

            if ($count > 0) {
                echo "\r\n";
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        self::showVar($k, $v, $level + 1);
                    }
                } else { // object
                    foreach ($ref->getProperties() as $prop) {
                        $modifier = $prop->isPrivate() ? self::IS_PRIVATE : ($prop->isProtected() ? self::IS_PROTECTED : self::IS_PUBLIC);
                        if ($prop->isStatic()) {
                            $modifier += self::IS_STATIC;
                        }
                        $prop->setAccessible(true);
                        $name = $prop->getName();
                        // TODO: detect declared type? (as opposed to current value type)
                        if ($prop->isInitialized($value)) {
                            $val = $prop->getValue($value);
                        } else {
                            $val = null;
                        }
                        $type = $prop->getType();
                        if (is_object($type)) {
                            if ($type->allowsNull())
                                $type = '?' . $type->getName();
                            else
                                $type = $type->getName();
                        }

                        self::showVar($name, $val, $level + 1, $modifier, $type);
                    }
                }
                for ($lvl = 0; $lvl < $level; $lvl++) {
                    echo $indent;
                }
            }
            echo is_array($value) ? ']' : '}';
            echo "\r\n";
        } else {
            echo '<span style="color:' . self::$config['color']['value'] . ';">';
            if (is_object($value) || is_resource($value)) {
                ob_start();
                var_dump($value);
                $result = ob_get_clean();
                //trim the var_dump because EasyDump already handle the newline after dump
                echo trim($result);
            } elseif (is_array($value)) {
                echo serialize($value);
            } elseif (is_string($value)) {
                echo '"' . htmlentities($value) . '"';
            } elseif (is_bool($value)) {
                echo $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                echo 'NULL';
            } elseif (is_numeric($value)) {
                echo $value;
            } else {
                echo 'N/A';
            }
            echo "</span>\r\n";
        }
    }

    /**
     * Check if given variable is traversable in any way (array, traversable object even if it doesn't
     * implements Traversable)
     * @param mixed $variable backtrace executed PHP code
     * @return bool           information about the call
     * @throws Exception
     */
    protected static function isTraversable($variable): bool
    {
        //most common cases
        if (is_array($variable) || $variable instanceof StdClass || $variable instanceof Traversable) {
            return true;
        }

        if (!is_object($variable)) {
            return false;
        }

        set_error_handler(function ($errno, $errstr) {
            throw new Exception($errstr, $errno);
        });


        //try to loop through object
        try {
            foreach ($variable as $k => $v) {
                break;
            }
        } catch (Exception $e) {
            restore_error_handler();
            return false;
        }
        restore_error_handler();
        return true;
    }

    /**
     * For debug purpose only. Exits after dump
     * @param mixed $variable the variable to dump
     */
    public static function debugExit()
    {
        call_user_func_array(array(__CLASS__, 'debug'), func_get_args());
        exit;
    }
}

/**
 * Dump variable
 * Alias of EasyDump::debug()
 */
if (!function_exists('d')) {
    function d()
    {
        call_user_func_array(array('EasyDump', 'debug'), func_get_args());
    }
}

/**
 * Dump variable, then exit script
 * Alias of EasyDump::debugExit()
 */
if (!function_exists('de')) {
    function de()
    {
        call_user_func_array(array('EasyDump', 'debugExit'), func_get_args());
    }
}
