<?php

return function() {
	if (empty($WHAT)) {
		$WHAT = trim($_GET['id']);
	}
	if (empty($WHAT)) {
		if (false !== ($_pos = strpos($_SERVER['REQUEST_URI'], '?q='))) {
			parse_str(substr($_SERVER['REQUEST_URI'], $_pos + 1), $_tmp);
			$WHAT = $_tmp['q'];
		}
	}
	if (!strlen($WHAT)) {
		return print '';
	}
	$LIMIT = $_tmp['limit'];
	$MAX = 10;
	if (!$LIMIT || $LIMIT > $MAX) {
		$LIMIT = $MAX;
	}

	mb_internal_encoding('utf-8');

	$WHAT = mb_strtolower(urldecode(trim($WHAT)));
	if (!strlen($WHAT)) {
		return print '';
	}
	$FIRST_SYMBOL = mb_substr($WHAT, 0, 1);

	$VERTICAL	= SEARCH_VERTICAL;
	$COUNTRY	= SEARCH_COUNTRY;

	$cache_file = INCLUDE_PATH.'core_cache/cache_autocomplete_'.$VERTICAL.'_'.$COUNTRY.'.php';
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
	include($cache_file);
	// Special entry to cache even empty recordset
	if (isset($data['_'])) {
		unset($data['_']);
	}
	if (!empty($data)) {
		if (empty($data[$FIRST_SYMBOL])) {
			return print '';
		}
		$data = $data[$FIRST_SYMBOL];
		// words filtering
		foreach ((array)$data as $word => $freq) {
			if (!$word || !$freq || strpos($word, $WHAT) !== 0) {
				unset($data[$word]);
			}
		}
	}
	if (empty($data)) {
		return print '';
	}
	// Do output
	main()->NO_GRAPHICS = true;
	foreach ((array)$data as $k => $v) {
		if ($counter++ >= $LIMIT) {
			break;
		}
		echo htmlspecialchars($k, ENT_QUOTES).PHP_EOL;
	}

	// Do not dump exec time directly, because will break autocomplete feature
	main()->_no_fast_init_debug = true;

	return true; // Means success
};
