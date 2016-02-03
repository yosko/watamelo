<?php

/**
 * Autoloads classes from library, controllers or models
 * @param  string $class class name (case insensitive)
 */
function autoload($class)
{
    $parts = explode('\\', $class);
    $className = strtolower(end($parts));

    if (file_exists( ROOT.'/lib/'.$className.'.class.php' )) {
		require_once( ROOT.'/lib/'.$className.'.class.php' );
	} else if (file_exists( ROOT.'/app/controller/'.$className.'.class.php' )) {
		require_once( ROOT.'/app/controller/'.$className.'.class.php' );
	} else if (file_exists( ROOT.'/app/model/'.$className.'.class.php' )) {
		require_once( ROOT.'/app/model/'.$className.'.class.php' );
	} else {
		//TODO: error handling
	}
}

//register the autoload function
spl_autoload_register('autoload');
