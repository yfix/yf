<?php

/**
*/
class yf_ssh_files {

	/**
	* Read remote file
	*/
	function read_file ($server_info = array(), $remote_file = "", $local_file = "") {
		$local_file		= trim($local_file);
		$remote_file	= _class('ssh')->_prepare_path($remote_file);
		if (!_class('ssh')->_INIT_OK || !$server_info || !strlen($remote_file)) {
			return false;
		}
		if (!_class('ssh')->file_exists($server_info, $remote_file)) {
			trigger_error("SSH: ".__FUNCTION__.": remote file ".$remote_file." does not exist", E_USER_WARNING);
			return false;
		}
		// When local file is empty we will return contents of the remote file as a string
		if ($local_file && !file_exists(dirname($local_file))) {
			_mkdir_m(dirname($local_file));
		}
		if (_class('ssh')->DRIVER == "phpseclib") {

			if (!($con = _class('ssh')->_init_sftp_phpseclib($server_info))) {
				return false;
			}
			if (DEBUG_MODE) {
				$time_start = microtime(true);
			}
			$result = $con->get($remote_file, $local_file ? $local_file : false);

		} elseif (_class('ssh')->DRIVER == "pecl_ssh2") {

			if (!($con = _class('ssh')->connect($server_info))) {
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
			_class('ssh')->_debug["exec"][] = $debug_info;
			_class('ssh')->_debug["time_sum"] += $exec_time;
		}
		_class('ssh')->_log($server_info, __FUNCTION__, "remote_file: ".$remote_file.", local_file: ".$local_file."");
		return $result;
	}

	/**
	* Write local file into remote file
	*/
	function write_file ($server_info = array(), $local_file = "", $remote_file = "") {
		$local_file		= trim($local_file);
		$remote_file	= _class('ssh')->_prepare_path($remote_file);
		if (!_class('ssh')->_INIT_OK || !$server_info || !strlen($local_file) || !strlen($remote_file)) {
			return false;
		}
		if (!file_exists($local_file)) {
			trigger_error("SSH: ".__FUNCTION__.": local file ".$local_file." does not exist", E_USER_WARNING);
			return false;
		}
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// Check if remote folder exists and create it if not done yet
		_class('ssh')->mkdir($server_info, dirname($remote_file));
		if (!_class('ssh')->file_exists($server_info, dirname($remote_file))) {
			trigger_error("SSH: ".__FUNCTION__.": cannot create remote dir ".dirname($remote_file)."", E_USER_WARNING);
			return false;
		}
		if (_class('ssh')->DRIVER == "phpseclib") {

			if (!($con = _class('ssh')->_init_sftp_phpseclib($server_info))) {
				return false;
			}
			$result = $con->put($remote_file, file_get_contents($local_file));

		} elseif (_class('ssh')->DRIVER == "pecl_ssh2") {

			if (!($con = _class('ssh')->connect($server_info))) {
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
			_class('ssh')->_debug["exec"][] = $debug_info;
			_class('ssh')->_debug["time_sum"] += $exec_time;
		}
		_class('ssh')->_log($server_info, __FUNCTION__, "local_file: ".$local_file.", remote_file: ".$remote_file."");
		return $result;
	}

	/**
	* Write string into remote file
	*/
	function write_string ($server_info = array(), $string = "", $remote_file = "") {
		$remote_file	= _class('ssh')->_prepare_path($remote_file);
		if (!_class('ssh')->_INIT_OK || !$server_info || !$string || (!strlen($remote_file) && !is_array($string))) {
			return false;
		}
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		if (_class('ssh')->DRIVER == "phpseclib") {
		
			if (!($sftp = _class('ssh')->_init_sftp_phpseclib($server_info))) {
				return false;
			}
		
		} elseif (_class('ssh')->DRIVER == "pecl_ssh2") {
		
			if (!($con = _class('ssh')->connect($server_info))) {
				return false;
			}
			$sftp = ssh2_sftp($con);
		
		}
		// Second argument as array
		if (is_array($string) && $sftp) {
			$completed = false;
			// Try to create temporary files and upload them with single archive
			// and then extract on the remote server (preferred method)
			if (_class('ssh')->MASS_USE_ARCHIVES) {
				$_remote_dir = "/tmp/__ssh_write_string__".abs(crc32(microtime(true)));
				_class('ssh')->mkdir_m($server_info, $_remote_dir);
				// Prepare local temporary folder for storing in tar archive
				$_local_tmp_dir = _class('ssh')->_prepare_path(realpath(PROJECT_PATH)."/". _class('ssh')->_TMP_DIR). "/". md5(microtime(true)."__write_string");
				_mkdir_m(dirname($_local_tmp_dir));
				// Fill temporary files with strings
				foreach ((array)$string as $_remote_file => $_string) {
					$_tmp_path = _class('ssh')->_prepare_path($_local_tmp_dir. "/". $_remote_file);
					if (!file_exists(dirname($_tmp_path))) {
						_mkdir_m(dirname($_tmp_path));
					}
					file_put_contents($_tmp_path, $_string);
				}
				// Get list of new files
				$cutoff_len = strlen($_local_tmp_dir);
				$dir_contents = _class("dir")->scan_dir($_local_tmp_dir, 1);
				if ($dir_contents) {
					$archive_path = _class('ssh')->_local_make_tar($dir_contents);
				}
				// Go with uploading and extracting
				if ($archive_path && file_exists($archive_path)) {
					$remote_archive_path = $_remote_dir. "/". basename($archive_path);
					_class('ssh')->write_file($server_info, $archive_path, $remote_archive_path);
					if (_class('ssh')->file_exists($server_info, $remote_archive_path)) {
						$first_local_dir = trim(substr($_local_tmp_dir, 0, strpos($_local_tmp_dir, "/", 1)), "/");
						$_cwd = trim(_class('ssh')->exec($server_info, "pwd"));
			
						$_tmp_dir = _class('ssh')->_prepare_path($_remote_dir. "/". str_replace(array(".tar", ".gz", ".bz"), "", basename($archive_path)));

						_class('ssh')->mkdir_m($server_info, $_tmp_dir);
			
						$cmd = "cd '".$_tmp_dir."';"
							." tar --extract ".(_class('ssh')->USE_GZIP ? " --ungzip" : "")." -p -f '".$remote_archive_path."';"
							." unlink '".$remote_archive_path."';";
						_class('ssh')->exec($server_info, $cmd);

						$cmd = ""
							.(" mv "._class('ssh')->_prepare_path($_tmp_dir."/".(OS_WINDOWS ? substr($_local_tmp_dir, 2) : $_local_tmp_dir))."/* '".$_remote_dir."';")
							.($first_local_dir ? " rm -rf '"._class('ssh')->_prepare_path($_tmp_dir)."';" : "")
// TODO: convert cp ...* to the "find . | xargs cp ..." to avoid errors like "argument list too long"
							.(" cp -r ".$_remote_dir."/* /;")
							.(" rm -rf ".$_remote_dir.";")
							." unlink '".$_remote_dir."';"
							." cd '".$_cwd."'";
						_class('ssh')->exec($server_info, $cmd);
			
						$completed = true;
					}
					unlink($archive_path);
				}
				_class("dir")->delete_dir($_local_tmp_dir, 1);
			}
			if (!$completed) {
				foreach ((array)$string as $_remote_file => $_string) {
					// Check if remote folder exists and create it if not done yet
					_class('ssh')->mkdir($server_info, dirname($_remote_file));
					if (!_class('ssh')->file_exists($server_info, dirname($_remote_file))) {
						trigger_error("SSH: ".__FUNCTION__.": cannot create remote dir ".dirname($_remote_file)."", E_USER_WARNING);
						continue;
					}
					if (_class('ssh')->DRIVER == "phpseclib") {

						$result = $con->put($remote_file, $_string);

					} elseif (_class('ssh')->DRIVER == "pecl_ssh2") {

						$sftp_stream = fopen("ssh2.sftp://".$sftp.$_remote_file, 'w');
						$result = fwrite($sftp_stream, $_string);
						fclose($sftp_stream);

					}
				}
			}
			_class('ssh')->_log($server_info, __FUNCTION__, "strlen: ".strlen($string).", remote_file: ".$remote_file."");
			return $result;
		}
		if (DEBUG_MODE) {
			$exec_time = microtime(true) - $time_start;
			$debug_info .= "<b>".common()->_format_time_value($exec_time)." sec</b>,\n";
			$debug_info .= "func: <b>".__FUNCTION__."</b>, server: ".$server_info["base_ip"].",\n";
			$debug_info .= "strlen: \"<b>".strlen($string)."</b>\"<br />";
			$debug_info .= "remote_file: \"<b style='color:blue;'>".$remote_file."</b>\"<br />";
			_class('ssh')->_debug["exec"][] = $debug_info;
			_class('ssh')->_debug["time_sum"] += $exec_time;
		}
		// Check if remote folder exists and create it if not done yet
		_class('ssh')->mkdir($server_info, dirname($remote_file));
		if (!_class('ssh')->file_exists($server_info, dirname($remote_file))) {
			trigger_error("SSH: ".__FUNCTION__.": cannot create remote dir ".dirname($remote_file)."", E_USER_WARNING);
			return false;
		}
		// Go!
		if ($sftp) {
			if (_class('ssh')->DRIVER == "phpseclib") {
		
				$result = $con->put($remote_file, $string);
		
			} elseif (_class('ssh')->DRIVER == "pecl_ssh2") {
		
				$sftp_stream = fopen("ssh2.sftp://".$sftp.$remote_file, 'w');
				$result = fwrite($sftp_stream, $string);
				fclose($sftp_stream);
		
			}
		}
		_class('ssh')->_log($server_info, __FUNCTION__, "strlen: ".strlen($string).", remote_file: ".$remote_file."");
		return $result;
	}

	/**
	* Check if file exists remotely
	*/
	function file_exists($server_info = array(), $path = "") {
		$path = _class('ssh')->_prepare_path($path);
		if (strlen($path)) {
			$command = "echo \"if [ -e '".$path."' ]; then echo 1; else echo 0; fi\" | bash";
			$result = (bool)intval(_class('ssh')->exec($server_info, $command));
			_class('ssh')->_log($server_info, __FUNCTION__, "path: ".$path.", result: ".(int)$result);
			return $result;
		}
		return false;
	}

	/**
	* Get selected file info
	*/
	function file_info ($server_info = array(), $path = "") {
		$path = _class('ssh')->_prepare_path($path);
		if (!_class('ssh')->_INIT_OK || !strlen($path) || !$server_info) {
			return false;
		}
		$result = _class('ssh')->scan_dir($server_info, dirname($path), "", "", 0, basename($path));
		$result = current($result);
		_class('ssh')->_log($server_info, __FUNCTION__, "path: ".$path."");
		return $result;
	}

	/**
	* Resolve full path for the given file, dir or link
	*/
	function realpath($server_info = array(), $path = "") {
		return trim(_class('ssh')->exec($server_info, "realpath ".$path), "'`\"\t\n ");
	}

	/**
	* Scan remote dir and return array of files details
	*/
	function scan_dir ($server_info = array(), $start_dir = "", $pattern_include = "", $pattern_exclude = "", $level = 0, $single_file = "") {
		if (is_array($start_dir)) {
			$_merged_contents = array();
			foreach ((array)$start_dir as $_start_dir) {
				$_cur_contents = (array)_class('ssh')->scan_dir ($server_info, $_start_dir, $pattern_include, $pattern_exclude, $level, $single_file);
				$_merged_contents += (array)$_cur_contents;
			}
			return $_merged_contents;
		}
		if ($start_dir != "/") {
			$start_dir = _class('ssh')->_prepare_path($start_dir);
		}
		if (!_class('ssh')->_INIT_OK || !strlen($start_dir) || !$server_info) {
			return false;
		}
		if (strlen($single_file)) {
			$single_file = basename(_class('ssh')->_prepare_path($single_file));
		}
		$SERVER_OS = _class('ssh')->_get_remote_os($server_info);
		if ($SERVER_OS == "LINUX") {
			$ls_cmd = "ls -flvH --time-style=long-iso";
		} elseif ($SERVER_OS == "FREEBSD") {
			$ls_cmd = "ls -flTH";
		} else {
			$ls_cmd = "ls -flH";
		}
		$tmp = _class('ssh')->exec($server_info, $ls_cmd." ".$start_dir. ($single_file ? " | grep ".$single_file : ""));
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
					$_tmp_link = _class('ssh')->realpath($server_info, ($start_dir != "/" ? $start_dir : "")."/".$_name);
					if ($_tmp_link) {
						$_link = $_tmp_link;
					} else {
						$_link = str_replace("..", "", trim($_link));
					}
				}
			}
			// Here full path to the item
			$item_name = ($start_dir != "/" ? $start_dir : "")."/".$_name;

			if (_class('ssh')->_skip_by_pattern($item_name, $_type, $pattern_include, $pattern_exclude)) {
				continue;
			}
			$_user	= trim($m[3][$id]);
			$_group	= trim($m[4][$id]);
			$_size	= trim($m[5][$id]);
			$_date	= strtotime($m[6][$id]);
			$_mode	= _class('ssh')->_perm_str2num($_perms);
			// We have dir here
			if ($_type == "d") {
				if (is_null($level) || $level > 0) {
					// Merge result
					foreach ((array)_class('ssh')->scan_dir($server_info, $item_name, $pattern_include, $pattern_exclude, is_null($level) ? $level : $level - 1) as $_path => $_info) {
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
		_class('ssh')->_log($server_info, __FUNCTION__, "start_dir: ".$start_dir.", level: ".(int)$level);
		return $files;
	}

	/**
	* Alias for the mkdir_m
	*/
	function mkdir($server_info = array(), $dir_name = "", $dir_mode = 755, $create_index_htmls = 0, $start_folder = "") {
		return _class('ssh')->mkdir_m($server_info, $dir_name, $dir_mode, $create_index_htmls, $start_folder);
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
		if (!_class('ssh')->_INIT_OK || !$server_info) {
			return false;
		}
		if (is_array($dir_name)) {
			// Default start folder to look at
			if (!strlen($start_folder)) {
				$start_folder = "/";
			}
			$_cmd = array();
			foreach ((array)$dir_name as $_dir_name => $_dir_mode) {
				$_dir_name = _class('ssh')->_prepare_path($_dir_name);
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
			_class('ssh')->exec($server_info, implode(" ; ", (array)$_cmd));
			_class('ssh')->_log($server_info, __FUNCTION__, "dir_name: ".print_r($dir_name, 1));
			return true;
		}
		$dir_name = _class('ssh')->_prepare_path($dir_name);
		if (!strlen($dir_name)) {
			return false;
		}
		// Default start folder to look at
		if (!strlen($start_folder)) {
			$start_folder = "/";
		}
		// String dir mode
		if (is_string($dir_mode) && strlen($dir_mode) >= 9 && strlen($dir_mode) <= 10) {
			$dir_mode = (string)_class('ssh')->_perm_str2num($dir_mode);
		}
		// Default dir mode
		$dir_mode = abs(intval($dir_mode));
		if (empty($dir_mode) || $dir_mode > 777) {
			$dir_mode = 755;
		}
		_class('ssh')->exec($server_info, "mkdir -m ".$dir_mode." -p ".$dir_name);
		_class('ssh')->_log($server_info, __FUNCTION__, "dir_name: ".$dir_name.", dir_mode: ".(int)$dir_mode);
		return true;
	}

	/**
	* Remove remote dir
	*/
	function rmdir($server_info = array(), $path = "") {
		$path = _class('ssh')->_prepare_path($path);
		// Do not allow to change folders with less than 1 level deep from "/", 
		// for example: deny for "/var",
		// but allow for "/var/www"
		if (substr_count($path, "/") <= 1) {
			trigger_error("SSH: ".__FUNCTION__.": attempt to remove ".$path." folder denied!", E_USER_WARNING);
			return false;
		}
		_class('ssh')->_log($server_info, __FUNCTION__, "path: ".$path."");
		return _class('ssh')->exec($server_info, "rm -rf '".$path."'");
	}

	/**
	* Unlink remote file or link
	*/
	function unlink($server_info = array(), $path = "") {
		$path = _class('ssh')->_prepare_path($path);
		_class('ssh')->_log($server_info, __FUNCTION__, "path: ".$path."");
		return _class('ssh')->exec($server_info, "unlink '".$path."'");
	}

	/**
	* Chmod remote file
	*/
	function chmod($server_info = array(), $path = "", $new_mode = null, $recursively = false) {
		if (is_array($path)) {
			$_bulk_cmd = array();
			foreach ((array)$path as $_path => $_new_mode) {
				$_path = _class('ssh')->_prepare_path($_path);
				if (substr_count($_path, "/") <= 1) {
					trigger_error("SSH: ".__FUNCTION__.": attempt to change ".$_path." denied!", E_USER_WARNING);
					continue;
				}
				$_bulk_cmd[] .= "chmod ".($recursively ? "-R" : "")." ".$_new_mode." '".$_path."'";
			}
			$_bulk_cmd = implode(" | ", $_bulk_cmd);
			_class('ssh')->_log($server_info, __FUNCTION__, "bulk cmd: ".$_bulk_cmd);
			return !empty($_bulk_cmd) ? _class('ssh')->exec($server_info, $_bulk_cmd) : false;
		}
		$path = _class('ssh')->_prepare_path($path);
		if (!strlen($path) || is_null($new_mode) || !is_numeric($new_mode)) {
			return false;
		}
		// Do not allow to change folders with less than 1 level deep from "/", 
		// for example: deny for "/var",
		// but allow for "/var/www"
		if (substr_count($path, "/") <= 1) {
			trigger_error("SSH: ".__FUNCTION__.": attempt to change ".$path." denied!", E_USER_WARNING);
			return false;
		}
		_class('ssh')->_log($server_info, __FUNCTION__, "path: ".$path.", new_mode: ".$new_mode.", recurse: ".(int)$recursively);
		return _class('ssh')->exec($server_info, "chmod ".($recursively ? "-R" : "")." ".$new_mode." '".$path."'");
	}

	/**
	* Chown remote file
	*/
	function chown($server_info = array(), $path = "", $new_owner = "", $new_group = "", $recursively = false) {
		if (is_array($path)) {
			$_bulk_cmd = array();
			foreach ((array)$path as $_path => $_new_owner) {
				$_path = _class('ssh')->_prepare_path($_path);
				if (substr_count($_path, "/") <= 1) {
					trigger_error("SSH: ".__FUNCTION__.": attempt to change ".$_path." denied!", E_USER_WARNING);
					continue;
				}
				$_bulk_cmd[] .= "chown ".($recursively ? "-R" : "")." ".$_new_owner." '".$_path."'";
			}
			$_bulk_cmd = implode(" | ", $_bulk_cmd);
			_class('ssh')->_log($server_info, __FUNCTION__, "bulk cmd: ".$_bulk_cmd);
			return !empty($_bulk_cmd) ? _class('ssh')->exec($server_info, $_bulk_cmd) : false;
		}
		$path = _class('ssh')->_prepare_path($path);
		$new_owner = preg_replace("/[^a-z0-9 \-\_]/ims", "", $new_owner);
		$new_group = preg_replace("/[^a-z0-9 \-\_]/ims", "", $new_group);
		if (!strlen($path) || !strlen($new_owner)) {
			return false;
		}
		// Do not allow to change folders with less than 1 level deep from "/", 
		// for example: deny for "/var",
		// but allow for "/var/www"
		if (substr_count($path, "/") <= 1) {
			trigger_error("SSH: ".__FUNCTION__.": attempt to change ".$path." denied!", E_USER_WARNING);
			return false;
		}
		_class('ssh')->_log($server_info, __FUNCTION__, "path: ".$path.", new_owner: ".$new_owner.", new_group: ".$new_group.", recurse: ".(int)$recursively);
		return _class('ssh')->exec($server_info, "chown ".($recursively ? "-R" : "")." ".$new_owner. ($new_group ? ":".$new_group : "")." '".$path."'");
	}

	/**
	* Rename remote file, dir or link
	*/
	function rename($server_info = array(), $old_name = "", $new_name = "") {
		$old_name = _class('ssh')->_prepare_path($old_name);
		$new_name = _class('ssh')->_prepare_path($new_name);
		if (!strlen($old_name) || !strlen($new_name)) {
			return false;
		}
		$path = $old_name;
		// Do not allow to change folders with less than 1 level deep from "/", 
		// for example: deny for "/var",
		// but allow for "/var/www"
		if (substr_count($path, "/") <= 1) {
			trigger_error("SSH: ".__FUNCTION__.": attempt to rename ".$path." denied!", E_USER_WARNING);
			return false;
		}
		_class('ssh')->_log($server_info, __FUNCTION__, "old_name: ".$old_name.", new_name: ".$new_name."");
		return _class('ssh')->exec($server_info, "mv '".$old_name."' '".$new_name."'");
	}

	/**
	* Copy remote dir structure into local one (bulk method)
	*/
	function download_dir ($server_info = array(), $remote_dir = "", $local_dir = "", $pattern_include = "", $pattern_exclude = "", $level = null) {
		$local_dir	= _class('ssh')->_prepare_path($local_dir);
		$remote_dir	= _class('ssh')->_prepare_path($remote_dir);
		if (!_class('ssh')->_INIT_OK || !$server_info || !strlen($local_dir) || !strlen($remote_dir)) {
			return false;
		}
		if (!_class('ssh')->file_exists($server_info, $remote_dir)) {
			trigger_error('SSH: '.__FUNCTION__.': remote dir '.$remote_dir.' not exist', E_USER_WARNING);
			return false;
		}
		$cutoff_len = strlen($remote_dir);
		if (!_class('ssh')->file_exists($server_info, $remote_dir)) {
			return false;
		}
		$completed = false;
		// Try this one if previous method failed or not allowed
		if (!$completed) {
			$dir_contents = _class('ssh')->scan_dir($server_info, $remote_dir, $pattern_include, $pattern_exclude, $level);
			if (!$dir_contents) {
				return false;
			}
			foreach ((array)$dir_contents as $_path => $_info) {
				$remote_file	= $_path;
				$local_file		= $local_dir. substr($_path, $cutoff_len);
				_mkdir_m(dirname($local_file));
				if ($_info['type'] == 'd') {
					_mkdir_m($local_file);
				} elseif ($_info['type'] == 'f') {
					_class('ssh')->read_file($server_info, $remote_file, $local_file);
				} elseif ($_info['type'] == 'l') {
					// Resolve link target and download it
					$_target_path = _class('ssh')->realpath($server_info, $_path);
					$_target_info = _class('ssh')->file_info($server_info, $_target_path);
					if ($_target_info['type'] == 'f') {
						_class('ssh')->read_file($server_info, $_target_path, $local_file);
					}
				}
			}
		}
		_class('ssh')->_log($server_info, __FUNCTION__, 'remote_dir: '.$remote_dir.', local_dir: '.$local_dir.'');
		return true;
	}

	/**
	* Copy local dir structure into remote one (bulk method)
	*/
	function upload_dir ($server_info = array(), $local_dir = '', $remote_dir = '', $pattern_include = '', $pattern_exclude = '', $level = null) {
		$local_dir	= _class('ssh')->_prepare_path($local_dir);
		$remote_dir	= _class('ssh')->_prepare_path($remote_dir);
		if (!_class('ssh')->_INIT_OK || !$server_info || !strlen($local_dir) || !strlen($remote_dir)) {
			return false;
		}
		if (!file_exists($local_dir)) {
			trigger_error('SSH: '.__FUNCTION__.': local dir: '.$local_dir.' not exists', E_USER_WARNING);
			return false;
		}
		$cutoff_len = strlen($local_dir);
		$dir_contents = _class('dir')->scan_dir($local_dir, 1, $pattern_include, $pattern_exclude, $level);
		if (!$dir_contents) {
			return false;
		}
		$completed = false;
		// Create archive with all selected files before upload and then extract on the remote server
		// (preferred method)
		if (_class('ssh')->MASS_USE_ARCHIVES) {
			$archive_path = _class('ssh')->_local_make_tar($dir_contents);
			if ($archive_path && file_exists($archive_path)) {
				$remote_archive_path = _class('ssh')->_prepare_path($remote_dir. '/'. basename($archive_path));
				_class('ssh')->write_file($server_info, $archive_path, $remote_archive_path);
				if (_class('ssh')->file_exists($server_info, $remote_archive_path)) {
					$first_local_dir = trim(substr($local_dir, 0, strpos($local_dir, '/', 1)), '/');
					$_cwd = trim(_class('ssh')->exec($server_info, 'pwd'));

					$_tmp_dir = _class('ssh')->_prepare_path($remote_dir. '/'. str_replace(array('.tar', '.gz', '.bz'), '', basename($archive_path)));
					_class('ssh')->mkdir_m($server_info, $_tmp_dir);

					$cmd = "cd '".$_tmp_dir."';"
						." tar --extract ".(_class('ssh')->USE_GZIP ? " --ungzip" : "")." -p -f '".$remote_archive_path."';"
						." unlink '".$remote_archive_path."';";
					_class('ssh')->exec($server_info, $cmd);

					$cmd = ""
						.($local_dir != "/" ? " mv "._class('ssh')->_prepare_path($_tmp_dir."/".$local_dir)."/* '".$remote_dir."';" : "")
						.($first_local_dir ? " rm -rf "._class('ssh')->_prepare_path($_tmp_dir).";" : "")
						." unlink '".$_tmp_dir."';"
						." cd '".$_cwd."'";
					_class('ssh')->exec($server_info, $cmd);

					$completed = true;
				}
				unlink($archive_path);
			}
		}
		// Try this one if previous method failed or not allowed
		if (!$completed) {
			foreach ((array)$dir_contents as $_path) {
				$local_file		= $_path;
				$remote_file	= $remote_dir. '/'. substr($_path, $cutoff_len);
				$remote_file	= str_replace('//', '/', $remote_file);
				_class('ssh')->mkdir($server_info, dirname($remote_file));
				if (!_class('ssh')->file_exists($server_info, dirname($remote_file))) {
					continue;
				}
				if (is_dir($local_file)) {
					_class('ssh')->mkdir($server_info, $remote_file);
				} elseif (is_file($local_file)) {
					_class('ssh')->write_file($server_info, $local_file, $remote_file);
				} elseif (is_link($local_file)) {
					// TODO: not sure what to do here...
				}
			}
		}
		_class('ssh')->_log($server_info, __FUNCTION__, 'local_dir: '.$local_dir.', remote_dir: '.$remote_dir.'');
		return true;
	}
}
