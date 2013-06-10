<?php

/**
 * Directory handling class
 * 
 * @package		YF
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 * @revision	$Revision$
 */
class yf_dir {

	/** @var bool */
	public $CHECK_IF_READABLE = true;
	/** @var bool */
	public $CHECK_IF_WRITABLE = true;

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* Check if we need to skip current path according to given patterns (unified method for whole dir module)
	*/
	function _skip_by_pattern ($path = "", $_is_dir = false, $pattern_include = "", $pattern_exclude = "") {
		if (!$path) {
			return false;
		}
		if (!$pattern_include && !$pattern_exclude) {
			return false;
		}
		$_path_clean = trim(str_replace("//", "/", str_replace("\\", "/", $path)));
		// Include files only if they match the mask
		$_index = $_is_dir ? 0 : 1;
		if ($_is_dir) {
			$_path_clean	= rtrim($_path_clean, "/");
		}
		if (is_array($pattern_include)) {
			$pattern_include = $pattern_include[$_index];
		}
		if (is_array($pattern_exclude)) {
			$pattern_exclude = $pattern_exclude[$_index];
		}
		$MATCHED = false;
		if (!empty($pattern_include) && is_string($pattern_include)) {
			if (strlen($pattern_include) == 2 && $pattern_include{0} == "-") {
				if ($pattern_include == "-d" && !$_is_dir) {
					$MATCHED = true;
				} elseif ($pattern_include == "-f" && $_is_dir) {
					$MATCHED = true;
				}
			} elseif (!preg_match($pattern_include."ims", $_path_clean)) {
				$MATCHED = true;
			}
		}
		// Exclude files from list by mask
		if (!empty($pattern_exclude) && is_string($pattern_exclude)) {
			if (strlen($pattern_exclude) == 2 && $pattern_exclude{0} == "-") {
				if ($pattern_exclude == "-d" && $_is_dir) {
					$MATCHED = true;
				} elseif ($pattern_exclude == "-f" && !$_is_dir) {
					$MATCHED = true;
				}
			} elseif (preg_match($pattern_exclude."ims", $_path_clean)) {
				$MATCHED = true;
			}
		}
		return $MATCHED;
	}

	/**
	* Recursively scanning directory structure (including subdirectories) //
	*/
	function scan_dir ($start_dir, $FLAT_MODE = true, $pattern_include = "", $pattern_exclude = "", $level = null) {
		// Here we accept several start folders, result will be merged
		if (is_array($start_dir)) {
			$FLAT_MODE = true;
			foreach ((array)$start_dir as $_dir_name) {
				foreach ((array)$this->scan_dir($_dir_name, 1, $pattern_include, $pattern_exclude, $level) as $_file_path) {
					$_files[] = $_file_path;
				}
			}
			return $_files;
		}
		if (!$start_dir || !file_exists($start_dir)) {
			return false;
		}
		$start_dir = rtrim($start_dir, "/");

		$files	= array();
		$dh		= opendir($start_dir);
		while (false !== ($f = readdir($dh))) {
			if ($f == "." || $f == "..") {
				continue;
			}
			$item_name	= $start_dir."/".$f;
			$tmp_file	= $FLAT_MODE ? $item_name : $f;
			$_is_dir	= is_dir($item_name);
			// Check patterns
			if ($this->_skip_by_pattern($_is_dir ? $item_name : $tmp_file, $_is_dir, $pattern_include, $pattern_exclude)) {
				continue;
			}
			// "Flat" mode (all filenames are stored as 1-dimension array, else - multi-dimension array)
			if ($_is_dir) {
				if (is_null($level) || $level > 0) {
					$tmp_file = $this->scan_dir($item_name, $FLAT_MODE, $pattern_include, $pattern_exclude, is_null($level) ? $level : $level - 1);
				}
			}
			// Add item to the result array
			$files[$item_name] = $tmp_file;
		}
		closedir($dh);
		// Prepare for the flat mode (if needed)
		if (is_array($files)) {
			if ($FLAT_MODE) {
				$files = $this->array_values_recursive($files);
			}
			ksort($files);
		}
		return $files;
	}

	/**
	* This function calculate directory size
	*/
	function dirsize($start_dir, $pattern_include = "", $pattern_exclude = "") {
		if (!$start_dir || !file_exists($start_dir)) {
			return false;
		}
		$start_dir = rtrim($start_dir, "/");

		$dh = opendir($start_dir);
		$size = 0;
		while (($f = readdir($dh)) !== false) {
			if ($f == "." || $f == "..") {
				continue;
			}
			$path = $start_dir."/".$f;
			$_is_dir	= is_dir($path);
			// Check patterns
			if ($this->_skip_by_pattern($path, $_is_dir, $pattern_include, $pattern_exclude)) {
				continue;
			}
			if ($_is_dir) {
				$size += $this->dirsize($path."/", $pattern_include, $pattern_exclude);
			} elseif (is_file($path)) {
				$size += filesize($path);
			}
		}
		closedir($dh);
		return $size;
	}

	/**
	* This function calculate number of files by mask inside given directory
	*/
	function count_files($start_dir, $pattern_include = "", $pattern_exclude = "") {
		if (!$start_dir || !file_exists($start_dir)) {
			return false;
		}
		$start_dir = rtrim($start_dir, "/");

		$dh = opendir($start_dir);
		$num_files = 0;
		while (($f = readdir($dh)) !== false) {
			if ($f == "." || $f == "..") {
				continue;
			}
			$path = $start_dir."/".$f;
			$_is_dir	= is_dir($path);
			// Check patterns
			if ($this->_skip_by_pattern($path, $_is_dir, $pattern_include, $pattern_exclude)) {
				continue;
			}
			if ($_is_dir) {
				$num_files += $this->count_files($path."/", $pattern_include, $pattern_exclude);
			} elseif (is_file($path)) {
				$num_files++;
			}
		}
		closedir($dh);
		return $num_files;
	}

	/**
	* This function recursively copies contents of source directory to destination
	*/
	function copy_dir($path1, $path2, $pattern_include = "", $pattern_exclude = "", $level = null) {
		if (!$path1 || !file_exists($path1)) {
			return false;
		}
		$path1 = rtrim(str_replace("\\", "/", realpath($path1)), "/");
		$path2 = rtrim(str_replace("\\", "/", realpath($path2)), "/");

		$dh = opendir($path1);
		$old_mask = umask(0);
		if (!file_exists($path2)) {
			$this->mkdir_m($path2);
		}
		while (false !== ($f = readdir($dh))) {
			if ($f == "." || $f == "..") {
				continue;
			}
			$item_name_1 = $path1."/".$f;
			$item_name_2 = $path2."/".$f;
			$_is_dir	= is_dir($item_name_1);
			// Check patterns
			if ($this->_skip_by_pattern($item_name_1, $_is_dir, $pattern_include, $pattern_exclude)) {
				continue;
			}
			if ($_is_dir) {
				if (!file_exists($item_name_2)) {
					$this->mkdir_m($item_name_2);
				}
				if (is_null($level) || $level > 0) {
					$this->copy_dir($item_name_1, $item_name_2, $pattern_include, $pattern_exclude, is_null($level) ? $level : $level - 1);
				}
			} else {
				$this->_copy_file($item_name_1, $item_name_2);
			}
		}
		umask($old_mask);
	}

	/**
	* This function recursively move contents of source directory to destination
	*/
	function move_dir($path1, $path2, $pattern_include = "", $pattern_exclude = "") {
		if (!$path1 || !file_exists($path1)) {
			return false;
		}
		$path1 = rtrim($path1, "/");
		$path2 = rtrim($path2, "/");

		$dh = opendir($path1);
		if (!file_exists($path2)) {
			mkdir($path2, 0777);
		}
		while (false !== ($f = readdir($dh))) {
			if ($f == "." || $f == "..") {
				continue;
			}
			$item_name_1 = $path1."/".$f;
			$item_name_2 = $path2."/".$f;
			$_is_dir	= is_dir($item_name_1);
			// Check patterns
			if ($this->_skip_by_pattern($item_name_1, $_is_dir, $pattern_include, $pattern_exclude)) {
				continue;
			}
			if ($_is_dir) {
				if (!file_exists($item_name_2)) {
					@mkdir($item_name_2, 0777);
				}
				$this->move_dir($item_name_1, $item_name_2, $pattern_include, $pattern_exclude);
			} else {
				$this->_copy_file($item_name_1, $item_name_2);
				unlink ($item_name_1);
			}
		}
		rmdir($path1);
	}

	/**
	* Try to copy file
	*/
	function _copy_file($path_from = "", $path_to = "") {
		if (!$path_from || !$path_to) {
			return false;
		}
		$result = false;
		if (!file_exists($path_from)) {
			return false;
		}
		// Quick way
		$result = @copy($path_from, $path_to);
		if (!$result) {
			$result = (bool)file_put_contents($path_to, file_get_contents($path_from));
		}
		return $result;
	}

	/**
	* Recursively delete directory structure (including subdirectories)
	*/
	function delete_dir($start_dir, $delete_start_dir = false) {
		if (!$start_dir || !file_exists($start_dir)) {
			return false;
		}
		$start_dir = rtrim($start_dir, "/");
		// Process folder contents
		$dh = opendir($start_dir);
		while (false !== ($f = readdir($dh))) {
			if ($f == "." || $f == "..") {
				continue;
			}
			$item_name = str_replace('//', '/', $start_dir."/".$f);
			@chmod($item_name, 0777);
			// Delete files immediatelly
			if (is_file($item_name)) {
				@unlink($item_name);
			// Store folders to delete in stack and try to delete sub items
			} elseif (is_dir($item_name)) {
				$this->delete_dir ($item_name);
				$sub_dirs_list[] = $item_name;
			}
		}
		@closedir($dh);
		// Now try to delete sub folders
		foreach ((array)$sub_dirs_list as $dir_name) {
			@rmdir($dir_name);
		}
		// Do delete start dir if needed
		if ($delete_start_dir) {
			@rmdir($start_dir);
		}
	}

	/**
	* Delete files in specified dir recursively using patterns
	*/
	function delete_files ($start_dir, $pattern_include = "", $pattern_exclude = "") {
		foreach ((array)$this->scan_dir($start_dir, 1, $pattern_include, $pattern_exclude) as $file_path) {
			unlink($file_path);
		}
	}

	/**
	* Recursively chmod directory structure (including subdirectories)
	*/
	function chmod_dir($start_dir, $new_mode = 0755, $pattern_include = "", $pattern_exclude = "") {
		if (!$start_dir || !file_exists($start_dir) || empty($new_mode)) {
			return false;
		}
		$start_dir = rtrim($start_dir, "/");

		$dh = opendir($start_dir);
		while (false !== ($f = readdir($dh))) {
			if ($f == "." || $f == "..") {
				continue;
			}
			$item_name = $start_dir."/".$f;
			$_is_dir	= is_dir($item_name);
			// Check patterns
			if ($this->_skip_by_pattern($item_name, $_is_dir, $pattern_include, $pattern_exclude)) {
				continue;
			}
			chmod($item_name, $new_mode);
			if ($_is_dir) {
				$this->chmod_dir ($item_name, $new_mode);
			}
		}
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
	function mkdir_m($dir_name, $dir_mode = 0755, $create_index_htmls = 0, $start_folder = "") {
		if (!$dir_name || !strlen($dir_name)) {
			return 0;
		}
		$dir_name = rtrim(str_replace(array("\\", "//"), "/", $dir_name), "/");
		// Default dir mode
		if (empty($dir_mode)) {
			$dir_mode = 0777;
		}
		// Use native recursive function if availiable
		if (version_compare(PHP_VERSION, '5.0.0', '>') && !$create_index_htmls) {
			if (file_exists($dir_name)) {
				return true;
			}
			return mkdir($dir_name, $dir_mode, true);
		}
		$old_mask = umask(0);
		// Default start folder to look at
		if (!strlen($start_folder)) {
			$start_folder = INCLUDE_PATH;
		}
		$start_folder	= str_replace(array("\\", "//"), "/", realpath($start_folder)."/");
		// Process given file name
		if (!file_exists($dir_name)) {
			$base_path = OS_WINDOWS ? "" : "/";
			preg_match_all('/([^\/]+)\/?/i', $dir_name, $atmp);
			foreach ((array)$atmp[0] as $val) {
				$base_path = $base_path. $val;
				// Skip paths while we are out of base_folder
				if (!empty($start_folder) && false === strpos($base_path, $start_folder)) {
					continue;
				}
				// Skip if already exists
				if (file_exists($base_path)) {
					continue;
				} elseif ($this->CHECK_IF_WRITABLE && !is_writable(dirname($base_path))) {
					trigger_error("DIR: directory \"".dirname($base_path)."\" is not writable", E_USER_WARNING);
				}
				// Try to create sub dir
				if (!mkdir($base_path, $dir_mode)) {
					trigger_error("DIR: Cannot create \"".$base_path."\"", E_USER_WARNING);
					return -1;
				}
				chmod($base_path, $dir_mode);
			}
		} elseif (!is_dir($dir_name)) {
			trigger_error("DIR: ".$dir_name." exists and is not a directory", E_USER_WARNING);
			return -2;
		}
		// Create empty index.html in new folder if needed
		if ($create_index_htmls) {
			$index_file_path = $dir_name. "/index.html";
			if (!file_exists($index_file_path)) {
				file_put_contents($index_file_path, "");
			}
		}
		umask($old_mask);
		return 0;
	}

	/**
	* Get values from the multi-dimensional array
	* 
	* @access	public
	* @param	$ary	array	Array to process
	* @return	array			Flat array of values
	*/
	function array_values_recursive($ary) {
		$lst = array();
		foreach (array_keys((array)$ary) as $k) {
			$v = $ary[$k];
			if (is_scalar($v)) {
				$lst[] = $v;
			} elseif (is_array($v)) {
				$lst = array_merge($lst, $this->array_values_recursive($v));
			}
		}
		return $lst;
	}

	/**
	* generate user upload path and if need make generated dirs
	* 
	* @code
	* $user_id = 123456789;
	* 
	* // generate only path
	* $dir = _class("dir")->_gen_dir_path($user_id);
	* // $dir == "123/456/789/";
	* 
	* // generate only full path
	* $dir = _class("dir")->_gen_dir_path($user_id,INCLUDE_PATH);
	* // $dir == INCLUDE_PATH."123/456/789/";
	* 
	* // generate full path and make dirs "123/456/789/"
	* $dir = _class("dir")->_gen_dir_path($user_id,INCLUDE_PATH,true);
	* // $dir == INCLUDE_PATH."123/456/789/";
	* 
	* // generate full path and make dirs "123/456/789/" with permissions 0644
	* $dir = _class("dir")->_gen_dir_path($user_id,INCLUDE_PATH,true,0644);
	* // $dir == INCLUDE_PATH."123/456/789/";
	* @endcode
	* @param $id user id
	* @param $path path to main dir
	* @param $make bool if create directories need
	* @param $dir_mode mode of the new dirs (octal)
	* @param $create_index_htmls bool Create index.html's in every new folder or not
	* @return user uploads path
	* @private
	*/
	function _gen_dir_path($id, $path = "", $make = false, $dir_mode = 0755, $create_index_htmls = 1) {
		// Make 3-level dir path
		$dirs = sprintf("%09s",$id);
		$dir3 = substr($dirs,-3,3);
		$dir2 = substr($dirs,-6,3);
		$dir1 = substr($dirs,0,-6);
		// 3-level path
		$mpath = $dir1."/".$dir2."/".$dir3."/";
		// Add path prefix to string
		if (strlen($path) > 0) {
			// if last char in $path not "\" or "/" add "/"
			if((substr($path,-1,1) !== "/") && (substr($path,-1,1) !== "\\")) {
				$path .= "/";
			}
			$mpath = $path. $mpath;
		}
		// Do create subdirs (if needed)
		if ($make) {
			$this->mkdir_m($mpath, $dir_mode, $create_index_htmls);
		}
		return $mpath;
	}

	/**
	* Cross-OS make symlink method
	*/
	function mklink($target, $link) {
		// Required fixes for trailink slash
		$target = rtrim(str_replace("\\", "/", $target), "/");
		$link	= rtrim(str_replace("\\", "/", $link), "/");
		// Check required syuff
		if (!strlen($target) || !strlen($link) || !file_exists($target)) {
			return false;
		}
		if (function_exists('symlink')) {
			return symlink($target, $link);
		}
		return false;
	}

	/**
	* This function searches given folder(folders) for provided text using paths include/exclude patterns
	*
	* @return array of found files
	*/
	function search($start_dirs, $pattern_include = "", $pattern_exclude = "", $pattern_find = "") {
		$files = array();
		if (!is_array($start_dirs)) {
			$start_dirs = array($start_dirs);
		}
		foreach ((array)$start_dirs as $_dir_name) {
			foreach ((array)$this->scan_dir($_dir_name, 1, $pattern_include, $pattern_exclude) as $_file_path) {
				$files[] = $_file_path;
			}
		}
		if (strlen($pattern_find)) {
			foreach ((array)$files as $_id => $_file_path) {
				$contents = file_get_contents($_file_path);
				if (!preg_match($pattern_find, $contents)) {
					unset($files[$_id]);
					continue;
				}
			}
		}
		return $files;
	}

	/**
	* This function searches given folder(folders) for provided text using paths include/exclude patterns
	* and replaces by pattern
	*
	* WARNING! Be careful here, it really overwrites files matches $pattern_replace (if not null)
	* 	test first with $this-search() method
	*
	* @return array of processed files
	*/
	function replace($start_dirs, $pattern_include = "", $pattern_exclude = "", $pattern_find = "", $pattern_replace = null) {
		$files = array();
		if (!is_array($start_dirs)) {
			$start_dirs = array($start_dirs);
		}
		foreach ((array)$start_dirs as $_dir_name) {
			foreach ((array)$this->scan_dir($_dir_name, 1, $pattern_include, $pattern_exclude) as $_file_path) {
				$files[] = $_file_path;
			}
		}
		foreach ((array)$files as $_id => $_file_path) {
			$contents = file_get_contents($_file_path);
			if (!$pattern_find || is_null($pattern_replace)) {
				unset($files[$_id]);
				continue;
			}
			if (!preg_match($pattern_find, $contents)) {
				unset($files[$_id]);
				continue;
			}
			$contents = preg_replace($pattern_find, $pattern_replace, $contents);
			file_put_contents($_file_path, $contents);
		}
		return $files;
	}

	/**
	* Implementation of the UNIX "tail" command on pure PHP, memory safe on huge files
	*/
	function tail($file, $lines = 10) {
		if (!$file || !file_exists($file)) {
			return false;
		}
		$handle = fopen($file, "r");
		$linecounter = $lines;
		$pos = -2;
		$beginning = false;
		$text = array();
		while ($linecounter > 0) {
			$t = " ";
			while ($t != "\n") {
				if (fseek($handle, $pos, SEEK_END) == -1) {
					$beginning = true; 
					break; 
				}
				$t = fgetc($handle);
				$pos--;
			}
			$linecounter--;
			if ($beginning) {
				rewind($handle);
			}
			$text[$lines - $linecounter - 1] = fgets($handle);
			if ($beginning) {
				break;
			}
		}
		fclose ($handle);
		return array_reverse($text);
	}
}
