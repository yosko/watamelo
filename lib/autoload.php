<?php

/**
 * Autoloads classes from library, controllers or models
 * @param  string $class class name (case insensitive)
 */
function autoload($class) {
    if (file_exists( ROOT.'/lib/'.strtolower($class).'.class.php' )) {
		require_once( ROOT.'/lib/'.strtolower($class).'.class.php' );
	} else if (file_exists( ROOT.'/app/controller/'.strtolower($class).'.class.php' )) {
		require_once( ROOT.'/app/controller/'.strtolower($class).'.class.php' );
	} else if (file_exists( ROOT.'/app/model/'.strtolower($class).'.class.php' )) {
		require_once( ROOT.'/app/model/'.strtolower($class).'.class.php' );
	} else {
		//TODO: error handling
	}
}

//register the autoload function
spl_autoload_register('autoload');

?>