<?php

// Fast process help tooltip
function _fast_tooltip () {
	$tip_info = array();
	// Try to get tip id
	$TIP_ID = substr($_REQUEST['id'], strlen('help_'));
	if (empty($TIP_ID) && !main()->USE_SYSTEM_CACHE) {
		return false;
	}
	if (main()->USE_SYSTEM_CACHE && empty($tip_info)) {
		clearstatcache();
		$locale_specific = '___'.(DEFAULT_LANG != 'DEFAULT_LANG' ? DEFAULT_LANG : 'en');
		$cache_file = INCLUDE_PATH.'core_cache/cache_tips'.$locale_specific.'.php';
		if (!file_exists($cache_file)) {
			return false;
		}
		// Delete expired cache files
		$last_modified = filemtime($cache_file);
		$TTL = 86400;
		$cache_ttl = module_conf('cache', 'FILES_TTL');
		if (!empty($cache_ttl)) {
			$TTL = intval($cache_ttl);
		}
		if ($last_modified < (time() - $TTL)) {
			return false;
		}
		// Get data from file
		$tips_array = eval('return '.substr(file_get_contents($cache_file), 7, -4).';');
		$tip_info = $tips_array[$TIP_ID];
	}
	main()->NO_GRAPHICS = true;
	// Display data
	if (empty($tip_info)) {
		$body = 'No info';
	} else {
		$body = nl2br(stripslashes($tip_info['text']));
	}
	echo $body;

	return true; // Means success
}
