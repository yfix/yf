<?php

// Fast execute php function (specially for the threaded execution)
function _fast_php_func () {
	main()->NO_GRAPHICS = true;

	// Check if console mode
	if ( ! (!empty($_SERVER['argc']) && !array_key_exists('REQUEST_METHOD', $_SERVER) ) ) {
		exit("No direct access to method allowed");
	}
	// Get console params
	$params = array();
	foreach ((array)$_SERVER['argv'] as $key => $argv) {
		if ($argv == '--params' && isset($_SERVER['argv'][$key + 1])) {
			$params = unserialize($_SERVER['argv'][$key + 1]);
			break;
		}
	}

	$func = preg_replace("#[^a-z0-9\_]+#", "", substr(trim($params["func"]), 0, 32));
	if (function_exists($func)) {
		echo $func($params["name"]);
	} else {
		return false;
	}

	main()->_no_fast_init_debug = true;

	return true; // Means success
}
