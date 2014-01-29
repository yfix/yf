<?php

/*
# Example of using cache functions
function _get_some_cached_data () {
	$name = 'some_name';
	$data = cache_get($name);
	if ($data) {
		return $data;
	}
	sleep(3);
	$data = array('key' => 'some_data');
	cache_set($name, $data);
	return $data;
}
*/

# Default cache driver, with ability to override earlier
if (!conf('CACHE_DRIVER')) {
	conf('CACHE_DRIVER', 'memcache'); // memcache | apc | xcache | eaccelerator | files
}
if (conf('CACHE_DRIVER') == 'memcache' && !function_exists('memcache_connect') && !class_exists('memcached')) { conf('CACHE_DRIVER', ''); }
elseif (conf('CACHE_DRIVER') == 'apc' && !function_exists('apc_fetch')) { conf('CACHE_DRIVER', ''); }
elseif (conf('CACHE_DRIVER') == 'xcache' && !function_exists('xcache_set')) { conf('CACHE_DRIVER', ''); }
elseif (conf('CACHE_DRIVER') == 'eaccelerator' && !function_exists('eaccelerator_get')) { conf('CACHE_DRIVER', ''); }

# TODO: implement $level: '' | 'globals' | 'apc' | 'memcached'
#	if (isset($GLOBALS['CACHE'][$name])) {
#		return $GLOBALS['CACHE'][$name];
#	}
# $GLOBALS['CACHE'][$name] = $result;

if (conf('USE_CACHE') === null) {
	if (defined('USE_CACHE')) {
		conf('USE_CACHE', USE_CACHE);
	}
	// By default we have cache enabled
	$use_cache = true;
	if (isset($PROJECT_CONF['main']['USE_SYSTEM_CACHE'])) {
		$use_cache = (bool)$PROJECT_CONF['main']['USE_SYSTEM_CACHE'];
	}
// TODO: add DEBUG_MODE checking here to not allow no_cache attacks
// TODO: maybe add check for: conf('cache_disable_token', 'something_random')
	if ($_GET['no_core_cache'] || $_GET['no_cache']) {
		$use_cache = false;
	}
	conf('USE_CACHE', $use_cache);
}

if (!isset($GLOBALS['sys_cache'])) {
	$cache_class = 'cache';
	$f = INCLUDE_PATH. 'classes/'.$cache_class.'.class.php';
	if (!file_exists($f)) {
		$cache_class = 'yf_cache';
		$f = (defined('YF_PATH') ? YF_PATH : YF_PATH). 'classes/'.$cache_class.'.class.php';
	}
	if (file_exists($f)) {
		require_once $f;
	}
	if (class_exists($cache_class)) {
		if (!isset($GLOBALS['modules'])) {
			$GLOBALS['modules'] = array();
		}
		$GLOBALS['modules']['cache'] = new $cache_class();
		$GLOBALS['modules']['cache']->_init(conf('CACHE_DRIVER') ? array('driver' => conf('CACHE_DRIVER')) : array());
		$GLOBALS['sys_cache'] =& $GLOBALS['modules']['cache'];
	}
}

if (!function_exists('cache_set')) {
function cache_set($name, $data, $ttl = 3600, $level = '') {
	if (isset($GLOBALS['sys_cache'])) {
		return $GLOBALS['sys_cache']->put($name, $data, $ttl);
	}
	$result = false;
	if (!conf('USE_CACHE')) {
		return false;
	}
	$conf_driver = conf('CACHE_DRIVER');
	if ($conf_driver == 'xcache') {
		$result = xcache_set($name, $data, $ttl);
	} elseif ($conf_driver == 'apc') {
		$result = apc_store($name, $data, $ttl);
	} elseif ($conf_driver == 'eaccelerator') {
		$result = eaccelerator_put($name, $data, $ttl);
	} elseif ($conf_driver == 'memcache') {
		cache_memcached_connect();
		if (isset($GLOBALS['memcache_obj'])) {
			// Check for new 'memcached' php extension: they have different args list with 'memcache'
			if (method_exists($GLOBALS['memcache_obj'], 'getMulti')) {
				$result = $GLOBALS['memcache_obj']->set($name, $data, $ttl);
			} else {
				$result = $GLOBALS['memcache_obj']->set($name, $data, MEMCACHE_COMPRESSED, $ttl);
			}
		}
	}
	return $result;
}
}

if (!function_exists('cache_get')) {
function cache_get($name, $level = '') {
	if (isset($GLOBALS['sys_cache'])) {
		return $GLOBALS['sys_cache']->get($name);
	}
	if (!conf('USE_CACHE')) {
		return false;
	}
// TODO: add DEBUG_MODE checking here to not allow refresh_cache attacks
// TODO: maybe add check for: conf('cache_refresh_token', 'something_random')
	if ($_GET['refresh_cache']) {
		return false;
	}
	$result = false;
	$conf_driver = conf('CACHE_DRIVER');
	if ($conf_driver == 'xcache') {
		$result = xcache_get($name);
	} elseif ($conf_driver == 'apc') {
		$result = apc_fetch($name);
	} elseif ($conf_driver == 'eaccelerator') {
		$result = eaccelerator_get($name);
	} elseif ($conf_driver == 'memcache') {
		cache_memcached_connect();
		if (isset($GLOBALS['memcache_obj'])) {
			$result = $GLOBALS['memcache_obj']->get($name);
		}
	}
	return $result;
}
}

if (!function_exists('cache_del')) {
function cache_del($name, $level = '') {
	if (isset($GLOBALS['sys_cache'])) {
		return $GLOBALS['sys_cache']->del($name);
	}
	if (!conf('USE_CACHE')) {
		return false;
	}
	$conf_driver = conf('CACHE_DRIVER');
	if ($conf_driver == 'xcache') {
		xcache_unset($name);
	} elseif ($conf_driver == 'apc') {
		apc_delete($name);
	} elseif ($conf_driver == 'eaccelerator') {
		eaccelerator_rm($name);
	} elseif ($conf_driver == 'memcache') {
		cache_memcached_connect();
		if ($GLOBALS['memcache_obj']) {
			$result = $GLOBALS['memcache_obj']->delete($name);
		}
	}
}
}

if (!function_exists('cache_multi_set')) {
function cache_multi_set($name, $data, $ttl = 3600, $level = '') {
	if (isset($GLOBALS['sys_cache'])) {
		return $GLOBALS['sys_cache']->multi_set($name, $data, $ttl);
	}
	return null;
}
}

if (!function_exists('cache_multi_get')) {
function cache_multi_get($name, $level = '') {
	if (isset($GLOBALS['sys_cache'])) {
		return $GLOBALS['sys_cache']->multi_get($name);
	}
	return null;
}
}

if (!function_exists('cache_multi_del')) {
function cache_multi_del($name, $level = '') {
	if (isset($GLOBALS['sys_cache'])) {
		return $GLOBALS['sys_cache']->multi_del($name);
	}
	return null;
}
}

if (!function_exists('cache_memcached_connect')) {
function cache_memcached_connect($params = array()) {
	if (isset($GLOBALS['memcache_obj'])) {
		return $GLOBALS['memcache_obj'];
	}
	if (!$params) {
		$conf_host = $GLOBALS['CONF']['MEMCACHED_HOST'];
		$conf_port = $GLOBALS['CONF']['MEMCACHED_PORT'];
		$params = array(
			'host'	=> $conf_host ? $conf_host : '127.0.0.1',
			'port'	=> $conf_port ? $conf_port : 11211,
		);
	}
	$GLOBALS['memcache_obj'] = null;
	if (class_exists('Memcached')) {
		$GLOBALS['memcache_obj'] = new Memcached();
		$GLOBALS['memcache_obj']->addServer($params['host'], $params['port']);
	} elseif (function_exists('memcache_connect')) {
		$GLOBALS['memcache_obj'] = memcache_connect($params['host'], $params['port']);
	}
	return $GLOBALS['memcache_obj'];
}
}
