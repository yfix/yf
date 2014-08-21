<?php

/**
* File uploads wrapper
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_remote_files {

	/** @var string SMTP host to verify emails from. Be sure to set this correctly! */
	public $SMTP_PROBE_HOST			= 'mx.test.com';
	/** @var string */
	public $SMTP_PROBE_ADDRESS		= 'admin@test.com';
	/** @var string @conf_skip */
	public $DEF_USER_AGENT			= 'Mozilla/5.0 Firefox YF';
	/** @var bool @conf_skip */
	public $REMOTE_ALLOW_CACHE		= true;
	/** @var string @conf_skip */
	public $REMOTE_CACHE_DIR		= 'remote_cache/';
	/** @var int @conf_skip In seconds */
	public $CURL_DEF_CONNECT_TIMEOUT= 15;
	/** @var int @conf_skip In seconds */
	public $CURL_DEF_TIMEOUT		= 30;
	/** @var int @conf_skip */
	public $CURL_DEF_MAX_REDIRECTS	= 30;
	/** @var int @conf_skip */
	public $CURL_DEF_MAX_THREADS	= 20;
	/** @var int @conf_skip */
	public $CURL_DEF_INTERFACE		= '';
	/** @var int @conf_skip */
	public $CURL_DEF_HEADER			= '';
	/** @var bool */
	public $DEBUG					= false;
	/** @var bool */
	public $_is_avail_setopt_array	= false;

	/**
	*/
	function __construct() {
		$this->_is_avail_setopt_array = function_exists('curl_setopt_array');
	}

	/**
	* Framework constructor
	*/
	function _init() {
		if ($GLOBALS['USE_CURL_DEBUG'] || conf('USE_CURL_DEBUG')) {
			$this->DEBUG = true;
		}
	}

	/**
	* Correctly escaping spaces symbols inside url, while not touching other symbols that will be encoded by urlencode (not needed here)
	*/
	function _fix_url($url = '') {
		return str_replace(array(' ',"\t","\r","\n"), array('%20','%20','',''), trim($url));
	}

	/**
	* Do upload file to server
	*/
	function do_upload ($tmp_file_path = '', $new_path = '', $new_file_name = '') {
		// First we need to move file to our temporary folder that is shown throgh web
		$new_tmp_file_name = abs(crc32(microtime(true))).'.upload.tmp';
		$new_tmp_file_path = INCLUDE_PATH. SITE_UPLOADS_DIR. $new_tmp_file_name;
		move_uploaded_file($tmp_file_path, $new_tmp_file_path);
		// Prepare CURL
		$url_to_post = REMOTE_STORAGE_URL.'?action=upload';
		$array_to_post[] = 'file_name='.urlencode(WEB_PATH. str_replace(array(INCLUDE_PATH, REAL_PATH, WEB_PATH), '', $new_tmp_file_path));
		$array_to_post[] = 'new_path='.urlencode($new_path);
		$array_to_post[] = 'new_file_name='.urlencode($new_file_name);
		if ($ch = curl_init()) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $array_to_post));
			curl_setopt($ch, CURLOPT_URL, $url_to_post);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec ($ch);
			curl_close ($ch);
		}
		// Do cleanup temporary file
		if (file_exists($new_tmp_file_path)) {
			unlink($new_tmp_file_path);
		}
		return true;
	}

	/**
	* Delete file from server
	*/
	function do_delete ($path_to_file = '') {
		if ($this->file_is_exists($path_to_file)) {
			@unlink($path_to_file);
		}
	}

	/**
	* Check if file exists on the server
	*/
	function file_is_exists ($path_to_file = '') {
		// Check if file is remote
		$uri	= @parse_url($path_to_file);
		return (int)(bool) (in_array($uri['scheme'], array('http', 'https', 'ftp')) && !empty($uri['host']) ? $this->filemtime_remote($path_to_file) : file_exists($path_to_file));
	}

	/**
	* Get last modification time from remote file
	*/
	function filemtime_remote($uri) {
		$CONNECTION_TIMEOUT = 1; // In seconds
		$uri	= @parse_url($uri);
		$h		= @fsockopen($uri['host'], $uri['port'] ? $uri['port'] : 80, $errno, $errstr, $CONNECTION_TIMEOUT);
		if (!$h) return 0;
		$result = 0;
		fputs($h, 'HEAD '.$this->_fix_url($uri['path'])." HTTP/1.1\r\nHost: ".$uri['host']."\r\n\r\n");
		while (!feof($h)) {
			$line = fgets($h, 1024);
			if (!trim($line)) break;
			$col = strpos($line, ':');
			if ($col !== false) {
				$header	= trim(substr($line, 0, $col));
				$value	= trim(substr($line, $col + 1));
				if (strtolower($header) == 'last-modified') {
					$result = strtotime($value);
					break;
				}
			}
		}
		fclose($h);
		return $result;
	}

	/**
	* Get remote file size
	*/
	function remote_file_size($url = '') {
		$url = $this->_fix_url($url);
		$tmp = @parse_url($url);
		$sch = $tmp['scheme'];
		if (($sch != 'http') && ($sch != 'https') && ($sch != 'ftp') && ($sch != 'ftps')) {
			return false;
		}
		if (($sch == 'http') || ($sch == 'https')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, true);
			// not necessary unless the file redirects (like the PHP example we're using here)
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$data = curl_exec($ch);
			curl_close($ch);
			if ($data === false) {
				return 0;
			}
			$content_length = 0;
			if (preg_match('/Content-Length: (\d+)/i', $data, $matches)) {
				$content_length = (int)$matches[1];
			}
			return (int)$content_length;
		}
		if (($sch == 'ftp') || ($sch == 'ftps')) {
			$server = @parse_url($url, PHP_URL_HOST);
			$port = @parse_url($url, PHP_URL_PORT);
			$path = @parse_url($url, PHP_URL_PATH);
			$user = @parse_url($url, PHP_URL_USER);
			$pass = @parse_url($url, PHP_URL_PASS);
			if ((!$server) || (!$path)) {
				return false;
			}
			if (!$port) {
				$port = 21;
			}
			if (!$user) {
				$user = 'anonymous';
			}
			if (!$pass) {
				$pass = 'phpos@';
			}
			switch ($sch) {
				case 'ftp':
					$ftpid = ftp_connect($server, $port);
					break;
				case 'ftps':
					$ftpid = ftp_ssl_connect($server, $port);
					break;
			}
			if (!$ftpid) {
				return false;
			}
			$login = ftp_login($ftpid, $user, $pass);
			if (!$login) {
				return false;
			}
			$ftpsize = ftp_size($ftpid, $path);
			ftp_close($ftpid);
			if ($ftpsize == -1) {
				return false;
			}
			return $ftpsize;
		}
	}

	/**
	* Get remote file using CURL extension (allow to cache response into local file)
	* 
	* @param	string	$url		Url to fetch
	* @param	int		$cache_ttl	Timeout for cache entry
	* @param	array	$url_options	Array of request options
	* @return	string
	*/
	function get_remote_page($url = '', $cache_ttl = -1, $url_options = array(), &$requests_info = array()) {
		if (empty($url)) {
			return false;
		}
		if (!$cache_ttl) {
			$cache_ttl = -1;
		}
		if (!is_array($url_options)) {
			$url_options = array();
		}
		$id = $url;
		$result = '';
		// Try to get from cache
		if ($this->REMOTE_ALLOW_CACHE && $cache_ttl != -1) {
			$cache_dir	= STORAGE_PATH. $this->REMOTE_CACHE_DIR;
			$cache_name	= md5($url);
			$cache_path	= $cache_dir.$cache_name[0].'/'.$cache_name[1].'/'.$cache_name;
			if (file_exists($cache_path)) {
				if ($cache_ttl && filemtime($cache_path) < (time() - $cache_ttl)) {
					unlink($cache_path);
				} else {
					return file_get_contents($cache_path);
				}
			}
		}
		$url = $this->_fix_url($url);

		$p = @parse_url($url);
		if (!array_key_exists('scheme', $p) || !array_key_exists('host', $p) || !in_array($p['scheme'], array('http', 'https', 'ftp'))) {
			return false;
		}
		if (!isset($GLOBALS['_curl_requests_info'])) {
			$GLOBALS['_curl_requests_info'] = array();
		}
		if (!$ch = curl_init()) {
			return false;
		}
		$file_handles	= array();

		$is_ftp_url = ($p['scheme'] == 'ftp');

		curl_setopt($ch, CURLOPT_URL,	$url);
		$curl_opts = $this->_set_curl_options($url_options, $is_ftp_url);
		if ($this->_is_avail_setopt_array) {
			curl_setopt_array($ch, $curl_opts);
		} else {
			foreach ((array)$curl_opts as $k => $v) {
				curl_setopt($ch, $k, $v);
			}
		}
		if ($url_options['curl_verbose'] || $this->DEBUG) {
			$verbose_stream = fopen('php://temp', 'rw+');
			curl_setopt($ch, CURLOPT_STDERR, $verbose_stream);
		}
		// Download file efficiently. Do not move up! 
		// Should be set after CURLOPT_RETURNTRANSFER !
		if ($url_options['save_path']) {
			$save_dir = dirname($url_options['save_path']);
			if (!file_exists($save_dir)) {
				mkdir($save_dir, 0777, true);
			}
			$file_handles[$url] = fopen($url_options['save_path'], 'w');
			curl_setopt($ch, CURLOPT_FILE,	$file_handles[$url]);
		}
		$result = curl_exec($ch);
		// Get lot of details about connections done
		$info = curl_getinfo($ch);
		if ($url_options['curl_verbose'] || $this->DEBUG) {
			$response_header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$response_header = substr($result, 0, $response_header_size);
			$result = substr($result, $response_header_size);
			$info['CURL_RESPONSE_HEADER'] = $response_header;
			if (strlen($result) < 100000) {
				$info['CURL_RESPONSE_BODY'] = $result;
			}
			rewind($verbose_stream);
			$info['CURL_STDERR'] = stream_get_contents($verbose_stream);
			if (main()->CONSOLE_MODE) {
				echo $info['CURL_STDERR'];
			}
			$info['CURL_OPTS'] = $this->pretty_dump_curl_opts($curl_opts);
			$info['CURL_REQUEST_DATE'] = date('Y-m-d H:i:s');
		} else {
			if (strlen($result) < 100000) {
				$info['CURL_RESPONSE_BODY'] = $result;
			}
		}
		$info['CURL_ERRNO']	= curl_errno($ch);
		$info['CURL_ERROR']	= curl_error($ch);
		$requests_info = $info;
		$GLOBALS['_curl_requests_info'][$id] = $info;
		if (DEBUG_MODE && !main()->CONSOLE_MODE) {
			debug('curl_get_remote_page[]', array('info' => $info, 'trace' => main()->trace_string()));
		}

		curl_close ($ch);
		// Close file handles after curl_close to receive good file
		if ($url_options['save_path'] && $file_handles[$url]) {
			@fclose($file_handles[$url]);
		}

		// Put into cache
		if ($this->REMOTE_ALLOW_CACHE && $cache_ttl != -1 && strlen($result)) {
			if (!file_exists(dirname($cache_path))) {
				mkdir(dirname($cache_path), 0777, true);
			}
			file_put_contents($cache_path, $result);
		}
		return $result;
	}

	/**
	* For internal use by _multi_request
	*/
	function _curl_use_http_queue_item($id, $details) {
		$url = $details['url'];
		$url_options = $details['options'];

		$this->_curl_threads[$id] = curl_init();
		if (!$this->_curl_threads[$id]) {
			continue;
		}
		// Map of curl handles into url ids
		$this->_curl_ids[$this->_curl_threads[$id]] = $id;

		curl_setopt($this->_curl_threads[$id], CURLOPT_URL, $url);
		// Apply array of curl options (useful for debugging, see $this->pretty_dump_curl_opts() )
		$curl_opts = $this->_set_curl_options($url_options, false);
		if ($this->_is_avail_setopt_array) {
			curl_setopt_array($this->_curl_threads[$id], $curl_opts);
		} else {
			foreach ((array)$curl_opts as $k => $v) {
				curl_setopt($this->_curl_threads[$id], $k, $v);
			}
		}
		// Download file efficiently. Do not move up! 
		// Should be set after CURLOPT_RETURNTRANSFER !
		if ($url_options['save_path']) {
			$save_dir = dirname($url_options['save_path']);
			if (!file_exists($save_dir)) {
				mkdir($save_dir, 0777, true);
			}
			$this->_file_handles[$url] = fopen($url_options['save_path'], 'w');
			curl_setopt($this->_curl_threads[$id], CURLOPT_FILE, $this->_file_handles[$url]);
		}
		curl_multi_add_handle($this->_mh, $this->_curl_threads[$id]);
	}

	/**
	* Get several pages in separate threads using 'curl_multi_init'
	* $data and $options could be 1 and 2-dimensional arrays
	* When 2-dimensional - then data post params could be used and 
	* options could be set individually for each url
	* 
	* @param	array	$urls		Contains list of urls to fetch
	* @param	array	$options	Array of request options
	* @return	array				Result array of fetched urls
	*/
	function _multi_request($urls, $options = array(), $max_threads = 0, &$requests_info = array()) {
		if (!$max_threads) {
			$max_threads = $this->CURL_DEF_MAX_THREADS;
		}
		if (!is_array($urls) && is_string($urls)) {
			$urls = array($urls);
		}

		$result					= array();
		$ftp_calls				= array();
		$all_url_options		= array();
		$this->_curl_threads	= array();
		$this->_file_handles	= array();
		$this->_curl_ids 		= array();

		$this->_mh = curl_multi_init();

		// loop through $urls and create curl handles
		// then add them to the multi-handle
		foreach ((array)$urls as $id => $url_data) {

			// Check if there are options specific for the current url
			// This will completely override options for selected url
			if (isset($options[$id]) && is_array($options[$id])) {
				$url_options = $options[$id];
			} else {
				$url_options = $options;
				// Merge common options with url specific ones
				// Useful when lot of similar opts should be set in $options
				// But for example, several of them needed to be changed for every url
				// like 'save_path', 'url'
				if (is_array($url_data)) {
					foreach ((array)$url_data as $k => $v) {
						$url_options[$k] = $v;
					}
				}
			}
			$url = '';
			if (is_array($url_data)) {
				$url = $url_data['url'] ? $url_data['url'] : $id;
				if (isset($url_data['post'])) {
					$url_options['post'] = $url_data['post'];
				}
			} else {
				$url = $url_data;
			}
			// Required fix for urls with spaces symbols inside
			$url = $this->_fix_url($url);
			// Check url parts for correctness
			$p = @parse_url($url);
			if (!array_key_exists('scheme', $p) || !array_key_exists('host', $p) || !in_array($p['scheme'], array('http', 'https', 'ftp'))) {
				$result[$id] = false;
				continue;
			}
			// Because of php bug http://bugs.php.net/bug.php?id=52284 we need to do ftp requests one-by-one
			$is_ftp_url = ($p['scheme'] == 'ftp');
			if ($is_ftp_url) {
				$ftp_calls[$id] = $url_data;
			} else {
				$http_queue[$id] = array(
					'url'		=> $url,
					'options'	=> $url_options,
				);
				$all_url_options[$id] = $url_options;
			}
		}
		// Fill initial set of http urls (this step needed to make working max_threads)
		foreach ((array)$http_queue as $id => $details) {
			if ($i++ >= $max_threads) {
				break;
			}
			$this->_curl_use_http_queue_item($id, $details);
			// Remove queue item to not process url again
			unset($http_queue[$id]);
		}

		$GLOBALS['_curl_requests_info'] = array();

		// execute the handles in the efficient way
		$running = null;
		do {
			while (($execrun = curl_multi_exec($this->_mh, $running)) == CURLM_CALL_MULTI_PERFORM);
			if ($execrun != CURLM_OK) {
				break;
			}
			// a request was just completed -- find out which one
			while ($done = curl_multi_info_read($this->_mh)) {
				$c = $done['handle'];
				$id = $this->_curl_ids[$c];
				$url_options = $all_url_options[$id];
				// get the info and content returned on the request
				$result[$id] = curl_multi_getcontent($c);
				if ($url_options['get_redirected_url']) {
					$result[$id] = curl_getinfo($c, CURLINFO_EFFECTIVE_URL);
				}
				$info = curl_getinfo($c);
				if ($url_options['curl_verbose'] || $this->DEBUG) {
					$response_header_size = curl_getinfo($c, CURLINFO_HEADER_SIZE);
					$response_header = substr($result[$id], 0, $response_header_size);
					$result[$id] = substr($result[$id], $response_header_size);
					$info['CURL_RESPONSE_HEADER'] = $response_header;
					if (strlen($result[$id]) < 100000) {
						$info['CURL_RESPONSE_BODY'] = $result[$id];
					}
#					$info['CURL_OPTS'] = $this->pretty_dump_curl_opts($curl_opts);
					$info['CURL_REQUEST_DATE'] = date('Y-m-d H:i:s');
				} else {
					if (strlen($result[$id]) < 100000) {
						$info['CURL_RESPONSE_BODY'] = $result[$id];
					}
				}
				$info['CURL_ERRNO']	= curl_errno($c);
				$info['CURL_ERROR']	= curl_error($c);
				if (DEBUG_MODE && !main()->CONSOLE_MODE) {
					debug('curl_get_remote_page[]', array('info' => $info, 'trace' => main()->trace_string()));
				}
				$requests_info = $info;
				$GLOBALS['_curl_requests_info'][$id] = $info;
				// send the return values to the callback function.
				$callback = $url_options['callback'];
				if (is_callable($callback)) {
					call_user_func($callback, $result[$id], $info, $id, $url_options);
				}
				unset($http_queue[$id]);
				// Get next http url from queue to process (related to max_threads)
				if (count($http_queue)) {
					list($new_id, $new_details) = each($http_queue);
					$this->_curl_use_http_queue_item($new_id, $new_details);
					// Remove queue item to not process url again
					unset($http_queue[$new_id]);
				}
				// remove the curl handle that just completed
				curl_multi_remove_handle($this->_mh, $done['handle']);
			}
			// Block for data in / output; error handling is done by curl_multi_exec
			if ($running) {
				curl_multi_select($this->_mh, 2); // 2 = timeout to wait for any curl multi thread activity (in seconds)
			}
		} while ($running);

		// Close all opened file handles after curl_close
		foreach ((array)$this->_file_handles as $fh => $f_tmp) {
			@fclose($this->_file_handles[$fh]);
		}
		// all done
		curl_multi_close($this->_mh);

		// Ftp single-threade fallback (because of php bug)
		foreach ((array)$ftp_calls as $id => $url_data) {
			$url_options = array();
			if (isset($options[$id]) && is_array($options[$id])) {
				$url_options = $options[$id];
			} else {
				$url_options = $options;
				foreach ((array)$url_data as $k => $v) {
					$url_options[$k] = $v;
				}
			}
			$url = '';
			if (is_array($url_data)) {
				$url = $url_data['url'] ? $url_data['url'] : $id;
			} else {
				$url = $url_data;
			}
			// Add delay between requests
			if (isset($options['ftp_delay']) && !empty($options['ftp_delay'])) {
				sleep($options['ftp_delay']);
			}
			$result[$id] = $this->get_remote_page($url, -1, $url_options);

			$GLOBALS['_curl_requests_info'][$id] = $GLOBALS['_curl_requests_info'][$url];
			$info = $GLOBALS['_curl_requests_info'][$id];
			// send the return values to the callback function.
			$callback = $url_options['callback'];
			if (is_callable($callback)) {
				call_user_func($callback, $result[$id], $info, $id, $url_options);
			}
		}
		return $result;
	}

	/**
	* Alias for the _multi_request()
	*/
	function multi_request($urls, $options = array(), $max_threads = 0, &$requests_info = array()) {
		return $this->_multi_request($urls, $options, $max_threads, $requests_info);
	}

	/**	
	* Useful for debugging array of alredy set options
	*/
	function pretty_dump_curl_opts($curl_opts = array()) {
		if (!isset($this->_curlopt_consts)) {
			$all_consts = get_defined_constants(true);
			foreach ((array)$all_consts['curl'] as $name => $id) {
				if (substr($name, 0, 8) == 'CURLOPT_') {
					$this->_curlopt_consts[$id] = $name;
				} elseif (substr($name, 0, 9) == 'CURLINFO_') {
					$this->_curlinfo_consts[$id] = $name;
				}
			}
		}
		$dump = array();
		foreach ((array)$curl_opts as $k => $v) {
			if (isset($this->_curlopt_consts[$k])) {
				$dump[$this->_curlopt_consts[$k]] = $v;
			} elseif (isset($this->_curlinfo_consts[$k])) {
				$dump[$this->_curlinfo_consts[$k]] = $v;
			}
		}
		return $dump;
	}

	/**
	* Unified set CURL options for multi_request and get_remote_page
	*/
	function _set_curl_options($url_options = array(), $is_ftp_url = false) {
		$curl_opts = array();

		$user_agent			= isset($url_options['user_agent']) ? $url_options['user_agent'] : $this->DEF_USER_AGENT;
		$curlopt_interface	= isset($url_options['interface']) ? $url_options['interface'] : $this->CURL_DEF_INTERFACE;
		$custom_header		= isset($url_options['custom_header']) ? $url_options['custom_header'] : $this->CURL_DEF_HEADER;
		$referer			= isset($url_options['referer']) ? $url_options['referer'] : $url;

		$curl_opts[CURLOPT_RETURNTRANSFER]	= 1;
		$curl_opts[CURLOPT_FOLLOWLOCATION]	= 1; // follow redirects
		$curl_opts[CURLOPT_MAXREDIRS]		= $url_options['max_redirects'] ? $url_options['max_redirects'] : $this->CURL_DEF_MAX_REDIRECTS;
		$curl_opts[CURLOPT_FILETIME]		= 1;
		$curl_opts[CURLOPT_FRESH_CONNECT]	= 0;
		$curl_opts[CURLOPT_DNS_CACHE_TIMEOUT]= 3600;
		$curl_opts[CURLOPT_CONNECTTIMEOUT]	= $url_options['connect_timeout'] ? $url_options['connect_timeout'] : $this->CURL_DEF_CONNECT_TIMEOUT;
		$curl_opts[CURLOPT_TIMEOUT]			= $url_options['timeout'] ? $url_options['timeout'] : $this->CURL_DEF_TIMEOUT; // timeout on response 
		if ($user_agent) {
			$curl_opts[CURLOPT_USERAGENT]	= $user_agent;
		}
		if ($referer) {
			$curl_opts[CURLOPT_REFERER]		= $referer;
		}
		// Custom HTTP header string to add into request
		if ($custom_header) {
			// CURLOPT_HTTPHEADER An array of HTTP header fields to set, in the format array('Content-type: text/plain', 'Content-length: 100') 
			$curl_opts[CURLOPT_HTTPHEADER]	= !is_array($custom_header) ? array($custom_header) : $custom_header;
		}
		// We not need to check SSL here
		$curl_opts[CURLOPT_SSL_VERIFYPEER] = 0;
		$curl_opts[CURLOPT_SSL_VERIFYHOST] = 0;
		// HEAD request
		if ($url_options['method_head']) {
			$curl_opts[CURLOPT_HEADER]		= 1;
			$curl_opts[CURLOPT_NOBODY]		= 1;
		// Common GET or POST request
		} else {
			$curl_opts[CURLOPT_HEADER]		= 0;
			$curl_opts[CURLOPT_ENCODING]	= 'gzip'; // The contents of the 'Accept-Encoding: ' header. This enables decoding
			if (!$url_options['no_autoreferer']) {
				$curl_opts[CURLOPT_AUTOREFERER] = 1; // set referer on redirect 
			}
		}
		// POST method
		if (isset($url_options['post'])) {
			$curl_opts[CURLOPT_POST]		= 1;
			$curl_opts[CURLOPT_POSTFIELDS]	= is_array($url_options['post']) ? http_build_query($url_options['post']) : $url_options['post'];
		}
		// Cookie string
		if ($url_options['cookie']) {
			$curl_opts[CURLOPT_COOKIE]		= $url_options['cookie'];
		}
		if ($url_options['cookie_file']) {
			$curl_opts[CURLOPT_COOKIEFILE]	= $url_options['cookie_file'];
		}
		if ($url_options['cookie_jar']) {
			$curl_opts[CURLOPT_COOKIEJAR]	= $url_options['cookie_jar'];
		}
		// If we need to get target url where remote server redirects us
		if ($url_options['get_redirected_url']) {
			$curl_opts[CURLOPT_NOBODY]		= 1;
		}
		// Custom network interface to use in request
		if (!empty($curlopt_interface)) {
			$curl_opts[CURLOPT_INTERFACE]	= $curlopt_interface;
		}
		// Not really used not because a bug, but maybe in future PHP version will work ok
		if ($is_ftp_url) {
			$curl_opts[CURLOPT_FTP_USE_EPRT]	= 0;
			$curl_opts[CURLOPT_FTP_USE_EPSV]	= 0;
			if (!$url_options['method_head']) {
				$curl_opts[CURLOPT_HEADER]		= 0;
				$curl_opts[CURLOPT_NOBODY]		= 0;
			}
		}
		// Enable a proxy connection for request
		// $options['proxy'] = array('host' => '192.168.1.1', 'port' => '8080', 'user' => '', 'pswd' => '');
		if ($url_options['proxy']) {
			$proxy = $url_options['proxy'];
			$curl_opts[CURLOPT_HTTPPROXYTUNNEL]	= true;
			$curl_opts[CURLOPT_PROXY]			= $proxy['host']. ($proxy['port'] ? ':'.$proxy['port'] : '');
			if (isset($proxy['user']) && isset($proxy['pswd'])) {
				$curl_opts[CURLOPT_PROXYUSERPWD] = $proxy['user'].':'.$proxy['pswd'];
			}
		}
		// HTTP basic auth support
		if ($url_options['auth']) {
			$auth = $url_options['auth'];
			$curl_opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
			if (isset($auth['user']) && isset($auth['pswd'])) {
				$curl_opts[CURLOPT_USERPWD] = $auth['user'].':'.$auth['pswd'];
			}
		}
		// Enable verbose debug output (usually into STDERR)
		if ($url_options['curl_verbose'] || $this->DEBUG) {
			$curl_opts[CURLOPT_VERBOSE] 	= true;
			if (main()->CONSOLE_MODE && $url_options['interactive']) {
				$interactive_console = true;
			}
			if (!$interactive_console) {
				$curl_opts[CURLINFO_HEADER_OUT] = true;
				$curl_opts[CURLOPT_HEADER]		= true;
			}
		}
		// Ability to override any other curl option
		if ($url_options['curl_opts']) {
			foreach ((array)$url_options['curl_opts'] as $k => $v) {
				$curl_opts[$k] = $v;
			}
		}
		if ($this->DEBUG && main()->CONSOLE_MODE) {
			print_r($this->pretty_dump_curl_opts($curl_opts));
		}
		return $curl_opts;
	}

	/**
	* 'Safe' multi_request, which splits input array into smaller chunks to prevent server breaking
	*/
	function multi_request_safe($page_urls = array(), $options = array(), $chunk_size = 50) {
		$response = array();
		$overall_multi_info = array();
		foreach (array_chunk($page_urls, $chunk_size, true) as $chunked_urls) {
			$tmp_response = $this->multi_request($chunked_urls, $options);
			foreach ((array)$tmp_response as $k => $v) {
				$response[$k] = $v;
			}
			// catch exec times ($GLOBALS['_curl_requests_info'][$url])
			// Because it is cleaned on each call
			foreach ((array)$GLOBALS['_curl_requests_info'] as $k => $v) {
				$overall_multi_info[$k] = $v;
			}
		}
		$GLOBALS['_curl_requests_info'] = $overall_multi_info;
		return $response;
	}

	/**
	* Get remote file size threaded
	*/
	function multi_file_size($page_urls, $options = array(), $max_threads = 50) {
		if (empty($max_threads)) {
			$max_threads = $this->CURL_DEF_MAX_THREADS;
		}
		if (empty($options)) {
			$options = array(
				'method_head'	=> 1,
				'max_redirects'	=> 5,
				'user_agent'	=> '',
				'referer'		=> '',
				'no_autoreferer'=> 1,
			);
		}
		$response = $this->_multi_request($page_urls, $options);
		$sizes = array();
		foreach ((array)$page_urls as $k => $v) {
			$info = $GLOBALS['_curl_requests_info'][$k];
			$sizes[$k] = (int)$info['download_content_length'];
			if ($sizes[$k] < 0) {
				$sizes[$k] = 0;
			}
			// Try to fix case when content-length appeared > 1 times and second time it is 0
			// so curl return 0 as content length
			if (empty($size[$k]) && empty($info['download_content_length']) && !empty($response[$k])) {
				if (preg_match('/Content-Length: (\d+)/i', $response[$k], $matches)) {
					$content_length = (int)$matches[1];
					if ($content_length) {
						$sizes[$k] = $content_length;
					}
				}
			}
		}
		return $sizes;
	}

	/**
	* Perform an HTTP request.
	* This is a flexible and powerful HTTP client implementation. Correctly handles GET, POST, PUT or any other HTTP requests. Handles redirects.
	*
	* @param $url A string containing a fully qualified URI.
	* @param $headers  An array containing an HTTP header => value pair.
	* @param $method  A string defining the HTTP request to use.
	* @param $data  A string containing data to include in the request.
	* @param $retry  An integer representing how many times to retry the request in case of a redirect.
	* @return An object containing the HTTP request headers, response code, headers, data, and redirect status.
	*/
	function http_request($url, $headers = array(), $method = 'GET', $data = NULL, $retry = 3) {
		$result = new stdClass();
		// Parse the URL, and make sure we can handle the schema.
		$uri = parse_url($url);
		switch ($uri['scheme']) {
			case 'http':
				$port = isset($uri['port']) ? $uri['port'] : 80;
				$host = $uri['host'] . ($port != 80 ? ':'. $port : '');
				$fp = @fsockopen($uri['host'], $port, $errno, $errstr, 15);
				break;
			case 'https':
				// Note: Only works for PHP 4.3 compiled with OpenSSL.
				$port = isset($uri['port']) ? $uri['port'] : 443;
				$host = $uri['host'] . ($port != 443 ? ':'. $port : '');
				$fp = @fsockopen('ssl://'. $uri['host'], $port, $errno, $errstr, 20);
				break;
			default:
				$result->error = 'invalid schema '. $uri['scheme'];
				return $result;
		}
		// Make sure the socket opened properly.
		if (!$fp) {
			$result->error = trim($errno .' '. $errstr);
			return $result;
		}
		// Construct the path to act on.
		$path = isset($uri['path']) ? $this->_fix_url($uri['path']) : '/';
		if (isset($uri['query'])) {
			$path .= '?'. $uri['query'];
		}
		// Create HTTP request.
		$defaults = array(
			// RFC 2616: 'non-standard ports MUST, default ports MAY be included'.
			// We don't add the port to prevent from breaking rewrite rules checking
			// the host that do not take into account the port number.
			'Host' => 'Host: '.$host,
			'User-Agent' => 'User-Agent: YF (+http://yfix.dev/)',
			'Content-Length' => 'Content-Length: '. strlen($data)
		);
		foreach ((array)$headers as $header => $value) {
			$defaults[$header] = $header .': '. $value;
		}
		$request = $method .' '. $path ." HTTP/1.0\r\n";
		$request .= implode("\r\n", $defaults);
		$request .= "\r\n\r\n";
		if ($data) {
			$request .= $data ."\r\n";
		}
		$result->request = $request;
	
		fwrite($fp, $request);
		// Fetch response.
		$response = '';
		while (!feof($fp) && $chunk = fread($fp, 1024)) {
			$response .= $chunk;
		}
		fclose($fp);
	
		// Parse response.
		list($split, $result->data) = explode("\r\n\r\n", $response, 2);
		$split = preg_split("/\r\n|\n|\r/", $split);
	
		list($protocol, $code, $text) = explode(' ', trim(array_shift($split)), 3);
		$result->headers = array();
	
		// Parse headers.
		while ($line = trim(array_shift($split))) {
			list($header, $value) = explode(':', $line, 2);
			if (isset($result->headers[$header]) && $header == 'Set-Cookie') {
				// RFC 2109: the Set-Cookie response header comprises the token Set-
				// Cookie:, followed by a comma-separated list of one or more cookies.
				$result->headers[$header] .= ','. trim($value);
			}
			else {
				$result->headers[$header] = trim($value);
			}
		}
		$responses = array(
			100 => 'Continue', 101 => 'Switching Protocols',
			200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content',
			300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect',
			400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Time-out', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Large', 415 => 'Unsupported Media Type', 416 => 'Requested range not satisfiable', 417 => 'Expectation Failed',
			500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Time-out', 505 => 'HTTP Version not supported'
		);
		// RFC 2616 states that all unknown HTTP codes must be treated the same as the base code in their class.
		if (!isset($responses[$code])) {
			$code = floor($code / 100) * 100;
		}
		switch ($code) {
			case 200: // OK
			case 304: // Not modified
				break;
			case 301: // Moved permanently
			case 302: // Moved temporarily
			case 307: // Moved temporarily
				$location = $result->headers['Location'];
	
				if ($retry) {
					$result = $this->http_request($result->headers['Location'], $headers, $method, $data, --$retry);
					$result->redirect_code = $result->code;
				}
				$result->redirect_url = $location;
	
				break;
			default:
				$result->error = $text;
		}
		$result->code = $code;
		return $result;
	}

	/**
	* Verify url using remote call
	*/
	function _validate_url_by_http($url) {
		if (empty($url)) {
			return false;
		}
		if (!preg_match('/^(^http:\/\/)(.)+/', $url)) {
			$url = 'http://'.$url;
		} 
		$url = $this->_fix_url($url);
		if (!common()->url_verify($url)) {
			return false;
		}
		$uri = parse_url($url);
		$ip = gethostbyname($uri['host']);
		if ($ip == $uri['host']) {
			return false;
		} 
		$allowed_codes = array(
			'200',	// OK
			'300',	// Multiple Choices
			'301',	// Moved Permanently
			'302',	// Found
			'303',	// See Other (since HTTP/1.1)
			'304',	// Not Modified
			'307',	// Temporary Redirect (since HTTP/1.1)
		);
		$headers = common()->http_request($url);
		if (in_array($headers->code, $allowed_codes)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	* Extended email verification method
	*/
	function _email_verify ($email = '', $check_mx = false, $check_by_smtp = false, $check_blacklists = false) {
		if (empty($email)) {
			return false;
		}
		$debug	= $GLOBALS['_email_verify_debug'];
		$result = false;
		// First simple check by regexp
		$p_user = '\w+([\.-]?\w+)*';
		$p_domain = '\w+([\.-]?\w+)*(\.\w{2,})+';
		$p_ipv4 = '[0-9]{1,3}(\.[0-9]{1,3}){3}';
		$result = preg_match('/^'.$p_user.'@('.$p_domain.'|'.$p_ipv4.')$/ims', $email);
		if ($result) {
			list($user, $domain) = explode('@', $email);
		}
		// Check availability of DNS MX records
		if ($result && $check_mx) {
			$mailers = array();
			// Construct array of available mailservers
			if (function_exists('getmxrr')) {
				if (getmxrr($domain, $mxhosts, $mxweight)) {
					for ($i = 0; $i < count($mxhosts); $i++){
						$mxs[$mxhosts[$i]] = $mxweight[$i];
					}
					asort($mxs);
					$mailers = array_keys($mxs);
				}
			} else {
				@exec('nslookup -type=mx '.$domain, $_mx_result);
				foreach ((array)$_mx_result as $_key => $_value) {
					if (strstr($_value, 'mail exchanger')) {
						$_nslookup[$i++] = $_value;
					}
				}
				$_mx = array();
				foreach ((array)$_nslookup as $_key => $_value) {
					preg_match('/MX preference = ([0-9]+), mail exchanger = (.+)$/i', $_value, $_m);
					if (empty($_m[2])) {
						continue;
					}
					$_mx[$_m[2]] = $_m[1];
				}
				asort($_mx);
				$mailers = array_keys($_mx);
			}
			// Another try
			if (empty($mailers) && checkdnsrr($domain, 'A')) {
				$mailers[0] = gethostbyname($domain);
			}
			$total = count($mailers);
			if (!$total) {
				$_error_msg .= 'No usable DNS records found for domain "'.$domain.'"'.PHP_EOL;
			} else {
				$result = true;
			}
		}
		// Check SMTP probe host ad\nd address (these are required)
		if ($result && $check_by_smtp && !empty($total) && empty($_error_msg)) {
			if (empty($this->SMTP_PROBE_HOST) || empty($this->SMTP_PROBE_ADDRESS)) {
				$_error_msg .= 'Internal error: "SMTP_PROBE_HOST" AND "SMTP_PROBE_ADDRESS" required!'.PHP_EOL;
			}
		}
		// Check using SMTP
		if ($result && $check_by_smtp && !empty($total) && empty($_error_msg)) {
			// Query each mailserver
			for ($n = 0; $n < $total; $n++) {
				// Check if mailers accept mail
				if ($debug) {
					$_debug_info .= 'Checking server '.$mailers[$n].'...'.PHP_EOL;
				}
				$connect_timeout	= 2;
				$errno				= 0;
				$errstr				= 0;
				// Try to open up socket
				if ($sock = @fsockopen($mailers[$n], 25, $errno , $errstr, $connect_timeout)) {
					$response = fgets($sock);
					if ($debug) {
						$_debug_info .= 'Opening up socket to '.$mailers[$n].'... Succes!'.PHP_EOL;
					}
					stream_set_timeout($sock, 5);
					$meta = stream_get_meta_data($sock);
					if ($debug) {
						$_debug_info .= $mailers[$n].' replied: '.$response.PHP_EOL;
					}
					$cmds = array(
						'HELO '.$this->SMTP_PROBE_HOST,  // Be sure to set this correctly!
						'MAIL FROM: <'.$this->SMTP_PROBE_ADDRESS.'>',
						'RCPT TO: <'.$email.'>',
						'QUIT',
					);
					// Hard error on connect -> break out
					if (!$meta['timed_out'] && !preg_match('/^2\d\d[ -]/', $response)) {
						$_error_msg .= 'Error: '.$mailers[$n].' said: '.$response.PHP_EOL;
						break;
					}
					foreach ((array)$cmds as $_cmd_num => $cmd) {
						$before = microtime(true);
						fputs($sock, $cmd."\r\n");
						$response = fgets($sock, 4096);
						$t = 1000 * (microtime(true) - $before);
						if ($debug) {
							$_debug_info .= htmlentities($cmd.PHP_EOL.$response) . '('.sprintf('%.2f', $t) . ' ms)'.PHP_EOL;
						}
						if (!$meta['timed_out'] && preg_match('/^5\d\d[ -]/', $response)) {
							$_error_msg .= 'Unverified address: '.$mailers[$n].' said: '.$response;
							break 2;
						}
						// Check if greylisted
						if ($_cmd_num == 2 && in_array(intval(substr($response, 0, 3)), array(450,451))) {
							$_debug_info .= 'Greylisted address: '.$mailers[$n].' said: '.$response;
							continue 2;
						}
					}
					fclose($sock);
					if ($debug) {
						$_debug_info .= 'Succesful communication with '.$mailers[$n].', no hard errors, assuming OK';
						$result = true;
					}
					break;
				} elseif ($n == $total - 1) {
					$_error_msg = 'None of the mailservers listed for '.$domain.' could be contacted';
				}
			}
		}
		// Check for errors
		if (!empty($_error_msg)) {
			$result = false;
		}
		// Check using blacklists
		if ($result && $check_blacklists && !empty($total)) {
			for ($n = 0; $n < $total; $n++) {
				$response = $this->_not_blacklisted($mailers[$n]);
				if ($response) {
					$_error_msg = 'Address '.$mailers[$n].' is blacklisted: '.$response;
					break;
				}
			}
		}
		// Check for errors
		if (!empty($_error_msg)) {
			$result = false;
		}
		// Prepare debug output
		$GLOBALS['_email_verify_output'] = 
			"<pre>\n"
			."\nMailers:\n".print_r($mailers, 1)
			.($_error_msg ? "\nError:\n".print_r($_error_msg, 1) : "")
			.($_debug_info ? "\nDebug info:\n".print_r($_debug_info, 1) : "")
			."</pre>\n";
		// result (bool)
		return $result;
	}

	/*
	* Check DNSBL - WIN also
	*/
	function _not_blacklisted($ip) {
		if (empty($ip)) {
			return false;
		}
		$_ip_pattern = '/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/';
		// Try to get IP by host name
		if (!preg_match($_ip_pattern, $ip)) {
			$ip = gethostbyname($ip);
		}
		// Last check for valid IP address
		if (!preg_match($_ip_pattern, $ip)) {
			return false;
		}
		$reverse_ip = implode('.', array_reverse(explode('.', $ip)));
		$dnsbl_lists = array(
			'bl.spamcop.net',
			'list.dsbl.org',
			'sbl.spamhaus.org'
		);
		foreach ((array)$dnsbl_lists as $dnsbl_list) {
			if (checkdnsrr($reverse_ip . '.' . $dnsbl_list . '.', 'A')) {
				return $reverse_ip . '.' . $dnsbl_list;
			} 
		}
		return false;
	}

	/**
	* Alternale remote_file_size but with one very useful feature:
	* it goes using method GET, but receives only first 4kb of data
	* saving lot of traffic on large files that could not be sized using HEAD
	*/
	function alternate_remote_file_size($url, $retry = 3) {
		$method = 'GET';

		$time_start = microtime(true);

		$uri = parse_url($url);
		switch ($uri['scheme']) {
			case 'http':
				$port = isset($uri['port']) ? $uri['port'] : 80;
				$host = $uri['host'] . ($port != 80 ? ':'. $port : '');
				$fp = @fsockopen($uri['host'], $port, $errno, $errstr, 15);
				break;
			case 'https':
				// Note: Only works for PHP 4.3 compiled with OpenSSL.
				$port = isset($uri['port']) ? $uri['port'] : 443;
				$host = $uri['host'] . ($port != 443 ? ':'. $port : '');
				$fp = @fsockopen('ssl://'. $uri['host'], $port, $errno, $errstr, 20);
				break;
			default:
				$result['error'] = 'invalid schema '. $uri['scheme'];
				return $result;
		}
		if (!$fp) {
			$result['error'] = trim($errno .' '. $errstr);
			return $result;
		}
		// Construct the path to act on.
		$path = isset($uri['path']) ? $this->_fix_url($uri['path']) : '/';
		if (isset($uri['query'])) {
			$path .= '?'. $uri['query'];
		}
		$defaults = array(
			'Host' => 'Host: '.$host,
		);
		foreach ((array)$headers as $header => $value) {
			$defaults[$header] = $header .': '. $value;
		}
		$request = $method .' '. $path ." HTTP/1.0\r\n";
		$request .= implode("\r\n", $defaults);
		$request .= "\r\n\r\n";
		if ($data) {
			$request .= $data ."\r\n";
		}
		$result['request'] = $request;

		fwrite($fp, $request);
		// Get only first 4kb of response
		$response = '';
		$response .= fread($fp, 4096);
		fclose($fp);

		list($split, $result['data']) = explode("\r\n\r\n", $response, 2);
		$split = preg_split("/\r\n|\n|\r/", $split);
		list($protocol, $code, $text) = explode(' ', trim(array_shift($split)), 3);
		// Parse headers.
		$result['headers'] = array();
		while ($line = trim(array_shift($split))) {
			list($header, $value) = explode(':', $line, 2);
			if (isset($result['headers'][$header]) && $header == 'Set-Cookie') {
				$result['headers'][$header] .= ','. trim($value);
			} else {
				$result['headers'][$header] = trim($value);
			}
		}
		$responses = array(
			100, 101,
			200, 201, 202, 203, 204, 205, 206,
			300, 301, 302, 303, 304, 305, 307,
			400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417,
			500, 501, 502, 503, 504, 505,
		);
		if (!in_array($code, $responses)) {
			$code = floor($code / 100) * 100;
		}
		switch ($code) {
			case 200: // OK
			case 304: // Not modified
				break;
			case 301:
			case 302:
			case 307:
				$location = $result['headers']['Location'];
				if ($retry) {
					$result = $this->alternate_remote_file_size($result['headers']['Location'], --$retry);
					$result['redirect_code'] = $result['code'];
				}
				$result['redirect_url'] = $location;
				break;
			default:
				$result['error'] = $text;
		}
		$result['code'] = $code;

		$time_end = microtime(true);

		$result['emulate_curl_info'] = array(
			'url'				=> $url,
			'content_type'		=> $result['headers']['Content-Type'],
			'http_code'			=> $code,
			'header_size'		=> !empty($result['headers'])?strlen(implode("\r\n", $result['headers'])):0,
			'request_size'		=> strlen($request),
			'filetime'			=> -1,
			'ssl_verify_result'	=> 0,
			'redirect_count'	=> 0,
			'total_time'		=> ($time_end - $time_start),
			'namelookup_time'	=> 0,
			'connect_time'		=> 0,
			'pretransfer_time'	=> 0,
			'size_upload'		=> 0,
			'size_download'		=> 0,
			'speed_download'	=> 0,
			'speed_upload'		=> 0,
			'download_content_length'=> (int)$result['headers']['Content-Length'],
			'upload_content_length'	=> 0,
			'starttransfer_time'=> 0,
			'redirect_time'		=> 0,
			'CURL_ERRNO'		=> 0,
			'CURL_ERROR'		=> '',
		);
		return $result;
	}
}
