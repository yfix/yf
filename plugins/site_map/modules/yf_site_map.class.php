<?php

/**
* Site Map Generator (implementation of the http://www.sitemaps.org/protocol.php)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_site_map {

	/** @var bool Enable\disable site map generation */
	public $SITE_MAP_ENABLED		= false;
	/** @var array Array of site modules to include their parts of sitemap */
	public $MODULES_TO_INCLUDE		= array();
	/** @var string Sitemap store folder */
	public $SITEMAP_STORE_FOLDER	= 'site_map/';
	/** @var string Sitemap file name */
	public $SITEMAP_FILE_NAME		= 'site_map';
	/** @var @conf_skip */
	public $HOOK_NAMES				= array('_site_map_items', '_hook_sitemap', '_hook_site_map');
	/** @var mixed */
	public $DEFAULT_LAST_UPDATE		= '';
	/** @var mixed */
	public $DEFAULT_PRIORITY		= '';
	/** @var mixed */
	public $DEFAULT_CHANGEFREQ		= 'daily';
	/** @var bool Notify Google */
	public $NOTIFY_GOOGLE			= false;
	/** @var int Max entries for sitemap file */
	public $MAX_ENTRIES				= 50000;
	/** @var int Max sitemap filesize */
	public $MAX_SIZE				= 10000000;
	/** @var int Limit max URL length to the 2048 symbols*/
	public $MAX_URL_LENGTH			= 2048;
	/** @var int Max number of sitemaps */
	public $MAX_SITEMAPS			= 1000;
	/** @var bool Do not change! It's a flag which is become 'true' if number of sitemap files reached $MAX_SITEMAPS. There will be no actions after this */
	public $LIMIT_REACHED			= false;
	/** @var array Frequency avail values @conf_skip */
	public $CHANGEFREQ_VALUES		= array('always','hourly','daily','weekly','monthly','yearly','never');
	/** @var string @conf_skip */
	public $_notify_url				= '';
	/** @var string @conf_skip */
	public $_file_name				= '';
	/** @var string @conf_skip */
	public $_file_extension			= '.xml';
	/** @var bool Use locking */
	public $USE_LOCKING				= false;
	/** @var int Lock timeout */
	public $LOCK_TIMEOUT			= 600;
	/** @var string Lock file name */
	public $LOCK_FILE_NAME			= 'site_map.lock';
	/** @var int Site map TTL */
	public $SITEMAP_LIVE_TIME		= 43200;	// 60*60*12 = 12hours
	/** @var bool Allow rewrite */
	public $ALLOW_REWRITE			= false;
	/** @var int Rewrite split length */
	public $REWRITE_SPLIT_LENGTH	= 200000;
	/** @var bool */
	public $DIRECT_FILE_OUTPUT		= true;
	/** @var bool */
	public $TEST_MODE				= false;
	/** @var bool */
	public $SECURITY_URL_PARAM		= '';

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* YF module constructor
	*
	* @access	private
	* @return	void
	*/
	function _init () {
		if ($this->USE_LOCKING) {
			$this->LOCK_FILE_NAME = STORAGE_PATH. $this->LOCK_FILE_NAME;
		}
		$this->SITEMAP_WEB_PATH = WEB_PATH. 'uploads/'. $this->SITEMAP_STORE_FOLDER;
		$this->SITEMAP_STORE_FOLDER = PROJECT_PATH. 'uploads/'. $this->SITEMAP_STORE_FOLDER;

		// Calculate size of a string (web path) adding to url when file processed
		$this->_path_size_bytes = strlen(htmlspecialchars(utf8_encode(WEB_PATH)));
		// Leave some space in file size
		$this->MAX_SIZE = $this->MAX_SIZE * 0.9;

		$this->_notify_url = url('/@object');

		if (empty($this->MODULES_TO_INCLUDE)) {
			$this->MODULES_TO_INCLUDE = $this->_get_modules_from_files();
		}
		// Ensure uniqueness of module names
		$tmp = array();
		foreach ((array)$this->MODULES_TO_INCLUDE as $v) {
			$tmp[$v] = $v;
		}
		$this->MODULES_TO_INCLUDE = $tmp;

		// Remove non-active modules
		$active_modules = db()->select('name', 'name AS n2')->from('user_modules')->where('active = 1')->get_2d();
		foreach ($this->MODULES_TO_INCLUDE as $k => $name) {
			if (!isset($active_modules[$name])) {
				unset($this->MODULES_TO_INCLUDE[$k]);
			}
		}
		if ($this->ALLOW_REWRITE && !tpl()->REWRITE_MODE) {
			$this->ALLOW_REWRITE = false;
		}
	}

	/**
	* Default method
	*/
	function show () {
		if ($this->SECURITY_URL_PARAM && $_GET['id'] != $this->SECURITY_URL_PARAM) {
			main()->NO_GRAPHICS = true;
			return print _e('Wrong url');
		}
		return $this->_generate_sitemap();
	}

	/**
	* Sitemap format generator (compatible with Google, Yahoo, MSN etc)
	*
	* https://www.google.com/webmasters/sitemaps/docs/en/about.html
	* XML-Specification: https://www.google.com/webmasters/sitemaps/docs/en/protocol.html
	* 
	* You can use the gzip feature or compress your Sitemap files using gzip. 
	* Please note that your uncompressed Sitemap file may not be larger than 10MB.
	*
	* No support for more the 50.000 URLs at the moment. See
	* http://www.google.com/webmasters/sitemaps/docs/en/protocol.html#sitemapFileRequirements
	*/
	function _generate_sitemap () {
		main()->NO_GRAPHICS = true;
		if (!$this->SITE_MAP_ENABLED) {
			return false;
		}
		// !! Important !!
		// Turn off rewrite adding url params into sitemap urls
		_class('rewrite')->URL_ADD_BUILTIN_PARAMS = false;

		$_sitemap_base_name = $this->SITEMAP_FILE_NAME;
		// Check if needed to recreate sitemap files
		$_path = $this->SITEMAP_STORE_FOLDER. $this->SITEMAP_FILE_NAME.'_index'.$this->_file_extension;
		if (file_exists($_path) && (filemtime($_path) + $this->SITEMAP_LIVE_TIME) > time() && !$this->TEST_MODE) {
			// send headers
			$this->_redirect_sitemap($this->SITEMAP_WEB_PATH. $this->SITEMAP_FILE_NAME. '_index'. $this->_file_extension);
			return false;
		}
		if (!file_exists($_path)){
			$_path = $this->SITEMAP_STORE_FOLDER. $this->SITEMAP_FILE_NAME.'1'.$this->_file_extension;
			if (file_exists($_path) && (filemtime($_path) + $this->SITEMAP_LIVE_TIME) > time() && !$this->TEST_MODE) {
				// send headers
				$this->_redirect_sitemap($this->SITEMAP_WEB_PATH. $this->SITEMAP_FILE_NAME. '1'. $this->_file_extension);
				return false;
			} 
		}
		// Start generate sitemap
		@ignore_user_abort(true);
		@set_time_limit($this->LOCK_TIMEOUT);

		if ($this->USE_LOCKING) {
			clearstatcache();
			if (file_exists($this->LOCK_FILE_NAME)) {
				if ((time() - filemtime($this->LOCK_FILE_NAME)) > $this->LOCK_TIMEOUT) {
					unlink($this->LOCK_FILE_NAME);
				} else {
					return false;
				}
			}
			file_put_contents($this->LOCK_FILE_NAME, time());
		}
		$this->_sitemap_file_counter = 1;		
		_mkdir_m($this->SITEMAP_STORE_FOLDER);
		_class('dir')->delete_files($this->SITEMAP_STORE_FOLDER, '/'.$_sitemap_base_name.'.*/i');

		$this->_fp = fopen($this->SITEMAP_STORE_FOLDER. $this->SITEMAP_FILE_NAME. $this->_sitemap_file_counter. $this->_file_extension, 'w+');
		$this->_total_length = 0;
		$this->_entries_counter = 0;

		$this->_output($this->_tpl_sitemap_header());
		$this->_total_length = strlen($header_text);

		foreach ((array)$this->MODULES_TO_INCLUDE as $module_name) {
			$module_obj = module_safe($module_name);
			if ($this->LIMIT_REACHED || !is_object($module_obj)) {
				continue;
			}
			$hook_name = '';
			foreach ((array)$this->HOOK_NAMES as $_hook_name) {
				if (method_exists($module_obj, $_hook_name)) {
					$hook_name = $_hook_name;
					break;
				}
			}
			if ($hook_name) {
				$items = $module_obj->$hook_name($this);
				if (is_array($items)) {
					foreach ((array)$items as $item) {
						$this->_add($item);
					}
				}
			}
		}
		if (!$this->LIMIT_REACHED) {
			$this->_output($this->_tpl_sitemap_footer());
			@fclose($this->_fp);
		}
		$files = _class('dir')->scan($this->SITEMAP_STORE_FOLDER);
		foreach ((array)$files as $file_name) {
			if (false !== strpos($file_name, '.svn') || false !== strpos($file_name, '.git')) {
				continue;
			}
			$path_info = pathinfo($file_name);
			$_sitemap_filename_pattern = '/^('.$this->SITEMAP_FILE_NAME.')([0-9]{1,3})(\.xml)$/';
			if (!preg_match($_sitemap_filename_pattern, $path_info['basename'])) {
				continue;
			} else {
				$sitemaps[$file_name] = $file_name;
			}
		}
		if ($sitemaps) {
			foreach ((array)$sitemaps as $filename) {
				$this->_process_sitemap_file($filename);
			}
		} 
		$this->_file_for_google = str_replace(PROJECT_PATH, WEB_PATH, $this->SITEMAP_STORE_FOLDER). $this->SITEMAP_FILE_NAME.'1'.$this->_file_extension;
		if ($sitemaps && count($sitemaps) > 1) {
			$this->_output($this->_tpl_sitemap_index_header());

			// Create sitemap index 
			$this->_fp = fopen($this->SITEMAP_STORE_FOLDER.$this->SITEMAP_FILE_NAME.'_index'.$this->_file_extension, 'w+');
			foreach ((array)$sitemaps as $sitemap_file) {
				$string = "\t<sitemap>\n";
				$string .= "\t\t<loc>".str_replace(PROJECT_PATH, WEB_PATH, $sitemap_file)."</loc>\n";
				$last_mod = $this->_iso8601_date(filemtime($sitemap_file));
				if ($last_mod) {
					$string .= "\t\t<lastmod>".$last_mod."</lastmod>\n";
				}
				$string .= "\t</sitemap>\n";
				// Store entry data to file
				$this->_output($string);
			}
			fclose($this->_fp);

			$this->_output($this->_tpl_sitemap_index_footer());

			$this->_file_for_google = str_replace(PROJECT_PATH, WEB_PATH, $this->SITEMAP_STORE_FOLDER) .$this->SITEMAP_FILE_NAME. '_index'. $this->_file_extension;
		}	
		// Release lock
		if ($this->USE_LOCKING) {
			unlink($this->LOCK_FILE_NAME);
		}
		$this->_redirect_sitemap($this->_file_for_google);

		$this->_do_notify_google();
	}

	/**
	* Alias for _store_item
	*/
	function _add ($data = array()) {
		return $this->_store_item($data);
	}

	/**
	* Store sitemap item
	*/
	function _store_item ($data = array()) {
		if (is_string($data) && strlen($data)) {
			$data = array('url' => $data);
		}
		if (empty($data) || empty($data['url'])) {
			return false;
		}
		if ($this->LIMIT_REACHED) {
			return false;
		}
		// Shortcut for calling url('/some_module')
		if (substr($data['url'], 0, 1) === '/' && substr($data['url'], 0, 2) !== '//') {
			$data['url'] = url($data['url']);
		}
		$location = $data['url'];
		if ((strlen($location) + $this->_path_size_bytes) >= $this->MAX_URL_LENGTH) {
			return false;
		}
		if (!$data['last_update'] && $this->DEFAULT_LAST_UPDATE) {
			$data['last_update'] = $this->DEFAULT_LAST_UPDATE;
			if (is_callable($data['last_update'])) {
				$func = $data['last_update'];
				$data['last_update'] = $func($data);
			}
		}
		if (!$data['priority'] && $this->DEFAULT_PRIORITY) {
			$data['priority'] = $this->DEFAULT_PRIORITY;
			if (is_callable($data['priority'])) {
				$func = $data['priority'];
				$data['priority'] = $func($data);
			}
		}
		if (!$data['changefreq'] && $this->DEFAULT_CHANGEFREQ) {
			$data['changefreq'] = $this->DEFAULT_CHANGEFREQ;
			if (is_callable($data['changefreq'])) {
				$func = $data['changefreq'];
				$data['changefreq'] = $func($data);
			}
		}

		$string .= "\t<url>\n";
		$string .= "\t\t<loc>".$location."</loc>\n";
		// Adding size of path string to a total size of data
		$this->_total_length += $this->_path_size_bytes;
		if ($data['last_update']) {
			$string .= "\t\t<lastmod>".$this->_iso8601_date($data['last_update'])."</lastmod>\n";
		}
		if ($data['priority']) {
			$string .= "\t\t<priority>".floatval($data['priority'])."</priority>\n";
		}
		if ($data['changefreq'] && in_array($data['changefreq'], $this->CHANGEFREQ_VALUES)) {
			$string .= "\t\t<changefreq>".$data['changefreq']."</changefreq>\n";
		}
		$string .= "\t</url>\n";

		$this->_total_length += strlen($string);
		$this->_entries_counter++;	
		// Check total length and number of entries before save
		if ($this->_total_length >= $this->MAX_SIZE || $this->_entries_counter >= $this->MAX_ENTRIES) {
			// Finish to write file and create another one
			if ($this->_fp) {
				$this->_output($this->_tpl_sitemap_footer());
				fclose($this->_fp);
			}
			if ($this->_entries_counter >= $this->MAX_ENTRIES || $this->_total_length >= $this->MAX_SIZE) {
				$this->_entries_counter = 0; 
				$this->_total_length = 0; 
			}
			// Check if we reached max sitemap files number
			if ($this->_sitemap_file_counter >= $this->MAX_SITEMAPS){
				$this->LIMIT_REACHED = true;
			}
			if (!$this->LIMIT_REACHED) {
				$this->_sitemap_file_counter++;
				// Create and open to write next sitemap file 
				$this->_fp = fopen($this->SITEMAP_STORE_FOLDER.$this->SITEMAP_FILE_NAME.$this->_sitemap_file_counter.$this->_file_extension, 'w+');

				$this->_output($this->_tpl_sitemap_header());
				$this->_total_length = strlen($this->_tpl_sitemap_header());
			}
		}
		if (!$this->LIMIT_REACHED) {
			$this->_output($string);
		}
	}

	/**
	* Output generated content
	*/
	function _output($string) {
		if ($this->TEST_MODE) {
			if (DEBUG_MODE) {
				echo '<pre>'._prepare_html($string).'</pre>';
			} else {
				header('Content-type: text/xml');
				echo $string;
			}
			return false;
		}
		if ($this->_fp) {
			fwrite($this->_fp, $string);
		} 
	}

	/**
	* Redirect to sitemap source files
	*/
	function _redirect_sitemap($location) {
		if ($this->TEST_MODE) {
			return false;
		}
		if ($this->DIRECT_FILE_OUTPUT) {
			header('Content-type: text/xml');
			readfile(str_replace($this->SITEMAP_WEB_PATH, $this->SITEMAP_STORE_FOLDER, $location));
			exit();
		} else {
			header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1').' 301 Moved Permanently');
			header('Location: '.$location); 
		}
	}

	/**
	* Notify google about update. $this->_file_for_google - sitemap or sitemap index file to give to google
	*/
	function _do_notify_google() {
		if ($this->NOTIFY_GOOGLE) {
			fopen('http://www.google.com/webmasters/sitemaps/ping?sitemap='.urlencode($this->_notify_url), 'r');
		}
	}

	/**
	* backwards compatible for PHP version < 5
	*/
	function _iso8601_date($timestamp) {
		return date('c', $timestamp);
	}

	/**
	* Processes sitemap file. Escapes special chars. Makes full webpath 
	*/
	function _process_sitemap_file($_path = ''){
		$text = file_get_contents($_path);
		if ($this->ALLOW_REWRITE) {
			$RW = _class('rewrite');
			$RW->FORCE_NO_DEBUG = true;
			// Save old pattern
			$old_pattern = $RW->_links_pattern;
			// Aplly our custom pattern
			$RW->_links_pattern = '/(<loc>)([^\<]+)(<\/loc>)/ms';
			foreach ((array)common()->_my_split($text, '<url>', $this->REWRITE_SPLIT_LENGTH) as $_cur_text) {
				$rewrited .= $RW->_rewrite_replace_links($_cur_text);
			}
			$text = $rewrited;
			unset($rewrited);
			// Revert old links pattern
			$RW->_links_pattern = $old_pattern;
		} else {
			$text = str_replace('<loc>./?', '<loc>'.WEB_PATH.'?', $text);
		}
		$text = preg_replace('/<loc>([^\<]+)<\/loc>/imse', "'<loc>'.htmlspecialchars('\\1').'</loc>'", $text);
		file_put_contents($_path, $text);
	}

	/**
	* Get available user modules from the project modules folder
	*/
	function _get_modules_from_files () {
		$regex = '~function(\s)+('.implode('|', $this->HOOK_NAMES).')\s*\(~ims';

		$yf_prefix_len = strlen(YF_PREFIX);
		$yf_cls_ext_len = strlen(YF_CLS_EXT);
		$site_prefix_len = strlen(YF_SITE_CLS_PREFIX);

		$pattern = USER_MODULES_DIR.'*'.YF_CLS_EXT;
		$places = array(
			'yf_main'			=> YF_PATH. $pattern,
			'yf_plugins'		=> YF_PATH. 'plugins/*/'. $pattern,
			'project_main'		=> PROJECT_PATH. $pattern,
			'project_plugins'	=> PROJECT_PATH. 'plugins/*/'. $pattern,
			'app_main'			=> APP_PATH. $pattern,
			'app_plugins'		=> APP_PATH. 'plugins/*/'. $pattern,
		);
		$modules = array();
		foreach ($places as $place_name => $glob) {
			foreach (glob($glob) as $path) {
				if (substr($path, -$yf_cls_ext_len) !== YF_CLS_EXT) {
					continue;
				}
				$name = substr(basename($path), 0, -$yf_cls_ext_len);
				if (substr($name, 0, $yf_prefix_len) === YF_PREFIX) {
					$name = substr($name, $yf_prefix_len);
				}
				if (substr($name, 0, $site_prefix_len) === YF_SITE_CLS_PREFIX) {
					$name = substr($name, $site_prefix_len);
				}
				if (!strlen($name) || !preg_match($regex, file_get_contents($path), $m)) {
					continue;
				}
				$modules[$name] = $name;
			}
		}
		ksort($modules);
		return $modules;
	}

	/**
	*/
	function _tpl_sitemap_header () {
		return '<?xml version="1.0" encoding="UTF-8"?>'
			.PHP_EOL. '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
			.PHP_EOL. ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">'
			.PHP_EOL;
	}

	/**
	*/
	function _tpl_sitemap_footer () {
		return PHP_EOL. '</urlset>'. PHP_EOL;
	}

	/**
	*/
	function _tpl_sitemap_index_header () {
		return '<?xml version="1.0" encoding="UTF-8"?>'
			.PHP_EOL. '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
			.PHP_EOL. ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd">'
			.PHP_EOL;
	}

	/**
	*/
	function _tpl_sitemap_index_footer () {
		return PHP_EOL. '</sitemapindex>'. PHP_EOL;
	}
}
