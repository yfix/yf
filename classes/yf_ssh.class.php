<?php

/**
* SSH Client (based on SSH2 PHP extension)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_ssh {

	/** @var string @conf_skip array("phpseclib","pecl_ssh2","auto") */
	var $DRIVER				= "phpseclib";
	/** @var bool @conf_skip */
	var $_INIT_OK			= false;
	/** @var string @conf_skip */
	var $_TMP_DIR			= "uploads/tmp";
	/** @var enum('password','pubkey') */
	var $AUTH_TYPE			= "password";
	/** @var bool Save actions log or not */
	var $LOG_ACTIONS		= false;
	/** @var bool Use archiving for mass actions */
	var $MASS_USE_ARCHIVES	= true;
	/** @var string Path to the tar archiver in console */
	var $TAR_PATH			= "";
	/** @var string Path to the gzip console programm */
	var $GZIP_PATH			= "";
	/** @var bool */
	var $USE_GZIP			= true;
	/** @var bool */
	var $CONNECT_TIMEOUT	= 5;
	/** @var bool */
	var $MAX_RECONNECTS		= 1;
	/** @var bool */
	var $LOG_FULL_EXEC		= 0;

	/**
	* Catch missing method call
	*/
    function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* Framework constructor
	*/
	function _init () {
		if (!$this->DRIVER) {
			$this->DRIVER = "phpseclib";
		}
		$test_phpseclib_path = PF_PATH."libs/phpseclib/Net/SSH2.php";
		if ($this->DRIVER == "phpseclib" && !file_exists($test_phpseclib_path)) {
			trigger_error("phpseclib Net_SSH2 not found", E_USER_WARNING);
			return false;
		} elseif ($this->DRIVER == "pecl_ssh2" && !function_exists("ssh2_connect")) {
			trigger_error("function ssh2_connect doesn't exist", E_USER_WARNING);
			return false;
		} else {
			$this->_INIT_OK = true;
		}

		if ($this->_INIT_OK && $this->DRIVER == "phpseclib") {
			set_include_path (PF_PATH."libs/phpseclib/". PATH_SEPARATOR. get_include_path());
			require_once('Crypt/RSA.php');
			require_once('Net/SSH2.php');
		}
	}

	/**
	* Return internal SERVER_ID (usually "ssh_host:ssh_port")
	*/
	function _get_server_id ($server_info = array()) {
		if (!$server_info) {
			return false;
		}
		$ssh_host	= $server_info["base_ip"] ? $server_info["base_ip"] : $server_info["ssh_host"];
		$ssh_port	= $server_info["ssh_port"] ? $server_info["ssh_port"] : 22;
		if (!$ssh_host) {
			return false;
		}
		return $ssh_host.":".$ssh_port;
	}

	/**
	* Return remote OS string
	*/
	function _get_remote_os ($server_info = array()) {
		$_SERVER_ID = $this->_get_server_id($server_info);
		if (isset($this->_ssh_cache_os[$_SERVER_ID])) {
			return $this->_ssh_cache_os[$_SERVER_ID];
		}
		$result = strtoupper(trim($this->exec($server_info, "uname")));
		$this->_ssh_cache_os[$_SERVER_ID] = $result;
		return $result;
	}

	/**
	* Connect to the remote server
	*
	* @example
	*
	* $server_info = array(
	*	"ssh_host"	=> "192.168.1.2",
	*	"ssh_user"	=> "root",
	*	"ssh_pswd"	=> "111111",
	* );
	*/
	function connect ($server_info = array()) {
		if (!$this->_INIT_OK || !$server_info) {
			return false;
		}
		$ssh_host	= $server_info["base_ip"] ? $server_info["base_ip"] : $server_info["ssh_host"];
		$ssh_port	= $server_info["ssh_port"] ? $server_info["ssh_port"] : 22;
		if (!$ssh_host) {
			trigger_error("SSH: missing server IP to connect", E_USER_WARNING);
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
		$ssh_user	= $server_info["ssh_user"] ? $server_info["ssh_user"] : "root";
		$ssh_pswd	= $server_info["ssh_pswd"];
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// Try to connect to server with selected params
		// This avoid long timeouts if server not connected
		$fp = fsockopen($ssh_host, $ssh_port, $errno, $errstr, $this->CONNECT_TIMEOUT);
		if (!$fp) {
			$this->_ssh_try_to_connect[$_SERVER_ID]++;
			trigger_error("SSH: cannot connect to \"".$_SERVER_ID."\"", E_USER_WARNING);
			return false;
		} else {
			fclose($fp);
		}
		// IMPORTANT: for best execution speed need to do: apt-get install php5-gmp php5-mcrypt php5-mhash
		if ($this->DRIVER == "phpseclib") {

			$use_pswd = true;
			if (!empty($server_info["ssh_key_private"])) {
				$use_pswd = false;
			}
			if (!$use_pswd) {
				$key = new Crypt_RSA();
				if ($server_info["ssh_key_pswd"]) {
					$key->setPassword($server_info["ssh_key_pswd"]); // password for key
				}
				$key_result = $key->loadKey(file_get_contents($server_info["ssh_key_private"]));
				if (!$key_result) {
					$this->_ssh_try_to_connect[$_SERVER_ID]++;
					trigger_error("SSH: wrong key \"".$server_info["ssh_key_private"]."\" for \"".$_SERVER_ID."\"", E_USER_WARNING);
					return false;
				}
			}
			$con = new Net_SSH2($ssh_host);
			$auth_result = $con->login($ssh_user, $use_pswd ? $ssh_pswd : $key);

		} elseif ($this->DRIVER == "pecl_ssh2") {

			$con = ssh2_connect($ssh_host, $ssh_port, null, array());
			// Try to authenticate
			$auth_result = false;
			if ($con) {
				if (!empty($server_info["ssh_key_public"]) && !empty($server_info["ssh_key_private"])) {
					// using public key
					$auth_result = ssh2_auth_pubkey_file($con, $ssh_user, $server_info["ssh_key_public"], $server_info["ssh_key_private"]);
				} else {
					// using plain password
					$auth_result = ssh2_auth_password($con, $ssh_user, $ssh_pswd);
				}
			}

		}
		if (DEBUG_MODE) {
			$this->_debug["connect_time"] = microtime(true) - $time_start;
		}
		if (!$con) {
			$this->_ssh_try_to_connect[$_SERVER_ID]++;
			trigger_error("SSH: cannot connect to \"".$_SERVER_ID."\"", E_USER_WARNING);
			return false;
		}
		if ($auth_result) {
			$this->_ssh_connected[$_SERVER_ID] = $con;
			$this->_log($server_info, __FUNCTION__, "user: '".$ssh_user."', auth successful");
			return $con;
		} else {
			trigger_error("SSH: auth on \"".$ssh_host.":".$ssh_port."\" failed for ".($this->AUTH_TYPE == "pubkey" ? "pubkey: ".$server_info["pubkey_path"] : "user \"".$ssh_user."\""), E_USER_WARNING);
		}
		return false;
	}

	/**
	* SFTP subsystem for phpseclib
	*/
	function _init_sftp_phpseclib ($server_info = array()) {
		if (!$this->DRIVER == "phpseclib") {
			return false;
		}
		if (!$this->_INIT_OK || !$server_info) {
			return false;
		}
		$ssh_host	= $server_info["base_ip"] ? $server_info["base_ip"] : $server_info["ssh_host"];
		$ssh_port	= $server_info["ssh_port"] ? $server_info["ssh_port"] : 22;
		if (!$ssh_host) {
			trigger_error("SSH: missing server IP to connect", E_USER_WARNING);
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
		$ssh_user	= $server_info["ssh_user"] ? $server_info["ssh_user"] : "root";
		$ssh_pswd	= $server_info["ssh_pswd"];
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// Try to connect to server with selected params
		// This avoid long timeouts if server not connected
		$fp = fsockopen($ssh_host, $ssh_port, $errno, $errstr, $this->CONNECT_TIMEOUT);
		if (!$fp) {
			$this->_ssh_try_to_connect[$_SERVER_ID]++;
			trigger_error("SSH: cannot connect to \"".$_SERVER_ID."\"", E_USER_WARNING);
			return false;
		} else {
			fclose($fp);
		}

		$use_pswd = true;
		if ($this->AUTH_TYPE == "pubkey" && !empty($server_info["ssh_key_private"])) {
			$use_pswd = false;
		}
		if (!$use_pswd) {
			$key = new Crypt_RSA();
			if ($server_info["ssh_key_pswd"]) {
				$key->setPassword($server_info["ssh_key_pswd"]); // password for key
			}
			$key_result = $key->loadKey(file_get_contents($server_info["ssh_key_private"]));
			if (!$key_result) {
				$this->_ssh_try_to_connect[$_SERVER_ID]++;
				trigger_error("SSH: wrong key \"".$server_info["ssh_key_private"]."\" for \"".$_SERVER_ID."\"", E_USER_WARNING);
				return false;
			}
		}

		require_once('Net/SFTP.php');

		$con = new Net_SFTP($ssh_host);
		$auth_result = $con->login($ssh_user, $use_pswd ? $ssh_pswd : $key);

		if (DEBUG_MODE) {
			$this->_debug["connect_time"] += microtime(true) - $time_start;
		}
		if (!$con) {
			$this->_sftp_try_to_connect[$_SERVER_ID]++;
			trigger_error("SSH: cannot connect to \"".$_SERVER_ID."\"", E_USER_WARNING);
			return false;
		}
		if ($auth_result) {
			$this->_sftp_connected[$_SERVER_ID] = $con;
			$this->_log($server_info, __FUNCTION__, "user: '".$ssh_user."', auth successful");
			return $con;
		} else {
			trigger_error("SSH: auth on \"".$ssh_host.":".$ssh_port."\" failed for ".($this->AUTH_TYPE == "pubkey" ? "pubkey: ".$server_info["pubkey_path"] : "user \"".$ssh_user."\""), E_USER_WARNING);
		}
		return false;
	}

	/**
	* Executes remote command on given shell and returns result
	*/
	function exec ($server_info = array(), $cmd = "") {
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
		if ($this->DRIVER == "phpseclib") {

			$data = $con->exec($cmd);
			if (false === $data) {
				trigger_error("SSH: failed to execute remote command", E_USER_WARNING);
				return false;
			}

		} elseif ($this->DRIVER == "pecl_ssh2") {

			if (!($stream = ssh2_exec($con, $cmd, false))) {
				trigger_error("SSH: failed to execute remote command", E_USER_WARNING);
				return false;
			} else {
				// collect returning data from command
				stream_set_blocking($stream, true);
				$data = "";
				while ($buf = fgets($stream)) {
			    	$data .= $buf;
				}
				fclose($stream);
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
	*
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
		$local_file		= trim($local_file);
		$remote_file	= $this->_prepare_path($remote_file);
		if (!$this->_INIT_OK || !$server_info || !strlen($remote_file)) {
			return false;
		}
		if (!$this->file_exists($server_info, $remote_file)) {
			trigger_error("SSH: ".__FUNCTION__.": remote file \"".$remote_file."\" does not exist", E_USER_WARNING);
			return false;
		}
		// When local file is empty we will return contents of the remote file as a string
		if ($local_file && !file_exists(dirname($local_file))) {
			_mkdir_m(dirname($local_file));
		}
		if ($this->DRIVER == "phpseclib") {

			if (!($con = $this->_init_sftp_phpseclib($server_info))) {
				return false;
			}
			if (DEBUG_MODE) {
				$time_start = microtime(true);
			}
			$result = $con->get($remote_file, $local_file ? $local_file : false);

		} elseif ($this->DRIVER == "pecl_ssh2") {

			if (!($con = $this->connect($server_info))) {
				return false;
			}
			if (DEBUG_MODE) {
				$time_start = microtime(true);
			}
			// Go!
			$sftp = ssh2_sftp($con);
			if ($sftp) {
				$result = file_get_contents("ssh2.sftp://".$sftp.$remote_file);
			}
			if ($local_file) {
				file_put_contents($local_file, $result);
			}

		}
		if (DEBUG_MODE) {
			$exec_time = microtime(true) - $time_start;
			$debug_info .= "<b>".common()->_format_time_value($exec_time)." sec</b>,\n";
			$debug_info .= "func: <b>".__FUNCTION__."</b>, server: ".$server_info["base_ip"].",\n";
			$debug_info .= "remote_file: \"<b style='color:blue;'>".$remote_file."</b>\"<br />";
			$debug_info .= "local_file: \"<b style='color:blue;'>".$local_file."</b>\"<br />";
			$this->_debug["exec"][] = $debug_info;
			$this->_debug["time_sum"] += $exec_time;
		}
		$this->_log($server_info, __FUNCTION__, "remote_file: '".$remote_file."', local_file: '".$local_file."'");
		return $result;
	}

	/**
	* Write local file into remote file
	*/
	function write_file ($server_info = array(), $local_file = "", $remote_file = "") {
		$local_file		= trim($local_file);
		$remote_file	= $this->_prepare_path($remote_file);
		if (!$this->_INIT_OK || !$server_info || !strlen($local_file) || !strlen($remote_file)) {
			return false;
		}
		if (!file_exists($local_file)) {
			trigger_error("SSH: ".__FUNCTION__.": local file \"".$local_file."\" does not exist", E_USER_WARNING);
			return false;
		}
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// Check if remote folder exists and create it if not done yet
		$this->mkdir($server_info, dirname($remote_file));
		if (!$this->file_exists($server_info, dirname($remote_file))) {
			trigger_error("SSH: ".__FUNCTION__.": cannot create remote dir \"".dirname($remote_file)."\"", E_USER_WARNING);
			return false;
		}
		if ($this->DRIVER == "phpseclib") {

			if (!($con = $this->_init_sftp_phpseclib($server_info))) {
				return false;
			}
			$result = $con->put($remote_file, file_get_contents($local_file));

		} elseif ($this->DRIVER == "pecl_ssh2") {

			if (!($con = $this->connect($server_info))) {
				return false;
			}
			// Go!
			$sftp = ssh2_sftp($con);
			if ($sftp) {
				$sftp_stream = fopen("ssh2.sftp://".$sftp.$remote_file, 'w');
				$result = fwrite($sftp_stream, file_get_contents($local_file));
				fclose($sftp_stream);
			}

		}
		// Go!
		if (DEBUG_MODE) {
			$exec_time = microtime(true) - $time_start;
			$debug_info .= "<b>".common()->_format_time_value($exec_time)." sec</b>,\n";
			$debug_info .= "func: <b>".__FUNCTION__."</b>, server: ".$server_info["base_ip"].",\n";
			$debug_info .= "local_file: \"<b style='color:blue;'>".$local_file."</b>\"<br />";
			$debug_info .= "remote_file: \"<b style='color:blue;'>".$remote_file."</b>\"<br />";
			$this->_debug["exec"][] = $debug_info;
			$this->_debug["time_sum"] += $exec_time;
		}
		$this->_log($server_info, __FUNCTION__, "local_file: '".$local_file."', remote_file: '".$remote_file."'");
		return $result;
	}

	/**
	* Write string into remote file
	*/
	function write_string ($server_info = array(), $string = "", $remote_file = "") {
		$remote_file	= $this->_prepare_path($remote_file);
		if (!$this->_INIT_OK || !$server_info || !$string || (!strlen($remote_file) && !is_array($string))) {
			return false;
		}
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		if ($this->DRIVER == "phpseclib") {
		
			if (!($sftp = $this->_init_sftp_phpseclib($server_info))) {
				return false;
			}
		
		} elseif ($this->DRIVER == "pecl_ssh2") {
		
			if (!($con = $this->connect($server_info))) {
				return false;
			}
			$sftp = ssh2_sftp($con);
		
		}
		// Second argument as array
		if (is_array($string) && $sftp) {
			$completed = false;
			// Try to create temporary files and upload them with single archive
			// and then extract on the remote server (preferred method)
			if ($this->MASS_USE_ARCHIVES) {
				$_remote_dir = "/tmp/__ssh_write_string__".abs(crc32(microtime(true)));
				$this->mkdir_m($server_info, $_remote_dir);
				// Prepare local temporary folder for storing in tar archive
				$_local_tmp_dir = $this->_prepare_path(realpath(PROJECT_PATH)."/". $this->_TMP_DIR). "/". md5(microtime(true)."__write_string");
				_mkdir_m(dirname($_local_tmp_dir));
				// Fill temporary files with strings
				foreach ((array)$string as $_remote_file => $_string) {
					$_tmp_path = $this->_prepare_path($_local_tmp_dir. "/". $_remote_file);
					if (!file_exists(dirname($_tmp_path))) {
						_mkdir_m(dirname($_tmp_path));
					}
					file_put_contents($_tmp_path, $_string);
				}
				// Get list of new files
				$cutoff_len = strlen($_local_tmp_dir);
				$dir_contents = _class("dir")->scan_dir($_local_tmp_dir, 1);
				if ($dir_contents) {
					$archive_path = $this->_local_make_tar($dir_contents);
				}
				// Go with uploading and extracting
				if ($archive_path && file_exists($archive_path)) {
					$remote_archive_path = $_remote_dir. "/". basename($archive_path);
					$this->write_file($server_info, $archive_path, $remote_archive_path);
					if ($this->file_exists($server_info, $remote_archive_path)) {
						$first_local_dir = trim(substr($_local_tmp_dir, 0, strpos($_local_tmp_dir, "/", 1)), "/");
						$_cwd = trim($this->exec($server_info, "pwd"));
		    
						$_tmp_dir = $this->_prepare_path($_remote_dir. "/". str_replace(array(".tar", ".gz", ".bz"), "", basename($archive_path)));

						$this->mkdir_m($server_info, $_tmp_dir);
		    
						$cmd = "cd '".$_tmp_dir."';"
							." tar --extract ".($this->USE_GZIP ? " --ungzip" : "")." -p -f '".$remote_archive_path."';"
							." unlink '".$remote_archive_path."';";
						$this->exec($server_info, $cmd);

						$cmd = ""
							.(" mv ".$this->_prepare_path($_tmp_dir."/".(OS_WINDOWS ? substr($_local_tmp_dir, 2) : $_local_tmp_dir))."/* '".$_remote_dir."';")
							.($first_local_dir ? " rm -rf '".$this->_prepare_path($_tmp_dir)."';" : "")
// TODO: convert cp ...* to the "find . | xargs cp ..." to avoid errors like "argument list too long"
							.(" cp -r ".$_remote_dir."/* /;")
							.(" rm -rf ".$_remote_dir.";")
							." unlink '".$_remote_dir."';"
							." cd '".$_cwd."'";
						$this->exec($server_info, $cmd);
		    
						$completed = true;
					}
					unlink($archive_path);
				}
				_class("dir")->delete_dir($_local_tmp_dir, 1);
			}
			if (!$completed) {
				foreach ((array)$string as $_remote_file => $_string) {
					// Check if remote folder exists and create it if not done yet
					$this->mkdir($server_info, dirname($_remote_file));
					if (!$this->file_exists($server_info, dirname($_remote_file))) {
						trigger_error("SSH: ".__FUNCTION__.": cannot create remote dir \"".dirname($_remote_file)."\"", E_USER_WARNING);
						continue;
					}
					if ($this->DRIVER == "phpseclib") {

						$result = $con->put($remote_file, $_string);

					} elseif ($this->DRIVER == "pecl_ssh2") {

						$sftp_stream = fopen("ssh2.sftp://".$sftp.$_remote_file, 'w');
						$result = fwrite($sftp_stream, $_string);
						fclose($sftp_stream);

					}
				}
			}
			$this->_log($server_info, __FUNCTION__, "strlen: '".strlen($string)."', remote_file: '".$remote_file."'");
			return $result;
		}
		if (DEBUG_MODE) {
			$exec_time = microtime(true) - $time_start;
			$debug_info .= "<b>".common()->_format_time_value($exec_time)." sec</b>,\n";
			$debug_info .= "func: <b>".__FUNCTION__."</b>, server: ".$server_info["base_ip"].",\n";
			$debug_info .= "strlen: \"<b>".strlen($string)."</b>\"<br />";
			$debug_info .= "remote_file: \"<b style='color:blue;'>".$remote_file."</b>\"<br />";
			$this->_debug["exec"][] = $debug_info;
			$this->_debug["time_sum"] += $exec_time;
		}
		// Check if remote folder exists and create it if not done yet
		$this->mkdir($server_info, dirname($remote_file));
		if (!$this->file_exists($server_info, dirname($remote_file))) {
			trigger_error("SSH: ".__FUNCTION__.": cannot create remote dir \"".dirname($remote_file)."\"", E_USER_WARNING);
			return false;
		}
		// Go!
		if ($sftp) {
			if ($this->DRIVER == "phpseclib") {
		
				$result = $con->put($remote_file, $string);
		
			} elseif ($this->DRIVER == "pecl_ssh2") {
		
				$sftp_stream = fopen("ssh2.sftp://".$sftp.$remote_file, 'w');
				$result = fwrite($sftp_stream, $string);
				fclose($sftp_stream);
		
			}
		}
		$this->_log($server_info, __FUNCTION__, "strlen: '".strlen($string)."', remote_file: '".$remote_file."'");
		return $result;
	}

	/**
	* Check if file exists remotely
	*/
	function file_exists($server_info = array(), $path = "") {
		$path = $this->_prepare_path($path);
		if (strlen($path)) {
			$command = "echo \"if [ -e '".$path."' ]; then echo 1; else echo 0; fi\" | bash";
			$result = (bool)intval($this->exec($server_info, $command));
			$this->_log($server_info, __FUNCTION__, "path: '".$path."', result: ".(int)$result);
			return $result;
		}
		return false;
	}

	/**
	* Get selected file info
	*/
	function file_info ($server_info = array(), $path = "") {
		$path = $this->_prepare_path($path);
		if (!$this->_INIT_OK || !strlen($path) || !$server_info) {
			return false;
		}
		$result = $this->scan_dir($server_info, dirname($path), "", "", 0, basename($path));
		$result = current($result);
		$this->_log($server_info, __FUNCTION__, "path: '".$path."'");
		return $result;
	}

	/**
	* Resolve full path for the given file, dir or link
	*/
	function realpath($server_info = array(), $path = "") {
		return trim($this->exec($server_info, "realpath ".$path), "'`\"\t\n ");
	}

	/**
	* Scan remote dir and return array of files details
	*/
	function scan_dir ($server_info = array(), $start_dir = "", $pattern_include = "", $pattern_exclude = "", $level = 0, $single_file = "") {
		if (is_array($start_dir)) {
			$_merged_contents = array();
			foreach ((array)$start_dir as $_start_dir) {
				$_cur_contents = (array)$this->scan_dir ($server_info, $_start_dir, $pattern_include, $pattern_exclude, $level, $single_file);
				$_merged_contents = my_array_merge($_merged_contents, $_cur_contents);
			}
			return $_merged_contents;
		}
		if ($start_dir != "/") {
			$start_dir = $this->_prepare_path($start_dir);
		}
		if (!$this->_INIT_OK || !strlen($start_dir) || !$server_info) {
			return false;
		}
		if (strlen($single_file)) {
			$single_file = basename($this->_prepare_path($single_file));
		}
		$SERVER_OS = $this->_get_remote_os($server_info);
		if ($SERVER_OS == "LINUX") {
			$ls_cmd = "ls -flvH --time-style=long-iso";
		} elseif ($SERVER_OS == "FREEBSD") {
			$ls_cmd = "ls -flTH";
		} else {
			$ls_cmd = "ls -flH";
		}
		$tmp = $this->exec($server_info, $ls_cmd." ".$start_dir. ($single_file ? " | grep ".$single_file : ""));
		// Extract items from string
		$time_pattern = 
			"[0-9]{4}-[0-9]{2}-[0-9]{2}[\s]+[0-9]{2}\:[0-9]{2}"
			. "|"
			. "[a-z]{3}[\s]+[0-9]{1,2}[\s]+[0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}[\s]+[0-9]{4}"
			."";
		$pattern = "/([ldrwxst\-]{10})[\s]+([0-9]+)[\s]+(\w+)[\s]+(\w+)[\s]+([0-9]+)[\s]+(".$time_pattern.")[\s]+(.*)/i";
		preg_match_all($pattern, $tmp, $m);
		$files = array();
		foreach ((array)$m[0] as $id => $_matched_all) {
			$_name = trim($m[7][$id]);
			if ($_name == "." || $_name == "..") {
				continue;
			}
			$_perms	= trim($m[1][$id]);
			// Compatibility with sticky bit, setuid, setgid (http://en.wikipedia.org/wiki/File_system_permissions)
			$_perms = str_replace(array("s", "S", "t", "T"), array("x", "-", "x", "-"), $_perms);
			// could be: enum("f","d","l");
			$_type	= $_perms{0} == "d" ? "d" : ($_perms{0} == "l" ? "l" : "f");
			// Remove link target from name and place it in separate var
			$_link = "";
			if ($_type == "l") {
				list($_name, $_link) = explode("->", $_name);
				$_name = trim($_name);
				$_link = trim($_link);
				// Resolve links with relative paths to their absolute targets using "stat"
				if (false !== strpos($_link, "..")) {
					$_tmp_link = $this->realpath($server_info, ($start_dir != "/" ? $start_dir : "")."/".$_name);
					if ($_tmp_link) {
						$_link = $_tmp_link;
					} else {
						$_link = str_replace("..", "", trim($_link));
					}
				}
			}
			// Here full path to the item
			$item_name = ($start_dir != "/" ? $start_dir : "")."/".$_name;

			if ($this->_skip_by_pattern($item_name, $_type, $pattern_include, $pattern_exclude)) {
				continue;
			}
			$_user	= trim($m[3][$id]);
			$_group	= trim($m[4][$id]);
			$_size	= trim($m[5][$id]);
			$_date	= strtotime($m[6][$id]);
			$_mode	= $this->_perm_str2num($_perms);
			// We have dir here
			if ($_type == "d") {
				if (is_null($level) || $level > 0) {
					// Merge result
					foreach ((array)$this->scan_dir($server_info, $item_name, $pattern_include, $pattern_exclude, is_null($level) ? $level : $level - 1) as $_path => $_info) {
						$files[$_path] = $_info;
					}
				}
			}
			$files[($start_dir != "/" ? $start_dir : "")."/".$_name] = array(
				"name"	=> $_name,
				"type"	=> $_type,
				"perms"	=> $_perms,
				"mode"	=> $_mode,
				"user"	=> $_user,
				"group"	=> $_group,
				"size"	=> $_size,
				"date"	=> $_date,
				"link"	=> $_link,
			);
		}
		if (is_array($files)) {
			ksort($files);
		}
		$this->_log($server_info, __FUNCTION__, "start_dir: '".$start_dir."', level: ".(int)$level);
		return $files;
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
	* Alias for the mkdir_m
	*/
	function mkdir($server_info = array(), $dir_name = "", $dir_mode = 755, $create_index_htmls = 0, $start_folder = "") {
		return $this->mkdir_m($server_info, $dir_name, $dir_mode, $create_index_htmls, $start_folder);
	}

	/**
	* Create multiple dirs at one time (eg. mkdir_m("some_dir1/some_dir2/some_dir3"))
	* 
	* @access	public
	* @param	$dir_name			string
	* @param	$dir_mode			octal
	* @param	$create_index_htmls	bool
	* @param	$start_folder		string
	* @return	int		Status code
	*/
	function mkdir_m($server_info = array(), $dir_name = "", $dir_mode = 755, $create_index_htmls/*!not implemented here!*/ = 0, $start_folder = "/") {
		if (!$this->_INIT_OK || !$server_info) {
			return false;
		}
		if (is_array($dir_name)) {
			// Default start folder to look at
			if (!strlen($start_folder)) {
				$start_folder = "/";
			}
			$_cmd = array();
			foreach ((array)$dir_name as $_dir_name => $_dir_mode) {
				$_dir_name = $this->_prepare_path($_dir_name);
				if (!strlen($_dir_name)) {
					continue;
				}
				if (!is_numeric($_dir_mode)) {
					$_dir_mode = "";
				}
				// Default dir mode
				$_dir_mode = abs(intval($_dir_mode));
				if (empty($_dir_mode) || $_dir_mode > 777) {
					$_dir_mode = 755;
				}
				$_cmd[] = "mkdir -m ".$_dir_mode." -p ".$_dir_name;
			}
			$this->exec($server_info, implode(" ; ", (array)$_cmd));
			$this->_log($server_info, __FUNCTION__, "dir_name: ".print_r($dir_name, 1));
			return true;
		}
		$dir_name = $this->_prepare_path($dir_name);
		if (!strlen($dir_name)) {
			return false;
		}
		// Default start folder to look at
		if (!strlen($start_folder)) {
			$start_folder = "/";
		}
		// String dir mode
		if (is_string($dir_mode) && strlen($dir_mode) >= 9 && strlen($dir_mode) <= 10) {
			$dir_mode = (string)$this->_perm_str2num($dir_mode);
		}
		// Default dir mode
		$dir_mode = abs(intval($dir_mode));
		if (empty($dir_mode) || $dir_mode > 777) {
			$dir_mode = 755;
		}
		$this->exec($server_info, "mkdir -m ".$dir_mode." -p ".$dir_name);
		$this->_log($server_info, __FUNCTION__, "dir_name: '".$dir_name."', dir_mode: ".(int)$dir_mode);
		return true;
	}

	/**
	* Remove remote dir
	*/
	function rmdir($server_info = array(), $path = "") {
		$path = $this->_prepare_path($path);
		// Do not allow to change folders with less than 1 level deep from "/", 
		// for example: deny for "/var",
		// but allow for "/var/www"
		if (substr_count($path, "/") <= 1) {
			trigger_error("SSH: ".__FUNCTION__.": attempt to remove \"".$path."\" folder denied!", E_USER_WARNING);
			return false;
		}
		$this->_log($server_info, __FUNCTION__, "path: '".$path."'");
		return $this->exec($server_info, "rm -rf '".$path."'");
	}

	/**
	* Unlink remote file or link
	*/
	function unlink($server_info = array(), $path = "") {
		$path = $this->_prepare_path($path);
		$this->_log($server_info, __FUNCTION__, "path: '".$path."'");
		return $this->exec($server_info, "unlink '".$path."'");
	}

	/**
	* Chmod remote file
	*/
	function chmod($server_info = array(), $path = "", $new_mode = null, $recursively = false) {
		if (is_array($path)) {
			$_bulk_cmd = array();
			foreach ((array)$path as $_path => $_new_mode) {
				$_path = $this->_prepare_path($_path);
				if (substr_count($_path, "/") <= 1) {
					trigger_error("SSH: ".__FUNCTION__.": attempt to change \"".$_path."\" denied!", E_USER_WARNING);
					continue;
				}
				$_bulk_cmd[] .= "chmod ".($recursively ? "-R" : "")." ".$_new_mode." '".$_path."'";
			}
			$_bulk_cmd = implode(" | ", $_bulk_cmd);
			$this->_log($server_info, __FUNCTION__, "bulk cmd: ".$_bulk_cmd);
			return !empty($_bulk_cmd) ? $this->exec($server_info, $_bulk_cmd) : false;
		}
		$path = $this->_prepare_path($path);
		if (!strlen($path) || is_null($new_mode) || !is_numeric($new_mode)) {
			return false;
		}
		// Do not allow to change folders with less than 1 level deep from "/", 
		// for example: deny for "/var",
		// but allow for "/var/www"
		if (substr_count($path, "/") <= 1) {
			trigger_error("SSH: ".__FUNCTION__.": attempt to change \"".$path."\" denied!", E_USER_WARNING);
			return false;
		}
		$this->_log($server_info, __FUNCTION__, "path: '".$path."', new_mode: ".$new_mode.", recurse: ".(int)$recursively);
		return $this->exec($server_info, "chmod ".($recursively ? "-R" : "")." ".$new_mode." '".$path."'");
	}

	/**
	* Chown remote file
	*/
	function chown($server_info = array(), $path = "", $new_owner = "", $new_group = "", $recursively = false) {
		if (is_array($path)) {
			$_bulk_cmd = array();
			foreach ((array)$path as $_path => $_new_owner) {
				$_path = $this->_prepare_path($_path);
				if (substr_count($_path, "/") <= 1) {
					trigger_error("SSH: ".__FUNCTION__.": attempt to change \"".$_path."\" denied!", E_USER_WARNING);
					continue;
				}
				$_bulk_cmd[] .= "chown ".($recursively ? "-R" : "")." ".$_new_owner." '".$_path."'";
			}
			$_bulk_cmd = implode(" | ", $_bulk_cmd);
			$this->_log($server_info, __FUNCTION__, "bulk cmd: ".$_bulk_cmd);
			return !empty($_bulk_cmd) ? $this->exec($server_info, $_bulk_cmd) : false;
		}
		$path = $this->_prepare_path($path);
		$new_owner = preg_replace("/[^a-z0-9 \-\_]/ims", "", $new_owner);
		$new_group = preg_replace("/[^a-z0-9 \-\_]/ims", "", $new_group);
		if (!strlen($path) || !strlen($new_owner)) {
			return false;
		}
		// Do not allow to change folders with less than 1 level deep from "/", 
		// for example: deny for "/var",
		// but allow for "/var/www"
		if (substr_count($path, "/") <= 1) {
			trigger_error("SSH: ".__FUNCTION__.": attempt to change \"".$path."\" denied!", E_USER_WARNING);
			return false;
		}
		$this->_log($server_info, __FUNCTION__, "path: '".$path."', new_owner: ".$new_owner.", new_group: ".$new_group.", recurse: ".(int)$recursively);
		return $this->exec($server_info, "chown ".($recursively ? "-R" : "")." ".$new_owner. ($new_group ? ":".$new_group : "")." '".$path."'");
	}

	/**
	* Rename remote file, dir or link
	*/
	function rename($server_info = array(), $old_name = "", $new_name = "") {
		$old_name = $this->_prepare_path($old_name);
		$new_name = $this->_prepare_path($new_name);
		if (!strlen($old_name) || !strlen($new_name)) {
			return false;
		}
		$path = $old_name;
		// Do not allow to change folders with less than 1 level deep from "/", 
		// for example: deny for "/var",
		// but allow for "/var/www"
		if (substr_count($path, "/") <= 1) {
			trigger_error("SSH: ".__FUNCTION__.": attempt to rename \"".$path."\" denied!", E_USER_WARNING);
			return false;
		}
		$this->_log($server_info, __FUNCTION__, "old_name: '".$old_name."', new_name: '".$new_name."'");
		return $this->exec($server_info, "mv '".$old_name."' '".$new_name."'");
	}

	/**
	* Copy remote dir structure into local one (bulk method)
	*/
	function download_dir ($server_info = array(), $remote_dir = "", $local_dir = "", $pattern_include = "", $pattern_exclude = "", $level = null) {
		$local_dir	= $this->_prepare_path($local_dir);
		$remote_dir	= $this->_prepare_path($remote_dir);
		if (!$this->_INIT_OK || !$server_info || !strlen($local_dir) || !strlen($remote_dir)) {
			return false;
		}
		if (!$this->file_exists($server_info, $remote_dir)) {
			trigger_error("SSH: ".__FUNCTION__.": remote dir \"".$remote_dir."\" not exist", E_USER_WARNING);
			return false;
		}
		$cutoff_len = strlen($remote_dir);
		if (!$this->file_exists($server_info, $remote_dir)) {
			return false;
		}
		$completed = false;
		// Try this one if previous method failed or not allowed
		if (!$completed) {
			$dir_contents = $this->scan_dir($server_info, $remote_dir, $pattern_include, $pattern_exclude, $level);
			if (!$dir_contents) {
				return false;
			}
			foreach ((array)$dir_contents as $_path => $_info) {
				$remote_file	= $_path;
				$local_file		= $local_dir. substr($_path, $cutoff_len);
				_mkdir_m(dirname($local_file));
				if ($_info["type"] == "d") {
					_mkdir_m($local_file);
				} elseif ($_info["type"] == "f") {
					$this->read_file($server_info, $remote_file, $local_file);
				} elseif ($_info["type"] == "l") {
					// Resolve link target and download it
					$_target_path = $this->realpath($server_info, $_path);
					$_target_info = $this->file_info($server_info, $_target_path);
					if ($_target_info["type"] == "f") {
						$this->read_file($server_info, $_target_path, $local_file);
					}
				}
			}
		}
		$this->_log($server_info, __FUNCTION__, "remote_dir: '".$remote_dir."', local_dir: '".$local_dir."'");
		return true;
	}

	/**
	* Copy local dir structure into remote one (bulk method)
	*/
	function upload_dir ($server_info = array(), $local_dir = "", $remote_dir = "", $pattern_include = "", $pattern_exclude = "", $level = null) {
		$local_dir	= $this->_prepare_path($local_dir);
		$remote_dir	= $this->_prepare_path($remote_dir);
		if (!$this->_INIT_OK || !$server_info || !strlen($local_dir) || !strlen($remote_dir)) {
			return false;
		}
		if (!file_exists($local_dir)) {
			trigger_error("SSH: ".__FUNCTION__.": local dir \"".$local_dir."\" not exist", E_USER_WARNING);
			return false;
		}
		$cutoff_len = strlen($local_dir);
		$dir_contents = _class("dir")->scan_dir($local_dir, 1, $pattern_include, $pattern_exclude, $level);
		if (!$dir_contents) {
			return false;
		}
		$completed = false;
		// Create archive with all selected files before upload and then extract on the remote server
		// (preferred method)
		if ($this->MASS_USE_ARCHIVES) {
			$archive_path = $this->_local_make_tar($dir_contents);
			if ($archive_path && file_exists($archive_path)) {
				$remote_archive_path = $this->_prepare_path($remote_dir. "/". basename($archive_path));
				$this->write_file($server_info, $archive_path, $remote_archive_path);
				if ($this->file_exists($server_info, $remote_archive_path)) {
					$first_local_dir = trim(substr($local_dir, 0, strpos($local_dir, "/", 1)), "/");
					$_cwd = trim($this->exec($server_info, "pwd"));

					$_tmp_dir = $this->_prepare_path($remote_dir. "/". str_replace(array(".tar", ".gz", ".bz"), "", basename($archive_path)));
					$this->mkdir_m($server_info, $_tmp_dir);

					$cmd = "cd '".$_tmp_dir."';"
						." tar --extract ".($this->USE_GZIP ? " --ungzip" : "")." -p -f '".$remote_archive_path."';"
						." unlink '".$remote_archive_path."';";
					$this->exec($server_info, $cmd);

					$cmd = ""
						.($local_dir != "/" ? " mv ".$this->_prepare_path($_tmp_dir."/".$local_dir)."/* '".$remote_dir."';" : "")
						.($first_local_dir ? " rm -rf ".$this->_prepare_path($_tmp_dir).";" : "")
						." unlink '".$_tmp_dir."';"
						." cd '".$_cwd."'";
					$this->exec($server_info, $cmd);

					$completed = true;
				}
				unlink($archive_path);
			}
		}
		// Try this one if previous method failed or not allowed
		if (!$completed) {
			foreach ((array)$dir_contents as $_path) {
				$local_file		= $_path;
				$remote_file	= $remote_dir. "/". substr($_path, $cutoff_len);
				$remote_file	= str_replace("//", "/", $remote_file);
				$this->mkdir($server_info, dirname($remote_file));
				if (!$this->file_exists($server_info, dirname($remote_file))) {
					continue;
				}
				if (is_dir($local_file)) {
					$this->mkdir($server_info, $remote_file);
				} elseif (is_file($local_file)) {
					$this->write_file($server_info, $local_file, $remote_file);
				} elseif (is_link($local_file)) {
					// TODO: not sure what to do here...
				}
			}
		}
		$this->_log($server_info, __FUNCTION__, "local_dir: '".$local_dir."', remote_dir: '".$remote_dir."'");
		return true;
	}

	/**
	* Compress files to tar archive (local)
	*/
	function _local_make_tar ($files_list = array(), $archive_path = "") {
		if (empty($files_list)) {
			return false;
		}
		if (!$archive_path) {
			$archive_name = gmdate("Y-m-d__H_i_s")."_".abs(crc32(microtime(true))).".tar";
			$archive_path = $this->_prepare_path(realpath(PROJECT_PATH)."/". $this->_TMP_DIR). "/". $archive_name;
		}
		$destination_folder = dirname($archive_path);
		if (!file_exists($destination_folder)) {
			_mkdir_m($destination_folder);
		}
		// Check if destination folder really created
		if (!file_exists($destination_folder)) {
			return false;
		}
		$cur_dir = getcwd();
		chdir($destination_folder);
		if (file_exists($archive_path)) {
			unlink($archive_path);
		}
		foreach ((array)$files_list as $fpath) {
			$cmd = $this->TAR_PATH."tar "
				. (!file_exists($archive_path) ? "--create" : "--append"). " "
				. " -f ".$archive_path." ".$fpath."";
			exec($cmd);
		}
		if ($this->USE_GZIP) {
			exec($this->GZIP_PATH."gzip ".$archive_path);
			if (file_exists($archive_path.".gz")) {
				$archive_path .= ".gz";
			} else {
				// GZIP failed for some reason, turn off temporary
				$this->USE_GZIP = false;
			}
		}
		chdir($cur_dir);
		if (file_exists($archive_path)){
			return $archive_path;
		}
		return false;
	}

	/**
	* Extract files from tar archive (local)
	*/
	function _local_extract_tar ($archive_path = "", $extract_path = "") {
		if (empty($archive_path) || empty($extract_path)) {
			return false;
		}
		$archive_path = $this->_prepare_path($archive_path);
		$extract_path = $this->_prepare_path($extract_path);

		$destination_folder = dirname($extract_path);
		if (!file_exists($destination_folder)) {
			_mkdir_m($destination_folder);
		}
		return false;
	}

	/**
	* Clean path from SFTP prefix (usually for pretty output for user)
	*/
	function clean_path ($path = "") {
		$pattern = "#^(ssh2\.sftp://Resource id \#[0-9]+)#ims";
		if (is_array($path)) {
			// Get current resource string
			$cur = current($path);
			if (is_array($cur)) {
				$cur = current($cur);
			}
			preg_match($pattern, $cur, $m);
			return str_replace($m[1], "", $path);
		}
		return preg_replace($pattern, "", $path);
	}

	/**
	* Prepare path, Prevent some hacks and misuses
	*/
	function _prepare_path ($path = "") {
		if (is_array($path)) {
			foreach ((array)$path as $k => $v) {
				$path[$k] = $this->_prepare_path($v);
			}
			return $path;
		}
		$bad_chars = array("`", "\"", "'", "..", "~", " ", "\t", "\r", "\n", "|", "<", ">", "&");
		$result = str_replace($bad_chars, "", rtrim(str_replace(array("\\", "//", "///"), "/", trim($path)), "/"));
		return $result ? $result : "/";
	}

	/**
	* Prepare text for using inside (grep '%text%') or similar commands
	*/
	function _prepare_text ($text = "") {
		if (is_array($text)) {
			foreach ((array)$path as $k => $v) {
				$text[$k] = $this->_prepare_text($v);
			}
			return $text;
		}
		$text = preg_replace("/[\x0A-\xFF]/i", "", $text);
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
	function _log ($server_info = array(), $action = "", $comment = "") {
		if (!$this->LOG_ACTIONS) {
			return false;
		}
		$SERVER_ID = $this->_get_server_id($server_info);
		$sql_array = array(
			"server_id"		=> _es($SERVER_ID),
			"microtime"		=> _es(str_replace(",", ".", microtime(true))),
			"init_type"		=> _es(MAIN_TYPE),
			"action"		=> _es($action),
			"comment"		=> _es($comment),
			"get_object"	=> _es($_GET["object"]),
			"get_action"	=> _es($_GET["action"]),
			"user_id"		=> intval($_SESSION["user_id"]),
			"user_group"	=> intval($_SESSION["user_group"]),
			"ip"			=> _es(common()->get_ip()),
		);
		return db()->INSERT("log_ssh_action", $sql_array);
	}

	/**
	* Convert string permission output to numerical
	*/
	function _perm_str2num ($perm = "") {
		$perm_len = strlen(trim($perm));
		if ($perm_len > 10 && $perm_len < 9) {
			return false;
		}
		if ($perm_len == 10) {
			$perm = substr($perm, 1);
		}
		// Compatibility with sticky bit, setuid, setgid (http://en.wikipedia.org/wiki/File_system_permissions)
		$perm = str_replace(array("s", "S", "t", "T"), array("x", "-", "x", "-"), $perm);

		foreach ((array)str_split($perm) as $k => $v) {
			if ($v == "-") {
				continue;
			}
			// Owner
			if ($k == 0) {
				$own += 4;
			}
			if ($k == 1) {
				$own += 2;
			}
			if ($k == 2) {
				$own += 1;
			}
			// Group
			if ($k == 3) {
				$grp += 4;
			}
			if ($k == 4) {
				$grp += 2;
			}
			if ($k == 5) {
				$grp += 1;
			}
			// Others
			if ($k == 6) {
				$oth += 4;
			}
			if ($k == 7) {
				$oth += 2;
			}
			if ($k == 8) {
				$oth += 1;
			}
		}
		return "0". $own. "". $grp. "". $oth;
	}
}
