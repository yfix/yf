<?php

/**
* Sphinx search querying
*/
class yf_sphinxsearch {

	/** In seconds */
	private $CONNECT_TIMEOUT = 2;
	/** Just counter, set to 0 to disable reconnect tries */
	private $RECONNECT_TRIES = 2;
	/** In milli-seconds (1 second == 1000000) */
	private $RECONNECT_SLEEP = 1000000;
	/** In milli-seconds (1 second == 1000000) */
	private $QUERY_RETRY_SLEEP = 1000000;
	/**
	* Lis of mysql client library errors, returned from sphinxsearch server, when we can retry query
	*	2003 - Can't connect to MySQL server on
	*	2006 - MySQL server has gone away
	*	2013 - Lost connection to MySQL server during query
	*	2020 - Got packet bigger than 'max_allowed_packet' bytes
	*/
	private $QUERY_RETRY_ERROR_CODES = array(2003,2006,2013,2020);
	/** [Resource] or null */
	private $sphinx_connection = null;
	/** Host:port like this: 127.0.0.1:9306 */
	private $HOST = '127.0.0.1:9306';
	/***/
	private $DEF_PORT = '9306';
	/***/
	private $EMPTY_RESULTS_LOG_PATH = '';
	/***/
	private $CACHE_TTL = 300;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function _init () {
		// SPHINX_HOST can contain port or not, both variants supported
		if (defined('SPHINX_HOST')) {
			$this->HOST = SPHINX_HOST;
			if (false === strpos($this->HOST, ':')) {
				$this->HOST .= ':'.(defined('SPHINX_HOST') ? SPHINX_PORT : $this->DEF_PORT);
			}
		}
		if (!$this->EMPTY_RESULTS_LOG_PATH && common()->SPHINX_EMPTY_LOG_PATH) {
			$this->EMPTY_RESULTS_LOG_PATH = common()->SPHINX_EMPTY_LOG_PATH;
		}
	}

	/**
	* Sphinx QL query wrapper
	*/
	function query ($sql, $need_meta = false) {
		if (empty($sql)) {
			return false;
		}
		if (DEBUG_MODE) {
			$trace = main()->trace_string();
			$time =  microtime(true);
		}
		$data = null;
		$CACHE_NAME = 'SPHINX_'.md5($sql);
		$cached = cache_get($CACHE_NAME);
		if ($cached) {
			list($data, $meta, $warnings, $query_error, $describe, $time_wo_cache) = $cached;
			if (DEBUG_MODE) {
				$this->_set_query_debug(array(
					'query'			=> $sql,
					'time'			=> $time,
					'trace'			=> $trace,
					'meta'			=> $meta,
					'warnings'		=> $warnings,
					'describe'		=> $describe,
					'error'			=> $query_error,
					'results'		=> $data,
					'cached'		=> 1,
					'time_wo_cache'	=> $time_wo_cache,
				));
			}
			$GLOBALS['_SPHINX_META'] = $meta;
			return $data;
		}
		$results		= array();
		$query_error	= '';
		$q_error_num	= '';
		if (!isset($this->sphinx_connection)) {
			$this->_connect();
		}
		if ($this->sphinx_connection) {
			$q = mysql_query($sql, $this->sphinx_connection);
			if (!$q) {
				$query_error = mysql_error($this->sphinx_connection);
				$q_error_num = mysql_errno($this->sphinx_connection);
				// Try to execute query again in case of these errors returned:
				if (in_array($q_error_num, $this->QUERY_RETRY_ERROR_CODES)) {
					usleep($this->QUERY_RETRY_SLEEP);
					$q = mysql_query($sql, $this->sphinx_connection);
					if (!$q) {
						$query_error = mysql_error($this->sphinx_connection);
						$q_error_num = mysql_errno($this->sphinx_connection);
					}
				}
				if ($query_error) {
					trigger_error('Sphinx error: '.$query_error.'; for query: '.$sql, E_USER_WARNING);
					conf('http_headers::X-Details', conf('http_headers::X-Details').';SE=('.$q_error_num.') '.$query_error.';');
				}
			}
			if ($q) {
				while ($a = mysql_fetch_assoc($q)) {
					$results[] = $a;
				}
				if (count($results) == 0) {
					$this->_save_empty_results_log($sql);
				}
			}
			$meta			= array();
			$warnings		= array();
			$describe		= array();
			if ($need_meta || DEBUG_MODE) {
				$meta = $this->_get_latest_meta();
				$warnings = $this->_get_latest_warnings();
				$describe = $this->_get_latest_describe($sql);
			}
			$time_wo_cache = 0;
			if (DEBUG_MODE) {
				$this->_set_query_debug(array(
					'query'	=> $sql,
					'time'	=> $time,
					'trace'	=> $trace,
					'meta'	=> $meta,
					'error'	=> $query_error,
					'warnings' => $warnings,
					'describe' => $describe,
					'results' => $results,
				));
				$time_wo_cache = microtime(true) - $time;
			}
		}
		if (empty($query_error) && $this->sphinx_connection) {
			cache_set($CACHE_NAME, array($results, $meta, $warnings, $query_error, $describe, $time_wo_cache), $this->CACHE_TTL);
		}
		return $results;
	}

	/**
	*/
	function _connect() {
		if (isset($this->sphinx_connection)) {
			return $this->sphinx_connection;
		}
		if ($this->_connects_tried > $this->RECONNECT_TRIES) {
			return false;
		}
		if (DEBUG_MODE) {
			$time =  microtime(true);
		}
		// For sphinxsearch we need small connect timeout, also save default for later restore
		if ($this->CONNECT_TIMEOUT) {
			$orig_connect_timeout = ini_get('mysql.connect_timeout');
			if ($orig_connect_timeout != $this->CONNECT_TIMEOUT) {
				ini_set('mysql.connect_timeout', $this->CONNECT_TIMEOUT);
			}
		}
		$this->sphinx_connection = mysql_connect($this->HOST, '', '', $new_link = true);
		$this->_connects_tried++;
		// Try to reconnect
		if (!$this->sphinx_connection && $this->RECONNECT_TRIES) {
			for ($i = 0; $i < $this->RECONNECT_TRIES; $i++) {
				usleep($this->RECONNECT_SLEEP);
				$this->sphinx_connection = mysql_connect($this->HOST, '', '', $new_link = true);
				$this->_connects_tried++;
			}
		}
		if (!$this->sphinx_connection) {
			$query_error = mysql_error($this->sphinx_connection);
			$q_error_num = mysql_errno($this->sphinx_connection);
			conf('http_headers::X-Details', conf('http_headers::X-Details').';SE=('.$q_error_num.') '.$query_error.';');
			trigger_error('No connection to sphinx', E_USER_WARNING);
		}
		if (DEBUG_MODE) {
			$this->_set_query_debug(array(
				'query'	=> 'sphinx connect',
				'time'	=> $time,
				'error'	=> $query_error,
			));
		}
		// Revert default mysql connect timeout
		if ($this->CONNECT_TIMEOUT && $orig_connect_timeout != $this->CONNECT_TIMEOUT) {
			ini_set('mysql.connect_timeout', $orig_connect_timeout);
		}
		return $this->sphinx_connection;
	}

	/**
	*/
	function escape_string ($string) {
		$from = array ( "\\", '(',')','|','-','!','@','~','"','&', '/', '^', '$', '=' );
		$to   = array ( "\\\\", '\(','\)','\|','\-','\!','\@','\~','\"', '\&', '\/', '\^', '\$', '\=' );
		return str_replace ( $from, $to, $string );
	}

	/**
	* Get latest query internal details
	*/
	function _get_latest_meta() {
		if (!$this->sphinx_connection) {
			$this->_connect();
		}
		if (!$this->sphinx_connection) {
			return false;
		}
		$q = mysql_query('SHOW META', $this->sphinx_connection);
		if (!is_bool($q)) {
			while ($a = mysql_fetch_row($q)) {
				$meta[$a[0]] = $a[1];
			}
		}
		$GLOBALS['_SPHINX_META'] = $meta;
		return $meta;
	}

	/**
	* Get latest query warnings
	*/
	function _get_latest_warnings() {
		if (!$this->sphinx_connection) {
			$this->_connect();
		}
		if (!$this->sphinx_connection) {
			return false;
		}
		$q = mysql_query('SHOW WARNINGS', $this->sphinx_connection);
		if (!is_bool($q)) {
			while ($a = mysql_fetch_row($q)) {
				$warnings[$a[0]] = $a[1];
			}
		}
		$GLOBALS['_SPHINX_WARNINGS'] = $warnings;
		return $warnings;
	}

	/**
	*/
	function _get_latest_describe($sql = '') {
		if (!$this->sphinx_connection) {
			$this->_connect();
		}
		if (!$this->sphinx_connection) {
			return false;
		}
		$describe = array();
		if (preg_match('/SELECT[\s\t]+.+[\s\t]+FROM[\s\t]+([a-z0-9\_]+)[\s\t]+WHERE[\s\t]+/ims', $sql, $m)) {
			$describe_sql = 'DESCRIBE '.$m[1];
			$q = mysql_query('DESCRIBE '.$m[1], $this->sphinx_connection);
			if (!is_bool($q)) {
				while ($a = mysql_fetch_row($q)) {
					$describe[$a[0]] = $a[1];
				}
			}
		}
		return $describe;
	}

	/**
	*/
	function _get_server_status() {
		if (!$this->sphinx_connection) {
			$this->_connect();
		}
		if (!$this->sphinx_connection) {
			return false;
		}
		$status = array();
		$q = mysql_query('SHOW STATUS', $this->sphinx_connection);
		if (!is_bool($q)) {
			while ($a = mysql_fetch_row($q)) {
				$status[$a[0]] = $a[1];
			}
		}
		return $status;
	}

	/**
	*/
	function _get_server_version() {
		if (!$this->sphinx_connection) {
			$this->_connect();
		}
		if (!$this->sphinx_connection) {
			return false;
		}
		return mysql_get_server_info($this->sphinx_connection);
	}

	/**
	*/
	function _get_host() {
		return $this->HOST;
	}

	/**
	*/
	function _save_empty_results_log($sql) {
		if (!$this->EMPTY_RESULTS_LOG_PATH) {
			return false;
		}
		$out = implode('#|#', array(
			date('YmdH'),
			conf('CUR_DOMAIN_SHORT'),
			common()->_db_escape($_SERVER['HTTP_REFERER']),
			common()->_db_escape($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']),
			common()->_db_escape($_SERVER['HTTP_USER_AGENT']),
			common()->_db_escape($sql),
		)). PHP_EOL;
		return file_put_contents($this->EMPTY_RESULTS_LOG_PATH, $out, FILE_APPEND);
	}

	/**
	*/
	function _set_query_debug($a) {
		debug('sphinxsearch[]', array(
			'query'		=> $a['query'],
			'results'	=> $a['results'],
			'count'		=> is_array($a['results']) ? intval(count($a['results'])) : '',
			'meta'		=> $a['meta'],
			'error'		=> $a['error'],
			'warnings'	=> $a['warnings'],
			'describe'	=> $a['describe'],
			'trace'		=> $a['trace'] ?: main()->trace_string(),
			'cached'	=> (int)$a['cached'],
			'time'		=> round(microtime(true) - $a['time'], 5),
			'time_wo_cache'	=> $a['time_wo_cache'] ? round($a['time_wo_cache'], 5) : '',
		));
	}
}
