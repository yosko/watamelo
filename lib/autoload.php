<?php

/**
 * Autoloads classes from library, controllers or models
 * @param  string $class class name (case insensitive)
 */
spl_autoload_register(function ($class) {
    $parts = explode('\\', $class);
    $parentNamespace = $parts[0];

	if(count($parts) >= 3 && $parts[0] == 'Watamelo') {
		$file = '';
		$subNamespace = $parts[1];
		$className = strtolower($parts[2]);

		if($subNamespace == 'Lib') {
			$file = ROOT.'/lib/'.$className.'.class.php';
		} elseif($subNamespace == 'Controllers') {
			$file = ROOT.'/app/controller/'.$className.'.class.php';
		} elseif($subNamespace == 'Managers') {
			$file = ROOT.'/app/manager/'.$className.'.class.php';
		} elseif($subNamespace == 'Data') {
			$file = ROOT.'/app/model/'.$className.'.class.php';
		} elseif($subNamespace == 'Utils') {
			$file = ROOT.'/app/utils/'.$className.'.class.php';
		} elseif($subNamespace == 'App') {
			$file = ROOT.'/app/'.$className.'.class.php';
		} elseif($subNamespace == 'Plugin' && count($parts) >= 4) {
    		$plugin = $className;
    		$className = strtolower($parts[3]);
			$file = ROOT.'/plugins/'.$plugin.'/'.$className.'.class.php';
		}

		if (file_exists($file)) {
			require_once($file);
		}
	}
});

function isClassInNamespace($namespace, $class) {
    return strncmp($namespace, $class, strlen($namespace)) !== 0;
}
