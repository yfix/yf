<?php

// Fast throw output cache page
function _fast_output_cache () {
	$MODULE_CONF = module_conf('output_cache');
	if (!conf('language')) {
		conf('language', 'en');
	}
	// Do not use cache for members
	if (!empty($_COOKIE['member_id'])) {
		return false;
	}
	if (isset($_GET['no_cache']) || isset($_GET['refresh_cache'])) {
		return false;
	}
	// Special for the 'share on facebook' feature
	if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'facebookexternalhit')) {
		return false;
	}
	$w = $MODULE_CONF['WHITE_LIST_PATTERN'];
	if (!empty($w)) {
		if (is_array($w)) {
			if (defined('SITE_DEFAULT_PAGE')) {
				@parse_str(substr(SITE_DEFAULT_PAGE, 3), $_tmp);
			}
			$_object = $_GET['object'] ? $_GET['object'] : $_tmp['object'];
			$_action = $_GET['action'] ? $_GET['action'] : 'show';
			if (!isset($w[$_object])) {
				$NO_NEED_TO_CACHE = true;
			} elseif (!empty($w[$_object]) && !in_array($_action, (array)$w[$_object])) {
				$NO_NEED_TO_CACHE = true;
			}
		} elseif (!preg_match('/'.$w.'/i', $_SERVER['QUERY_STRING'])) {
			$NO_NEED_TO_CACHE = true;
		}
	} else {
		foreach ((array)$MODULE_CONF['_OC_STOP_LIST'] as $pattern) {
			if (preg_match('/'.$pattern.'/i', $_SERVER['QUERY_STRING'])) {
				$NO_NEED_TO_CACHE = true;
			}
		}
	}
	if ($_SERVER['REQUEST_METHOD'] != 'GET' || MAIN_TYPE_ADMIN || $NO_NEED_TO_CACHE) {
		return false;
	}
	// Prepare path to the current page cache
	$locale_id = defined('DEFAULT_LANG') ? DEFAULT_LANG : conf('language');
	$cur_cache_name = md5(
		$_SERVER['HTTP_HOST'].
		'/'.$_SERVER['SCRIPT_NAME'].
		'?'.$_SERVER['QUERY_STRING'].
		'---'.$locale_id.
		'---'.(int)conf('SITE_ID').
		'---'.($_SESSION['user_group'] <= 1 ? 'guest' : 'member')
	);
	$CACHE_CONTENTS = '';
/*
	// Memcached code
	if ($MODULE_CONF['USE_MEMCACHED']) {
		$params = conf('MEMCACHED_PARAMS');
		$failed = true;
		if (isset($PARAMS) && is_array($PARAMS)) {
			$mc_obj = null;
			if (class_exists('Memcached')) {
				$mc_obj = new Memcached();
			} elseif (class_exists('Memcache')) {
				$mc_obj = new Memcache();
			} else {
				$client_path = YF_PATH.'libs/memcached/memcached_client.class.php';
				if (file_exists($client_path)) {
					include $client_path;
				}
				if (class_exists('memcached_client')) {
					$mc_obj = new memcached_client();
				}
			}
			if (is_object($mc_obj)) {
				$mc_params = (isset($params['memcache']) && !empty($params['memcache'])) 
					? (is_array($params['memcache']) ? $params['memcache'] : array($params['memcache'])) 
					: array($this->MEMCACHE_DEF_PARAMS);
				$failed = true;
				foreach ((array)$mc_params as $server) {
					if (!is_array($server) || !isset($server['host'])) {
						continue;
					}
					$server['port'] = isset($server['port']) ? (int)$server['port'] : 11211;
					$server['persistent'] = isset($server['persistent']) ? (bool) $server['persistent'] : true;
					if ($mc_obj->addServer($server['host'], $server['port'], $server['persistent'])) {
						$failed = false;
					}
				}
			}
		}
		if (!$failed) {
			$CACHE_CONTENTS = $_memcache->get('oc_'.$cur_cache_name);
		}
	}
*/
	// File-based method
	if (!$CACHE_CONTENTS) {
		$cache_dir	= REAL_PATH. 'pages_cache/';
		$sub_dir	= $cur_cache_name[0].'/'.$cur_cache_name[1].'/';
		if ($MODULE_CONF['SITE_ID_SUBDIR']) {
			$sub_dir = conf('SITE_ID').'/'.$sub_dir;
		}
		$CACHE_FILE_PATH = $cache_dir. $sub_dir. $cur_cache_name .'.cache.php';
		if (!file_exists($CACHE_FILE_PATH)) {
			return false;
		}
		$cache_last_modified_time = filemtime($CACHE_FILE_PATH);
		// Check if file is locked for generation (prevent parallel creation)
		if (filesize($CACHE_FILE_PATH) < 5) {
			// Remove old lock
			$lock_ttl = 600;
			if ($cache_last_modified_time < (time() - $lock_ttl)) {
				unlink($CACHE_FILE_PATH);
			}
			return false;
		}
		$OUTPUT_CACHE_TTL = $MODULE_CONF['OUTPUT_CACHE_TTL'];
		// Remove old page from cache
		if (($OUTPUT_CACHE_TTL != 0 && $cache_last_modified_time < (time() - $OUTPUT_CACHE_TTL)) || conf('refresh_output_cache')) {
			unlink($CACHE_FILE_PATH);
			return false;
		}
		$CACHE_CONTENTS = file_get_contents($CACHE_FILE_PATH);
	}
	if (!$CACHE_CONTENTS) {
		return false;
	}
	main()->NO_GRAPHICS = true;
	main()->_IN_OUTPUT_CACHE = true;
	echo preg_replace('/<\?php.+?\?>/ms', '', $CACHE_CONTENTS);

	$t = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$t .= '#'.$_SERVER['REQUEST_TIME'];
	$t .= '#'.$_SERVER['HTTP_REFERER'];
	$t .= '#'.conf('SITE_ID');
	$t .= '#'.$_SERVER['REMOTE_ADDR'];
	$t .= '#1'; // means page from output cache
	$t .= '#0'; // guest
	$t .= PHP_EOL;
	@file_put_contents(INCLUDE_PATH.'logs/query_log/query.log', $t, FILE_APPEND);

	return true; // Means success
}
