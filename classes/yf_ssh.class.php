<?php

/**
* SSH Client (based on SSH2 PHP extension)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_ssh {

// TODO: chained mode like in form and table, mass actions on one server, array of servers, named group(s) of servers:
// ssh('s23.dev')->exec('service memcached restart');
// ssh('s23*.dev')->exec('service memcached restart');
// ssh('s23.dev','s567.dev')->exec('service memcached restart');
// ssh(array('s23.dev','s567.dev'), 's89*.dev')->exec('service memcached restart');
// ssh('group:nginx_proxy')->exec('service nginx reload');
// ssh('group:nginx_proxy')->exec('service nginx reload', 'service cron reload');
// ssh('group:nginx_proxy', 's345.dev')->exec(array('service nginx reload', 'service cron reload'))->write_string('/tmp/last_mass_ssh_action.done', time());
// ssh('group:nginx_proxy', 's345.dev')->each(function($srv, $ssh){ $ssh->exec('service nginx reload'); });

	/** @var string @conf_skip array('phpseclib','pecl_ssh2','auto') */
	public $DRIVER				= 'phpseclib';
	/** @var bool @conf_skip */
	public $_INIT_OK			= false;
	/** @var string @conf_skip */
	public $_TMP_DIR			= 'uploads/tmp';
	/** @var enum('password','pubkey') */
	public $AUTH_TYPE			= 'password';
	/** @var bool Save actions log or not */
	public $LOG_ACTIONS			= false;
	/** @var bool Use archiving for mass actions */
	public $MASS_USE_ARCHIVES	= true;
	/** @var string Path to the tar archiver in console */
	public $TAR_PATH			= '';
	/** @var string Path to the gzip console programm */
	public $GZIP_PATH			= '';
	/** @var bool */
	public $USE_GZIP			= true;
	/** @var bool */
	public $CONNECT_TIMEOUT		= 5;
	/** @var bool */
	public $MAX_RECONNECTS		= 1;
	/** @var bool */
	public $LOG_FULL_EXEC		= 0;
	/** @var bool */
	public $_debug				= array();

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Framework constructor
	*/
	function _init () {
		if (!$this->DRIVER) {
			$this->DRIVER = 'phpseclib';
		}
		$test_phpseclib_path = YF_PATH.'libs/phpseclib/phpseclib/Net/SSH2.php';
		if ($this->DRIVER == 'phpseclib' && !file_exists($test_phpseclib_path)) {
			trigger_error('phpseclib Net_SSH2 not found', E_USER_WARNING);
			return false;
		} elseif ($this->DRIVER == 'pecl_ssh2' && !function_exists('ssh2_connect')) {
			trigger_error('function ssh2_connect does not exist', E_USER_WARNING);
			return false;
		} else {
			$this->_INIT_OK = true;
		}

		if ($this->_INIT_OK && $this->DRIVER == 'phpseclib') {
			set_include_path (YF_PATH.'libs/phpseclib/'. PATH_SEPARATOR. get_include_path());
			require_once('Crypt/RSA.php');
			require_once('Net/SSH2.php');
		}
	}

	/**
	* Return internal SERVER_ID (usually 'ssh_host:ssh_port')
	*/
	function _get_server_id ($server_info = array()) {
		if (!$server_info) {
			return false;
		}
		$ssh_host	= $server_info['base_ip'] ? $server_info['base_ip'] : $server_info['ssh_host'];
		$ssh_port	= $server_info['ssh_port'] ? $server_info['ssh_port'] : 22;
		if (!$ssh_host) {
			return false;
		}
		return $ssh_host.':'.$ssh_port;
	}

	/**
	* Return remote OS string
	*/
	function _get_remote_os ($server_info = array()) {
		$_SERVER_ID = $this->_get_server_id($server_info);
		if (isset($this->_ssh_cache_os[$_SERVER_ID])) {
			return $this->_ssh_cache_os[$_SERVER_ID];
		}
		$result = strtoupper(trim($this->exec($server_info, 'uname')));
		$this->_ssh_cache_os[$_SERVER_ID] = $result;
		return $result;
	}

	/**
	* Connect to the remote server
	*
	* @example
	*
	* $server_info = array(
	*	'ssh_host'	=> '192.168.1.2',
	*	'ssh_user'	=> 'root',
	*	'ssh_pswd'	=> '111111',
	* );
	*/
	function connect ($server_info = array()) {
		if (!$this->_INIT_OK || !$server_info) {
			return false;
		}
		$ssh_host	= $server_info['base_ip'] ? $server_info['base_ip'] : $server_info['ssh_host'];
		$ssh_port	= $server_info['ssh_port'] ? $server_info['ssh_port'] : 22;
		if (!$ssh_host) {
			trigger_error('SSH: missing server IP to connect', E_USER_WARNING);
			return false;
		}
		$_SERVER_ID = $this->_get_server_id($server_info);
		// Cache calls to the same server
		if (isset($this->_ssh_connected[$_SERVER_ID])) {
			return $this->_ssh_connected[$_SERVER_ID];
		}
		if ($this->_ssh_try_to_connect[$_SERVER_ID] >= $this->MAX_RECONNECTS) {
			return $this->_ssh_connected[$_SERVER_ID];
		}
		$ssh_user	= $server_info['ssh_user'] ? $server_info['ssh_user'] : 'root';
		$ssh_pswd	= $server_info['ssh_pswd'];
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// Try to connect to server with selected params
		// This avoid long timeouts if server not connected
		$fp = fsockopen($ssh_host, $ssh_port, $errno, $errstr, $this->CONNECT_TIMEOUT);
		if (!$fp) {
			$this->_ssh_try_to_connect[$_SERVER_ID]++;
			trigger_error('SSH: cannot connect to: '.$_SERVER_ID.'', E_USER_WARNING);
			return false;
		} else {
			fclose($fp);
		}
		// IMPORTANT: for best execution speed need to do: apt-get install php5-gmp php5-mcrypt php5-mhash
		if ($this->DRIVER == 'phpseclib') {

			$use_pswd = true;
			if (!empty($server_info['ssh_key_private'])) {
				$use_pswd = false;
			}
			if (!$use_pswd) {
				$key = new Crypt_RSA();
				if ($server_info['ssh_key_pswd']) {
					$key->setPassword($server_info['ssh_key_pswd']); // password for key
				}
				$key_result = $key->loadKey(file_get_contents($server_info['ssh_key_private']));
				if (!$key_result) {
					$this->_ssh_try_to_connect[$_SERVER_ID]++;
					trigger_error('SSH: wrong key: '.$server_info['ssh_key_private'].' for: '.$_SERVER_ID.'', E_USER_WARNING);
					return false;
				}
			}
			$con = new Net_SSH2($ssh_host);
			$auth_result = $con->login($ssh_user, $use_pswd ? $ssh_pswd : $key);

		} elseif ($this->DRIVER == 'pecl_ssh2') {

			$con = ssh2_connect($ssh_host, $ssh_port, null, array());
			// Try to authenticate
			$auth_result = false;
			if ($con) {
				if (!empty($server_info['ssh_key_public']) && !empty($server_info['ssh_key_private'])) {
					// using public key
					$auth_result = ssh2_auth_pubkey_file($con, $ssh_user, $server_info['ssh_key_public'], $server_info['ssh_key_private']);
				} else {
					// using plain password
					$auth_result = ssh2_auth_password($con, $ssh_user, $ssh_pswd);
				}
			}

		}
		if (DEBUG_MODE) {
			$this->_debug['connect_time'] = microtime(true) - $time_start;
		}
		if (!$con) {
			$this->_ssh_try_to_connect[$_SERVER_ID]++;
			trigger_error('SSH: cannot connect to: '.$_SERVER_ID, E_USER_WARNING);
			return false;
		}
		if ($auth_result) {
			$this->_ssh_connected[$_SERVER_ID] = $con;
			$this->_log($server_info, __FUNCTION__, 'user: '.$ssh_user.', auth successful');
			return $con;
		} else {
			trigger_error('SSH: auth on: '.$ssh_host.':'.$ssh_port.' failed for '.($this->AUTH_TYPE == 'pubkey' ? 'pubkey: '.$server_info['pubkey_path'] : 'user: '.$ssh_user.''), E_USER_WARNING);
		}
		return false;
	}

	/**
	* SFTP subsystem for phpseclib
	*/
	function _init_sftp_phpseclib ($server_info = array()) {
		if (!$this->DRIVER == 'phpseclib') {
			return false;
		}
		if (!$this->_INIT_OK || !$server_info) {
			return false;
		}
		$ssh_host	= $server_info['base_ip'] ? $server_info['base_ip'] : $server_info['ssh_host'];
		$ssh_port	= $server_info['ssh_port'] ? $server_info['ssh_port'] : 22;
		if (!$ssh_host) {
			trigger_error('SSH: missing server IP to connect', E_USER_WARNING);
			return false;
		}
		$_SERVER_ID = $this->_get_server_id($server_info);
		// Cache calls to the same server
		if (isset($this->_sftp_connected[$_SERVER_ID])) {
			return $this->_sftp_connected[$_SERVER_ID];
		}
		if ($this->_sftp_try_to_connect[$_SERVER_ID] >= $this->MAX_RECONNECTS) {
			return $this->_sftp_connected[$_SERVER_ID];
		}
		$ssh_user	= $server_info['ssh_user'] ? $server_info['ssh_user'] : 'root';
		$ssh_pswd	= $server_info['ssh_pswd'];
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// Try to connect to server with selected params
		// This avoid long timeouts if server not connected
		$fp = fsockopen($ssh_host, $ssh_port, $errno, $errstr, $this->CONNECT_TIMEOUT);
		if (!$fp) {
			$this->_ssh_try_to_connect[$_SERVER_ID]++;
			trigger_error('SSH: cannot connect to: '.$_SERVER_ID, E_USER_WARNING);
			return false;
		} else {
			fclose($fp);
		}

		$use_pswd = true;
		if ($this->AUTH_TYPE == 'pubkey' && !empty($server_info['ssh_key_private'])) {
			$use_pswd = false;
		}
		if (!$use_pswd) {
			$key = new Crypt_RSA();
			if ($server_info['ssh_key_pswd']) {
				$key->setPassword($server_info['ssh_key_pswd']); // password for key
			}
			$key_result = $key->loadKey(file_get_contents($server_info['ssh_key_private']));
			if (!$key_result) {
				$this->_ssh_try_to_connect[$_SERVER_ID]++;
				trigger_error('SSH: wrong key: '.$server_info['ssh_key_private'].' for: '.$_SERVER_ID.'', E_USER_WARNING);
				return false;
			}
		}

		require_once('Net/SFTP.php');

		$con = new Net_SFTP($ssh_host);
		$auth_result = $con->login($ssh_user, $use_pswd ? $ssh_pswd : $key);

		if (DEBUG_MODE) {
			$this->_debug['connect_time'] += microtime(true) - $time_start;
		}
		if (!$con) {
			$this->_sftp_try_to_connect[$_SERVER_ID]++;
			trigger_error('SSH: cannot connect to: '.$_SERVER_ID, E_USER_WARNING);
			return false;
		}
		if ($auth_result) {
			$this->_sftp_connected[$_SERVER_ID] = $con;
			$this->_log($server_info, __FUNCTION__, 'user: '.$ssh_user.', auth successful');
			return $con;
		} else {
			trigger_error('SSH: auth on '.$ssh_host.':'.$ssh_port.' failed for '.($this->AUTH_TYPE == 'pubkey' ? 'pubkey: '.$server_info['pubkey_path'] : 'user: '.$ssh_user.''), E_USER_WARNING);
		}
		return false;
	}

	/**
	* Executes remote command on given shell and returns result
	*/
	function exec ($server_info = array(), $cmd = '') {
		if (!$this->_INIT_OK || !$cmd || !$server_info) {
			return false;
		}
		$con = $this->connect($server_info);
		if (!$con) {
			return false;
		}
		// Execute several commands per one call ($cmd as array)
		if (is_array($cmd)) {
			$result = array();
			foreach ((array)$cmd as $k => $v) {
				$result[$k] = $this->exec($server_info, $v);
			}
			return $result;
		}
		$data = false;
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// execute a command
		if ($this->DRIVER == 'phpseclib') {

			$data = $con->exec($cmd);
			if (false === $data) {
				trigger_error('SSH: failed to execute remote command', E_USER_WARNING);
				return false;
			}

		} elseif ($this->DRIVER == 'pecl_ssh2') {

			if (!($stream = ssh2_exec($con, $cmd, false))) {
				trigger_error('SSH: failed to execute remote command', E_USER_WARNING);
				return false;
			} else {
				// collect returning data from command
				stream_set_blocking($stream, true);
				$data = '';
				while ($buf = fgets($stream)) {
					$data .= $buf;
				}
				fclose($stream);
			}

		}
		if (DEBUG_MODE) {
			$exec_time = microtime(true) - $time_start;
			$debug_info .= '<b>'.common()->_format_time_value($exec_time)." sec</b>,".PHP_EOL;
			$debug_info .= "func: <b>".__FUNCTION__."</b>, server: ".$server_info["base_ip"].",".PHP_EOL;
			$debug_info .= "cmd: \"<b style='color:blue;'>".$cmd."</b>\" ".($this->LOG_FULL_EXEC ? ", <b>response</b>:<br />\n <pre><small>".trim($data)."</small></pre>\n" : "")."<br />";
			$this->_debug["exec"][] = $debug_info;
			$this->_debug["time_sum"] += $exec_time;
		}
		$this->_log($server_info, __FUNCTION__, $cmd);
		return $data;
	}
	
	/**
	* Remote shell exec
	*/
	function shell_exec ($server_info = array(), $cmd = "") {
			
		if (!$this->_INIT_OK || !$cmd || !$server_info) {
			return false;
		}
		if (!($con = $this->connect($server_info))) {
			return false;
		}
		// Execute several commands per one call ($cmd as array)
		if (is_array($cmd)) {
			$result = array();
			foreach ((array)$cmd as $k => $v) {
				$result[$k] = $this->shell_exec($server_info, $v);
			}
			return $result;
		}
		$data = false;
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// execute a command
		if ($this->DRIVER == "phpseclib") {

			// Really not supported by lib, but should work as wrapper for exec
			$data = $con->exec($cmd);
			if (false === $data) {
				trigger_error("SSH: failed to execute remote command", E_USER_WARNING);
				return false;
			}

		} elseif ($this->DRIVER == "pecl_ssh2") {

			if (!isset($this->shell)) {
				$this->shell = ssh2_shell($con, 'xterm');
			}
		
			if (!(fwrite($this->shell, $cmd.PHP_EOL))) {
				trigger_error("SSH: failed to execute remote command", E_USER_WARNING);
				return false;
			} else {
				// collect returning data from command
				stream_set_blocking($this->shell, true);
				$data = "";
				while ($buf = fgets($this->shell)) {
					$data .= $buf;
				}
				fclose($this->shell);
			}
		}

		if (DEBUG_MODE) {
			$exec_time = microtime(true) - $time_start;
			$debug_info .= "<b>".common()->_format_time_value($exec_time)." sec</b>,\n";
			$debug_info .= "func: <b>".__FUNCTION__."</b>, server: ".$server_info["base_ip"].",\n";
			$debug_info .= "cmd: \"<b style='color:blue;'>".$cmd."</b>\" ".($this->LOG_FULL_EXEC ? ", <b>response</b>:<br />\n <pre><small>".trim($data)."</small></pre>\n" : "")."<br />";
			$this->_debug["exec"][] = $debug_info;
			$this->_debug["time_sum"] += $exec_time;
		}
		$this->_log($server_info, __FUNCTION__, $cmd);
		return $data;

	}

	/**
	* Read remote file
	*/
	function read_file ($server_info = array(), $remote_file = "", $local_file = "") {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $remote_file, $local_file);
	}

	/**
	* Write local file into remote file
	*/
	function write_file ($server_info = array(), $local_file = "", $remote_file = "") {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $local_file, $remote_file);
	}

	/**
	* Write string into remote file
	*/
	function write_string ($server_info = array(), $string = "", $remote_file = "") {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $string, $remote_file);
	}

	/**
	* Check if file exists remotely
	*/
	function file_exists($server_info = array(), $path = "") {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $path);
	}

	/**
	* Get selected file info
	*/
	function file_info ($server_info = array(), $path = "") {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $path);
	}

	/**
	* Resolve full path for the given file, dir or link
	*/
	function realpath($server_info = array(), $path = "") {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $path);
	}

	/**
	* Scan remote dir and return array of files details
	*/
	function scan_dir ($server_info = array(), $start_dir = "", $pattern_include = "", $pattern_exclude = "", $level = 0, $single_file = "") {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $start_dir, $pattern_include, $pattern_exclude, $level, $single_file);
	}

	/**
	* Alias for the mkdir_m
	*/
	function mkdir($server_info = array(), $dir_name = "", $dir_mode = 755, $create_index_htmls = 0, $start_folder = "") {
		return $this->mkdir_m($server_info, $dir_name, $dir_mode, $create_index_htmls, $start_folder);
	}

	/**
	* Create multiple dirs at one time (eg. mkdir_m("some_dir1/some_dir2/some_dir3"))
	*/
	function mkdir_m($server_info = array(), $dir_name = "", $dir_mode = 755, $create_index_htmls/*!not implemented here!*/ = 0, $start_folder = "/") {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $dir_name, $dir_mode, $create_index_htmls, $start_folder);
	}

	/**
	* Remove remote dir
	*/
	function rmdir($server_info = array(), $path = "") {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $path);
	}

	/**
	* Unlink remote file or link
	*/
	function unlink($server_info = array(), $path = "") {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $path);
	}

	/**
	* Chmod remote file
	*/
	function chmod($server_info = array(), $path = "", $new_mode = null, $recursively = false) {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $path, $new_mode, $recursively);
	}

	/**
	* Chown remote file
	*/
	function chown($server_info = array(), $path = "", $new_owner = "", $new_group = "", $recursively = false) {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $path, $new_owner, $new_group, $recursively);
	}

	/**
	* Rename remote file, dir or link
	*/
	function rename($server_info = array(), $old_name = "", $new_name = "") {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $old_name, $new_name);
	}

	/**
	* Copy remote dir structure into local one (bulk method)
	*/
	function download_dir ($server_info = array(), $remote_dir = "", $local_dir = "", $pattern_include = "", $pattern_exclude = "", $level = null) {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $remote_dir, $local_dir, $pattern_include, $pattern_exclude, $level);
	}

	/**
	* Copy local dir structure into remote one (bulk method)
	*/
	function upload_dir ($server_info = array(), $local_dir = '', $remote_dir = '', $pattern_include = '', $pattern_exclude = '', $level = null) {
		$f = __FUNCTION__; return _class('ssh_files', 'classes/ssh/')->$f($server_info, $local_dir, $remote_dir, $pattern_include, $pattern_exclude, $level);
	}

	/**
	* Recursively scanning directory structure (including subdirectories) //
	*/
	function _skip_by_pattern ($path = "", $_type = "f", $pattern_include = "", $pattern_exclude = "") {
		if (!$path) {
			return false;
		}
		if (!$pattern_include && !$pattern_exclude) {
			return false;
		}
		if (!$type || !in_array($type, "f","d","l")) {
			return false;
		}
		$_path_clean = trim(str_replace("//", "/", str_replace("\\", "/", $path)));
		// Include files only if they match the mask
		if ($_type == "d") {
			$_index = 0;
			$_path_clean	= rtrim($_path_clean, "/");
		} elseif ($_type == "f") {
			$_index = 1;
		} elseif ($_type == "l") {
			$_index = 2;
		}
		if (is_array($pattern_include)) {
			$pattern_include = $pattern_include[$_index];
		}
		if (is_array($pattern_include)) {
			$pattern_exclude = $pattern_exclude[$_index];
		}
		if (!empty($pattern_include) && is_string($pattern_include)) {
			// Matching file type (pattern like "-d")
			if ($pattern_include{0} == "-") {
				if ($_type != $pattern_include{1}) {
					return true;
				}
			}
			// Regex searching
			if (!preg_match($pattern_include."ims", $_path_clean)) {
				return true;
			}
		}
		// Exclude files from list by mask
		if (!empty($pattern_exclude) && is_string($pattern_exclude)) {
			// Matching file type (pattern like "-d")
			if ($pattern_exclude{0} == "-") {
				if ($_type == $pattern_exclude{1}) {
					return true;
				}
			}
			// Regex searching
			if (preg_match($pattern_exclude."ims", $_path_clean)) {
				return true;
			}
		}
		return false;
	}

	/**
	* Compress files to tar archive (local)
	*/
	function _local_make_tar ($files_list = array(), $archive_path = '') {
		$f = __FUNCTION__; return _class('ssh_tar', 'classes/ssh/')->$f($files_list, $archive_path);
	}

	/**
	* Extract files from tar archive (local)
	*/
	function _local_extract_tar ($archive_path = '', $extract_path = '') {
		$f = __FUNCTION__; return _class('ssh_tar', 'classes/ssh/')->$f($files_list, $archive_path);
	}

	/**
	* Clean path from SFTP prefix (usually for pretty output for user)
	*/
	function clean_path ($path = '') {
		$pattern = '#^(ssh2\.sftp://Resource id \#[0-9]+)#ims';
		if (is_array($path)) {
			// Get current resource string
			$cur = current($path);
			if (is_array($cur)) {
				$cur = current($cur);
			}
			preg_match($pattern, $cur, $m);
			return str_replace($m[1], '', $path);
		}
		return preg_replace($pattern, '', $path);
	}

	/**
	* Prepare path, Prevent some hacks and misuses
	*/
	function _prepare_path ($path = '') {
		if (is_array($path)) {
			foreach ((array)$path as $k => $v) {
				$path[$k] = $this->_prepare_path($v);
			}
			return $path;
		}
		$bad_chars = array("`", "\"", "'", "..", "~", " ", "\t", "\r", "\n", "|", "<", ">", "&");
		$result = str_replace($bad_chars, "", rtrim(str_replace(array("\\", "//", "///"), "/", trim($path)), "/"));
		return $result ? $result : '/';
	}

	/**
	* Prepare text for using inside (grep '%text%') or similar commands
	*/
	function _prepare_text ($text = '') {
		if (is_array($text)) {
			foreach ((array)$path as $k => $v) {
				$text[$k] = $this->_prepare_text($v);
			}
			return $text;
		}
		$text = preg_replace('/[\x0A-\xFF]/i', '', $text);
		$replace = array(
			"\\"	=> "\\\\",
			"`"		=> "\\`",
			"\""	=> "\\\"",
			"'"		=> "\\'",
			"|"		=> "\\|",
			"<"		=> "\\<",
			">"		=> "\\>",
			"&"		=> "\\&",
			"\t"	=> "",
			"\r"	=> "",
			"\n"	=> "",
		);
		$text = str_replace(array_keys($replace), array_values($replace), $text);
		return $text;
	}

	/**
	* Log internal action (Currently we store here successful actions, not errors for debug)
	*/
	function _log ($server_info = array(), $action = '', $comment = '') {
		if (!$this->LOG_ACTIONS) {
			return false;
		}
		$SERVER_ID = $this->_get_server_id($server_info);
		$sql_array = array(
			'server_id'		=> _es($SERVER_ID),
			'microtime'		=> _es(str_replace(',', '.', microtime(true))),
			'init_type'		=> _es(MAIN_TYPE),
			'action'		=> _es($action),
			'comment'		=> _es($comment),
			'get_object'	=> _es($_GET['object']),
			'get_action'	=> _es($_GET['action']),
			'user_id'		=> intval($_SESSION['user_id']),
			'user_group'	=> intval($_SESSION['user_group']),
			'ip'			=> _es(common()->get_ip()),
		);
		return db()->INSERT('log_ssh_action', $sql_array);
	}

	/**
	* Convert string permission output to numerical
	*/
	function _perm_str2num ($perm = '') {
		$perm_len = strlen(trim($perm));
		if ($perm_len > 10 && $perm_len < 9) {
			return false;
		}
		if ($perm_len == 10) {
			$perm = substr($perm, 1);
		}
		// Compatibility with sticky bit, setuid, setgid (http://en.wikipedia.org/wiki/File_system_permissions)
		$perm = str_replace(array('s', 'S', 't', 'T'), array('x', '-', 'x', '-'), $perm);

		foreach ((array)str_split($perm) as $k => $v) {
			if ($v == '-') {
				continue;
			}
			// Owner
			if ($k == 0) {	$own += 4;	}
			if ($k == 1) {	$own += 2;	}
			if ($k == 2) {	$own += 1;	}
			// Group
			if ($k == 3) {	$grp += 4;	}
			if ($k == 4) {	$grp += 2;	}
			if ($k == 5) {	$grp += 1;	}
			// Others
			if ($k == 6) {	$oth += 4;	}
			if ($k == 7) {	$oth += 2;	}
			if ($k == 8) {	$oth += 1;	}
		}
		return '0'. $own. ''. $grp. ''. $oth;
	}
}
