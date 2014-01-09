<?php

/**
* Sphinx search querying
*/
class yf_sphinxsearch {

	/**
	* Sphinx QL query wrapper
	*/
	function query ($sql, $need_meta = false) {
		if (empty($sql)) {
			return false;
		}
		if (DEBUG_MODE) {
			$trace = main()->trace_string();
		}
		$time =  microtime(true);

		ini_set('mysql.connect_timeout', 2);

		$CACHE_NAME = 'SPHINX_'.md5($sql);
		$data = cache_get($CACHE_NAME);
		if ($data) {
			list($data, $meta, $warnings, $query_error) = $data;
			if (DEBUG_MODE) {
				$debug_index = count(debug('sphinx'));
				debug('sphinx::'.$debug_index, array(
					'query'	=> $sql,
					'time'	=> microtime(true) - $time,
					'trace'	=> $trace,
					'count'	=> count($data),
					'meta'	=> $meta,
					'warnings'	=> $warnings,
					'error'	=> $query_error,
					'cached'=> 1,
					'results' => null,
				));
			}
			$GLOBALS['_SPHINX_META'] = $meta;
			return $data;
		}

	 	$host = SPHINX_HOST.':'.SPHINX_PORT;

		if (!isset($this->sphinx_connect)) {
			$time =  microtime(true);
			$this->sphinx_connect = mysql_connect($host, DB_USER, DB_PSWD, true);

			// Try to reconnect
			if(!$this->sphinx_connect){
				usleep(1000000);	// wait for 1 second
				$this->sphinx_connect = mysql_connect($host, DB_USER, DB_PSWD, true);
			}
			if(!$this->sphinx_connect){
				$query_error = mysql_error($this->sphinx_connect);
				$q_error_num = mysql_errno($this->sphinx_connect);
				conf('http_headers::X-Details', conf('http_headers::X-Details').';SE=('.$q_error_num.') '.$query_error.';');
				trigger_error('No connection to sphinx', E_USER_WARNING);
			}
			if (DEBUG_MODE) {
				$debug_index = count(debug('sphinx'));
				debug('sphinx::'.$debug_index, array(
					'query'	=> 'sphinx connect',
					'time'	=> microtime(true) - $time,
					'count'	=> '',
					'trace'	=> $trace,
					'meta'	=> '',
					'error'	=> $query_error,
				));
			}
		}
		$meta		= array();
		$warnings	= array();
		$results	= array();
		$query_error= '';
		$q_error_num= '';
		if ($this->sphinx_connect) {
			$Q = mysql_query($sql, $this->sphinx_connect);
			if (!$Q) {
				$query_error = mysql_error($this->sphinx_connect);
				$q_error_num = mysql_errno($this->sphinx_connect);
				// Try to execute query again in case of these errors returned:
				// 2003 - Can't connect to MySQL server on
				// 2006 - MySQL server has gone away
				// 2013 - Lost connection to MySQL server during query
				// 2020 - Got packet bigger than 'max_allowed_packet' bytes
				if (in_array($q_error_num, array(2003,2006,2013,2020))) {
					usleep(1000000);	// wait for 1 second
					$Q = mysql_query($sql, $this->sphinx_connect);
					if (!$Q) {
						$query_error = mysql_error($this->sphinx_connect);
						$q_error_num = mysql_errno($this->sphinx_connect);
					}
				}
				if ($query_error) {
					trigger_error('Sphinx error: '.$sql, E_USER_WARNING);
					conf('http_headers::X-Details', conf('http_headers::X-Details').';SE=('.$q_error_num.') '.$query_error.';');
				}
			} else {
				while ($A = mysql_fetch_assoc($Q)) {
					$results[] = $A;
				}
				// log empty results
				if (count($results) == 0 && $this->SPHINX_EMPTY_LOG_PATH != '') {
					$out = array(
						date('YmdH'),
						conf('CUR_DOMAIN_SHORT'),
						common()->_db_escape($_SERVER['HTTP_REFERER']),
						common()->_db_escape($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']),
						common()->_db_escape($_SERVER['HTTP_USER_AGENT']),			
						common()->_db_escape($sql),
					);
					file_put_contents(common()->SPHINX_EMPTY_LOG_PATH, implode('#|#',$out).PHP_EOL, FILE_APPEND);
				}
			}
			if (DEBUG_MODE || $need_meta) {
				// Get query internal details
				$Q = mysql_query('SHOW META', $this->sphinx_connect);
				if (!is_bool($Q)) {
					while ($A = mysql_fetch_row($Q)) {
						$meta[$A[0]] = $A[1];
					}
				}
				$GLOBALS['_SPHINX_META'] = $meta;

				// Get query warnings
				$Q = mysql_query('SHOW WARNINGS', $this->sphinx_connect);
				if (!is_bool($Q)) {
					while ($A = mysql_fetch_row($Q)) {
						$warnings[$A[0]] = $A[1];
					}
				}
				$GLOBALS['_SPHINX_WARNINGS'] = $warnings;
			}
			if (DEBUG_MODE) {
				$debug_index = count(debug('sphinx'));
				debug('sphinx::'.$debug_index, array(
					'query'	=> $sql,
					'time'	=> microtime(true) - $time,
					'count'	=> intval(count($results)),
					'trace'	=> $trace,
					'meta'	=> $meta,
					'error'	=> $query_error,
					'warnings' => $warnings,
					'results' => $results,
				));
			}
		}
		if (empty($query_error) && $this->sphinx_connect) {
			cache_set($CACHE_NAME, array($results, $meta, $warnings, $query_error), 300);
		}
		return $results;
	}
	
	/**
	* Sphinx-related
	*/
	function escape_string ( $string ){
		$from = array ( "\\", '(',')','|','-','!','@','~','"','&', '/', '^', '$', '=' );
		$to   = array ( "\\\\", '\(','\)','\|','\-','\!','\@','\~','\"', '\&', '\/', '\^', '\$', '\=' );
		return str_replace ( $from, $to, $string );
	}
}
