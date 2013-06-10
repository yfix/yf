<?php

/**
* Search Engines based keywords handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_se_keywords {

	/** @var int */
	public $KEYWORDS_DISPLAY_LIMIT = 40;
	/** @var bool */
	public $USE_FULLTEXT_SEARCH	= false;
	/** @var bool */
	public $CACHE_ENABLED			= false;
	/** @var int */
	public $CACHE_TTL				= 1800;
	/** @var string */
	public $CACHE_DIR				= "uploads/se_keywords_cache/";
	/** @var bool */
	public $USE_MEMCACHED			= false;
	/** @var int Multiplier for data from db (must be INT >= 1) */
	public $DATA_FROM_DB_MULTIPLY	= 5;
	/** @var int Minimal number of hits to use */
	public $MIN_HITS_DISPLAY_LIMIT	= 5;
	/** @var int Max number of keywords to allow (to decrease database load), set to "0" to disable */
	public $MAX_TOTAL_KEYWORDS		= 0/*20000*/;
	/** @var int Update most popular every calls when "rand(1, $num) == 1" */
	public $UPDATE_POPULAR_RAND	= 2;
	/** @var string @conf_skip */
	public $_auto_header			= "<?php\r\n";
	/** @var string @conf_skip */
	public $_auto_footer			= "\r\n?>";
	/** @var string @conf_skip */
	public $_file_prefix			= "cache_";
	/** @var string @conf_skip */
	public $_file_ext				= ".php";

	/**
	* Module constructor
	*
	* @access	public
	* @return	void
	*/
	function _init () {
		// Get array of known search engines
		$this->_search_engines = main()->get_data("search_engines");
		// Current cache depends on core cache
		if (!main()->USE_SYSTEM_CACHE) {
			$this->CACHE_ENABLED = false;
		}
		// Set full path to the cache dir
		if ($this->CACHE_ENABLED) {
			if ($this->USE_MEMCACHED) {
// TODO
			// Common (files-based) cache code
			} else {
				$this->CACHE_DIR = INCLUDE_PATH.$this->CACHE_DIR;
				if (!file_exists($this->CACHE_DIR)) {
					_mkdir_m($this->CACHE_DIR, 0777);
				}
			}
		}
	}

	/**
	* Show search keywords box
	*
	* @access	public
	* @return	string	Contents of the keywords block template
	*/
	function _show_search_keywords ($input = "") {
		// Get input params		
		$display_limit			= intval(!empty($input["display_limit"]) && $input["display_limit"] < $this->KEYWORDS_DISPLAY_LIMIT ? $input["display_limit"] : $this->KEYWORDS_DISPLAY_LIMIT);
		$search_words			= $input["search_words"];
		$type					= in_array($input["display_type"], array("most_popular","random")) ? $input["display_type"] : "most_popular";
		$only_from_current_site	= intval($input["only_from_current_site"]);
		$fill_with_popular		= $input["fill_with_popular"];
		// Most popular keywords
		$this->_most_popular = main()->get_data("search_keywords_most_popular");
		// Stop if no popular keywords found
		if (empty($this->_most_popular)) {
			return false;
		}
		// Process keywords by given params
		if (empty($search_words) && $type == "most_popular") {
			$array_from_db = $this->_most_popular;
		} else {
			if (!empty($search_words)) {
				$search_words_array = explode(",", $search_words);
			}
			$array_from_db = false; // No not change this, depends on cache !!
			// Try to get data from cache
			if (!empty($search_words_array) && $this->CACHE_ENABLED) {
				$se_cache_name = md5($search_words);
				$array_from_db = $this->_cache_get($se_cache_name);
			}
			// Prepare and get data from db
			if (!empty($search_words_array) && empty($array_from_db) && !is_array($array_from_db)) {
				// use full text searching
				if ($this->USE_FULLTEXT_SEARCH) {
					$search_words_sql = " AND MATCH(`text`) AGAINST ('"._es(implode(" ", $search_words_array))."' IN BOOLEAN MODE) ";
				} else {
					foreach ((array)$search_words_array as $k => $v) {
						$v = trim($v);
						if (empty($v)) {
							continue;
						}
						$tmp_array[$k] = " `text` LIKE '%"._es($v)."%' ";
					}
					if (!empty($tmp_array)) {
						$search_words_sql = " AND (".implode(" OR ", $tmp_array).") ";
					}
				}
				$Q = db()->query(
					"SELECT `site_url`,`text`,`hits` 
					FROM `".db('search_keywords')."` 
					WHERE 1 ".(!empty($search_words_sql) ? $search_words_sql : "")." 
					".($this->MIN_HITS_DISPLAY_LIMIT ? "HAVING `hits` > ".$this->MIN_HITS_DISPLAY_LIMIT : "")."
					ORDER BY ".(/*$type == "most_popular" ? */"`hits` DESC"/* : "RAND()"*/)." 
					LIMIT ".intval($display_limit * $this->DATA_FROM_DB_MULTIPLY)
				);
				while ($A = db()->fetch_assoc($Q)) $array_from_db[$A["text"]] = $A["site_url"];
				// Put cache file
				if ($this->CACHE_ENABLED) {
					$this->_cache_put($se_cache_name, $array_from_db);
				}
			}
		}
		foreach ((array)$array_from_db as $text => $site_url) {
			if ($only_from_current_site && (false === strpos($site_url, SITE_ADVERT_URL))) {
				continue;
			}
			if (!strlen($text)) {
				continue;
			}
			// Another check for display number
			if (++$c > $display_limit && $type == "most_popular") {
				break;
			}
			// Process items
			$text = str_replace("+", " ", str_replace(array("\"","'"), "", $text));
			$keywords[$text] = array(
				"site_url"	=> _prepare_html($site_url),
				"text"		=> _prepare_html($text),
			);
		}
		// Emulate ORDER BY RAND()
		if ($type != "most_popular") {
			$_tmp_keys = array_keys((array)$keywords);
			if (!empty($_tmp_keys)) {
				shuffle($_tmp_keys);
			}
			foreach ((array)$_tmp_keys as $_cur_keyword) {
				if (++$d > $display_limit) {
					break;
				}
				$_tmp_keywords[$_cur_keyword] = $keywords[$_cur_keyword];
			}
			if (!empty($_tmp_keywords)) {
				$keywords = $_tmp_keywords;
				unset($_tmp_keywords);
				unset($_tmp_keys);
			}
		}
		// Check if we are using search words mode but found too less records
		$need_to_fill = (int)$display_limit - count($keywords);
		// Check if we need to fill 
		if (!empty($search_words) && $fill_with_popular && $need_to_fill) {
			// Prepare most popular keywords
			$prepared_popular = array();
			foreach ((array)$this->_most_popular as $text => $site_url) {
				if ($only_from_current_site && (false === strpos($site_url, SITE_ADVERT_URL))) {
					continue;
				}
				if (!strlen($text)) {
					continue;
				}
				$prepared_popular[$text] = $site_url;
			}
			// Process fill
			for ($i = 1; $i <= $need_to_fill; $i++) {
				$rand_key	= array_rand($prepared_popular);
				$text		= $rand_key;
				$site_url	= $prepared_popular[$text];
				// Process items
				$text = str_replace("+", " ", str_replace(array("\"","'"), "", $text));
				$keywords[$text] = array(
					"site_url"	=> _prepare_html($site_url),
					"text"		=> _prepare_html($text),
				);
			}
		}
		if (isset($keywords[""])) {
			unset($keywords[""]);
		}
		// Stop here if nothing to display
		if (empty($keywords)) {
			return false;
		}
		// Process template
		$replace = array(
			"keywords" => $keywords,
		);
		return tpl()->parse(__CLASS__."/main", $replace);
	}

	/**
	* Set dynamic search keywords from the referer field
	*
	* @access	public
	* @return	void
	*/
	function _set_search_keywords ($referer = "") {
	
		if (empty($referer)) {
			$referer = $_SERVER["HTTP_REFERER"];
			$batch_mode = true;
		}
		// Return error message if no referer
		if (!strlen($referer)) {
			return false;
		}
		
		// process parsing referer field
		$parsed_url	= @parse_url($referer);
		$host		= $parsed_url["host"];
		$query		= $parsed_url["query"]."\r\n";
		
		// Check if search engine is known by system
		if (!isset($this->_search_engines[$host])) {
			return false;
		}
		$cur_se			= $this->_search_engines[$host];
		// Process keywords
		$parsed_url["query"] = str_replace(array("./?","./"), "", $parsed_url["query"]);
		parse_str($parsed_url["query"], $parsed_query);
		$q_s_charset	= $parsed_query[$cur_se["q_s_charset"]];
		$q_s_word		= $parsed_query[$cur_se["q_s_word"]];
		$charset		= isset($q_s_charset) ? $q_s_charset : $cur_se["def_charset"];
		$search_query	= isset($q_s_word) ? $q_s_word : $parsed_query[$cur_se["q_s_word2"]];
		$search_query	= trim(stripslashes(strlen($charset) ? iconv($charset, conf('charset'), $search_query) : $search_query));
		// Check for search query
		if (empty($search_query)) {
			return false;
		}
		
		// Set referral cookie
		$COOKIE_VAR_NAME = "ref_code";
		if (!$batch_mode && empty($_COOKIE[$COOKIE_VAR_NAME])) {
			$code = $cur_se["name"];
			setcookie($COOKIE_VAR_NAME, $code, time() + 86400 * 30, "/");
		}
		// Limit number of keywords
		if (!empty($this->MAX_TOTAL_KEYWORDS)) {
			list($current_keywords_num) = db()->query_fetch("SELECT COUNT(*) AS `0` FROM `".db('search_keywords')."`");
		}
		if (!empty($this->MAX_TOTAL_KEYWORDS) && $current_keywords_num >= $this->MAX_TOTAL_KEYWORDS) {
			db()->_add_shutdown_query(
				"UPDATE `".db('search_keywords')."` 
				SET `hits` = `hits` + 1, 
					`last_update` = ".time()."
				WHERE `text`='"._es($search_query)."'"
			);
		} else {
			// Check if such record already exists
			$sql = "INSERT INTO `".db('search_keywords')."` (
					`engine`,
					`text`,
					`ref_url`,
					`site_url`,
					`last_update`,
					`hits`
				) VALUES (
					".intval($this->_search_engines[$host]["id"]).",
					'"._es($search_query)."',
					'"._es($referer)."',
					'"._es("http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"])."',
					".time().",
					`hits` + 1 
				) ON DUPLICATE KEY UPDATE `hits` = `hits` + 1\r\n";
			db()->_add_shutdown_query($sql);
		}
		// Check if we need to update most popular right now
		if ($this->UPDATE_POPULAR_RAND > 1) {
			if (rand(1, $this->UPDATE_POPULAR_RAND) != 1) {
				return true;
			}
		}
		// Update system cache
		if (!$batch_mode && main()->USE_SYSTEM_CACHE) {
			main()->_add_shutdown_code('cache()->refresh("search_keywords_most_popular");');
		}
	}

	/**
	* Get data from cache
	*/
	function _cache_get ($cache_name = "") {
		if (empty($cache_name)) {
			return false;
		}
		$cache_file_path = $this->_prepare_cache_path($cache_name);
		return $this->_get_cache_file($cache_file_path);
	}

	/**
	* Put data into cache
	*/
	function _cache_put ($cache_name = "", $data = null) {
		if (is_null($data)) {
			$data = array();
		}
		$cache_file_path = $this->_prepare_cache_path($cache_name);
		$this->_put_cache_file($data, $cache_file_path, $data);
	}

	/**
	* Update selected cache entry
	*/
	function _cache_refresh ($name = "") {
		$this->_cache_put($name);
	}

	/**
	* Do get cache file contents
	*/
	function _get_cache_file ($cache_file = "") {
		if (empty($cache_file)) {
			return null;
		}
		if ($this->USE_MEMCACHED) {
//			$cache_key = basename($cache_file);
//			$data = $this->MC_OBJ->get($cache_key);
// TODO
		// Common (files-based) cache code
		} else {
			clearstatcache();
			if (!file_exists($cache_file)) {
				return null;
			}
			// Delete expired cache files
			$last_modified = filemtime($cache_file);
			$TTL = $this->CACHE_TTL;
			if ($last_modified < (time() - $TTL)) {
				unlink($cache_file);
				return null;
			}
			// Get data from file
			$data = array();
			if (DEBUG_MODE) {
				$_time_start = microtime(true);
			}
			// Try to include file
			include ($cache_file);
/*			@eval("?> ".file_get_contents($cache_file)." <?php"); */
			if (DEBUG_MODE) {
				$GLOBALS['include_files_exec_time'][strtolower(str_replace(DIRECTORY_SEPARATOR, "/", $cache_file))] = (microtime(true) - $_time_start);
			}
		}
		return $data;
	}

	/**
	* Do put cache file contetns
	*/
	function _put_cache_file ($data = array(), $cache_file = "") {
		if (empty($cache_file)) {
			return false;
		}
		if ($this->USE_MEMCACHED) {
// TODO
//			$cache_key = basename($cache_file);
//			$this->MC_OBJ->put($cache_key, $data, $this->CACHE_TTL);
		// Common (files-based) cache code
		} else {
			foreach ((array)$data as $k => $v) {
				$file_text .= "\t'".$this->_put_safe_slashes($k)."' => ";
				$file_text .= is_array($v) ? $this->_create_array_code($v) : "'".$this->_put_safe_slashes($v)."',";
				$file_text .= "\r\n";
			}
			file_put_contents($cache_file, $this->_auto_header. "\$data = array(\r\n".$file_text.");". $this->_auto_footer);
		}
	}

	/**
	* Create array code recursive
	*/
	function _create_array_code ($data = array()) {
		$code = "array(";
		foreach ((array)$data as $k => $v) {
			$code .= "'".$this->_put_safe_slashes($k)."'=>";
			$code .= is_array($v) ? $this->_create_array_code($v) : "'". $this->_put_safe_slashes($v). "',";
		}
		$code .= "),";
		return $code;
	}

	/**
	* Prepare text to store it in cache
	*/
	function _put_safe_slashes ($text = "") {
		$text = str_replace("'", "&#039;", trim($text));
		$text = str_replace("\\&#039;", "\\'", $text);
		$text = str_replace("&#039;", "\\'", $text);
		if (substr($text, -1) == "\\" && substr($text, -2, 1) != "\\") {
			$text .= "\\";
		}
		return $text;
	}

	/**
	* Clears all cache files inside cache folder
	* 
	* @access	public
	* @return	void
	*/
	function _clear_cache_files () {
		// Memcached code
		if ($this->USE_MEMCACHED) {
// TODO
		// Common (files-based) cache code
		} else {
			$dh = @opendir($this->CACHE_DIR);
			if (!$dh) {
				return false;
			}
			while (($f = readdir($dh)) !== false) {
				if ($f == "." || $f == ".." || !is_file($this->CACHE_DIR.$f)) {
					continue;
				}
				if (common()->get_file_ext($f) != "php") {
					continue;
				}
				if (substr($f, 0, strlen($this->_file_prefix)) != $this->_file_prefix) {
					continue;
				}
				// Do delete cache file
				if (file_exists($this->CACHE_DIR.$f)) {
					unlink($this->CACHE_DIR.$f);
				}
			}
			@closedir($dh);
		}
	}

	/**
	* Prepare path for the cache file (current page)
	*/
	function _prepare_cache_path($cur_cache_name = "") { 
		// Get name of the cache file
		if (empty($cur_cache_name)) {
			return false;
		}
		// Memcached code
		if ($this->USE_MEMCACHED) {
			return $cur_cache_name;
		}
		// Base cache dir
		$cache_dir = $this->CACHE_DIR;
		// Create subdir (a/b/c)
		$cache_sub_dir = $cur_cache_name[0]."/".$cur_cache_name[1]."/".$cur_cache_name[2]."/";
		$cache_dir .= $cache_sub_dir;
		_mkdir_m($cache_dir, 0777);
		return $cache_dir. $this->_file_prefix. $cur_cache_name. $this->_file_ext;
	}
}
