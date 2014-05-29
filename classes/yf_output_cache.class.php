<?php

/**
* Class to handle output caching
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
load('cache', 'framework', 'classes/');
class yf_output_cache extends yf_cache {

	/** @var bool Output caching on/off */
	public $OUTPUT_CACHING			= false;
	/** @var int Output cache TTL, in seconds (0 - for unlimited) */
	public $OUTPUT_CACHE_TTL		= 604800; // 1 week (7 * 24 * 60 * 60)
	/** @var string Output cache dir (relative to the SITE_PATH constant) */
	public $OUTPUT_CACHE_DIR		= 'pages_cache/';
	/** @var array Stop-list for output caching (REGEXPs allowed here) @conf_skip */
	public $_OC_STOP_LIST			= array(
		'object=(account|advert|aff|email|forum|manage_escorts|que|reviews|reviews_search|stats|task_loader|user_info|user_profile).*',
		'object=search&+',
		'task=(login|logout).*',
		'debug_mode',
	);
	/** @var string Use instead of '_OC_STOP_LIST', include _ONLY_ that is matched this pattern, will be checked if non-empty */
	public $WHITE_LIST_PATTERN		= '';
	/** @var array Use this if you need to have some page cached different from the global setting 'OUTPUT_CACHE_TTL' 
	*		NOTE: NOT WORKING ON WINDOWS (php < 5.3.0) ! (because we are using touch function that is broken under windows)
	*/
	public $CUSTOM_CACHE_TTLS		= array(
//		'object=(user_profile)'		=> 20,
	);
	/** @var bool @conf_skip Tells that current page will not be cached (default) */
	public $NO_NEED_TO_CACHE		= false;
	/** @var bool Append string to the end of the cached file */
	public $CACHE_APPEND_STRING		= true;
	/** @var string PHP code that will be eval'ed and added to the cache file @conf_skip */
	public $APPEND_STRING_CODE		= 'return PHP_EOL."<!-- cache generated at ".date("Y-m-d H:i:s")." in ".common()->_format_time_value(microtime(true) - main()->_time_start)." secs -->".PHP_EOL;';
	/** @var string Namespace for drivers other than 'file' */
	public $CACHE_NS				= 'oc_';
	/** @var bool Allow to refresh cache from url */
	public $CACHE_CONTROL_FROM_URL	= true;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Module constructor
	*/
	function _init ($params = array()) {
		// Assign group_id = 1 for guests
		if (empty($_SESSION['user_group'])) {
			$_SESSION['user_group'] = 1;
		}
		$this->OUTPUT_CACHING	= main()->OUTPUT_CACHING;

		if ($_SESSION['user_group'] > 1 || $_COOKIE['member_id']) {
			main()->OUTPUT_CACHING	= false;
			$this->OUTPUT_CACHING	= false;
		}
		// Ability to handle output cache through http query
		if ($this->OUTPUT_CACHING && $this->CACHE_CONTROL_FROM_URL) {
			// Display current page without as it is without cache
			if (isset($_GET['no_cache']) || false !== strpos($_SERVER['REQUEST_URI'], '?no_cache')) {
				conf('no_output_cache', true);
			}
			if (isset($_GET['refresh_cache']) || false !== strpos($_SERVER['REQUEST_URI'], '?refresh_cache')) {
				conf('refresh_output_cache', true);
			}
		}
#		$params = array(
#			'driver'	=> 'files',
#		) + (array)$params;
		parent::_init($params);
	}

	/**
	* Output cache file and stop
	*/
	function _process_output_cache () {
		$this->_check_if_need_to_cache();
		if (!$this->OUTPUT_CACHING || $_SERVER['REQUEST_METHOD'] != 'GET' || MAIN_TYPE_ADMIN || $this->NO_NEED_TO_CACHE) {
			return false;
		}
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		$cache_key = $this->CACHE_NS. $this->_get_page_cache_name();
/*
		if ($this->USE_MEMCACHED) {
			// Remove old page from cache (force)
			if (conf('refresh_output_cache')) {
				$this->_memcache->del($cache_key);
				return false;
			}
		} else {
			// Prepare path to the current page cache
			$this->CACHE_FILE_PATH = $this->_prepare_cache_path();
			// Try to process output cache file		
			if (!file_exists($this->CACHE_FILE_PATH)) {
				// Do create empty file to lock current page creation from being used
				file_put_contents($this->CACHE_FILE_PATH, '');
				return false;
			}
			// Get cache last modification time
			$cache_last_modified_time = filemtime($this->CACHE_FILE_PATH);
			// Check if file is locked for generation (prevent parallel creation)
			if (filesize($this->CACHE_FILE_PATH) < 5) {
				// Remove old lock
				$lock_ttl = 600;
				if ($cache_last_modified_time < (time() - $lock_ttl)) {
					unlink($this->CACHE_FILE_PATH);
				}
				return false;
			}
			// Remove old page from cache
			if (($this->OUTPUT_CACHE_TTL != 0 && $cache_last_modified_time < (time() - $this->OUTPUT_CACHE_TTL)) || conf('refresh_output_cache')) {
				unlink($this->CACHE_FILE_PATH);
				return false;
			}
		}
*/
/*
		main()->_IN_OUTPUT_CACHE = true;

		$this->_post_filter();
*/
/*
		if ($this->USE_MEMCACHED) {
			$mc_result = $this->_memcache->get($this->CACHE_NS. $cache_key);
			if (DEBUG_MODE) {
				debug('output_cache::size', strlen($mc_result));
			}
			if (empty($mc_result)) {
				return false;
			}
			if ($this->OUTPUT_CACHE_INCLUDE) {
				eval('?>'.$mc_result.'<?php ');
			} else {
				echo $mc_result;
			}
		} else {
			if (DEBUG_MODE) {
				debug('output_cache::size', filesize($this->CACHE_FILE_PATH));
			}
			if ($this->OUTPUT_CACHE_INCLUDE) {
				include ($this->CACHE_FILE_PATH);
			} else {
				echo file_get_contents($this->CACHE_FILE_PATH);
			}
		}
*/
/*
		$output = ob_get_contents();
		if (DEBUG_MODE) {
			debug('output_cache::exec_time', microtime(true) - $time_start);
		}
		if (DEBUG_MODE || conf('exec_time')) {
			echo common()->_show_execution_time();
		}
		if (DEBUG_MODE) {
			echo common()->show_debug_info();
		}
		$this->_send_http_headers($cache_last_modified_time);
		ob_end_flush();
		main()->NO_GRAPHICS = true;

		exit();
*/
	}

	/**
	* Send HTTP headers
	*/
	function _send_http_headers ($cache_last_modified_time = 0) {
		// Send correct headers
		header('Content-Type: text/html; charset='.conf('charset'), 1);
		header('Content-language: '.conf('language'), 1);
		header('Expires: '.gmdate('D, d M Y H:i:s', $cache_last_modified_time + 600/*$this->OUTPUT_CACHE_TTL*/).' GMT', 1);
		header('Cache-Control: ', 1);
		header('Pragma: ', 1);
		// Set default values:
		$date = gmdate('D, d M Y H:i:s', $cache_last_modified_time).' GMT';
		$etag = ''. md5($date) .'';
		// Check http headers:
		$modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $date : NULL;
		if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && ($timestamp = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])) > 0) {
			$modified_since = $cache_last_modified_time <= $timestamp;
		} else {
			$modified_since = NULL;
		}
		$none_match = !empty($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] == $etag : NULL;
		// The type checking here is very important, be careful when changing entries.
		if (($modified_since !== NULL || $none_match !== NULL) && $modified_since !== false && $none_match !== false) {
			header('HTTP/1.0 304 Not Modified');
			exit();
		}
		// Send appropriate response:
		header('Last-Modified: '.$date, 1);
		header('ETag: '.$etag, 1);
	}

	/**
	* Try to put current page to the output cache
	*/
	function _put_page_to_output_cache ($body = array()) {
		$this->_check_if_need_to_cache();
/*
		if (!$this->OUTPUT_CACHING || $_SERVER['REQUEST_METHOD'] != 'GET' || $this->NO_NEED_TO_CACHE) {
			return false;
		}
		if (common()->_error_exists()) {
			return false;
		}
		if (is_string($body)) {
			$body = array('content' => $body);
		}
		if ($this->USE_MEMCACHED) {
			$cache_key = $this->_get_page_cache_name();
			if ($this->CACHE_APPEND_STRING) {
				$body['content'] .= eval($this->APPEND_STRING_CODE);
			}
			// Special actions when needed
			$body['content'] = $this->_pre_filter($body['content']);
			$this->_memcache->set($this->CACHE_NS. $cache_key, $body['content'], MEMCACHE_COMPRESSED, $this->OUTPUT_CACHE_TTL);
		} else {
			$this->CACHE_FILE_PATH = $this->_prepare_cache_path();
			if (file_exists($this->CACHE_FILE_PATH)) {
				$last_modified = filemtime($this->CACHE_FILE_PATH);
				if ($this->OUTPUT_CACHE_TTL != 0 && $last_modified < (time() - $this->OUTPUT_CACHE_TTL)) {
					unlink($this->CACHE_FILE_PATH);
				}
			}
			if (!file_exists($this->CACHE_FILE_PATH) || filesize($this->CACHE_FILE_PATH) < 5) {
				if ($this->CACHE_APPEND_STRING) {
					$body['content'] .= eval($this->APPEND_STRING_CODE);
				}
				$body['content'] = $this->_pre_filter($body['content']);
				file_put_contents($this->CACHE_FILE_PATH, $body['content']);
				// Set different last_modified time (not working on Windows)
				if (!OS_WINDOWS && !empty($this->CUSTOM_CACHE_TTLS)) {
					$CUSTOM_TTL = 0;
					foreach ((array)$this->CUSTOM_CACHE_TTLS as $_cur_pattern => $_cur_ttl) {
						if (preg_match('/'.$_cur_pattern.'/i', $_SERVER['QUERY_STRING'])) {
							$CUSTOM_TTL = $_cur_ttl;
							break;
						}
					}
					if (!empty($CUSTOM_TTL)) {
						touch($this->CACHE_FILE_PATH, time() + ($CUSTOM_TTL - $this->OUTPUT_CACHE_TTL));
					}
				}
			}
		}
*/
	}

	/**
	* Generate cahe name
	*/
	function _get_page_cache_name () {
		if (!isset($this->_cur_cache_name)) {
			$this->_cur_cache_name = md5(
				$_SERVER['HTTP_HOST']
				.'/'.$_SERVER['SCRIPT_NAME']
				.'?'.$_SERVER['QUERY_STRING']
				.'---'.conf('language')
				.'---'.(int)conf('SITE_ID')
				.'---'.($_SESSION['user_group'] <= 1 ? 'guest' : 'member')
			);
		}
		return $this->_cur_cache_name;
	}

	/**
	* Check if current page need to be cached
	*/
	function _check_if_need_to_cache () {
		// Fast implementation of disabling output caching for the current page
		if (conf('no_output_cache')) {
			$this->NO_NEED_TO_CACHE = true;
			return false;
		}
		if (main()->NO_GRAPHICS && !conf('output_cache_force')) {
			$this->NO_NEED_TO_CACHE = true;
			return false;
		}
		if ($_SESSION['user_group'] > 1 || $_COOKIE['member_id']) {
			$this->NO_NEED_TO_CACHE = true;
			return false;
		}
		// Special for the 'share on facebook' feature
		if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'facebookexternalhit')) {
			$this->NO_NEED_TO_CACHE = true;
			return false;
		}
		// Check 'white' list first
		$w = $this->WHITE_LIST_PATTERN;
		if (!empty($w)) {
			// Array like: 'search' => array(), 'static_pages' => array('show')
			if (is_array($w)) {
				if (!isset($w[$_GET['object']])) {
					$this->NO_NEED_TO_CACHE = true;
					return $this->NO_NEED_TO_CACHE;
				} elseif (!empty($w[$_GET['object']]) && !in_array($_GET['action'], (array)$w[$_GET['object']])) {
					$this->NO_NEED_TO_CACHE = true;
					return $this->NO_NEED_TO_CACHE;
				}
			} else {
				if (!preg_match('/'.$w.'/i', $_SERVER['QUERY_STRING'])) {
					$this->NO_NEED_TO_CACHE = true;
					return $this->NO_NEED_TO_CACHE;
				}
			}
			return false;
		}
		// Try to search current query string in the stop list
		foreach ((array)$this->_OC_STOP_LIST as $pattern) {
			if (preg_match('/'.$pattern.'/i', $_SERVER['QUERY_STRING'])) {
				$this->NO_NEED_TO_CACHE = true;
				return $this->NO_NEED_TO_CACHE;
			}
		}
		return false; // Default return value
	}

	/**
	* Refreshing cache files
	*/
	function refresh ($event = '', $params = array()) {
	}

	/**
	* Do some actions before throw output
	*/
	function _post_filter () {
	}

	/**
	* Do special actions before put to output cache
	*/
	function _pre_filter ($body = '') {
		return $body;
	}

	/**
	* Cleanup all cache files
	*/
	function _cache_refresh_all () {
	}

	/**
	* Refreshing cache files (by given array of hashes)
	*/
	function _clean_by_hashes ($hashes = array()) {
	}

	/**
	* Refreshing cache files (by given direct sql)
	*/
	function _clean_by_sql ($sql = '') {
	}

	/**
	* Refreshing cache files (by given array of params)
	*/
	function _clean_by_params ($params = array()) {
	}

	/**
	* Output cache file and stop
	*/
	function _exec_trigger ($params = array()) {
	}
}
