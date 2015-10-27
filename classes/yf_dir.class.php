<?php

/**
* Filesystem utils
*
* Benchmark results of the methods, from SLOW to FASTEST:
* 1) _class("dir")->scan() | time: 0.46 | mem: 175824 | peakmem: 4467496 | found: 8
* 2) _class("dir")->riterate() | time: 0.306 | mem: 2288 | peakmem: 4489568 | found: 8
* 3) _class("dir")->scan_fast() | time: 0.176 | mem: 2352 | peakmem: 4489568 | found: 8
* 4) _class("dir")->rglob() | time: 0.066 | mem: 2256 | peakmem: 4489568 | found: 8
* 5) _class("dir")->find() | time: 0.058 | mem: 2232 | peakmem: 4489568 | found: 8    = fastest so far
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_dir {

	/** @var bool */
	public $CHECK_IF_READABLE = true;
	/** @var bool */
	public $CHECK_IF_WRITABLE = true;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Scan dir using shell find = so far, fastest method
	*/
	function find($folder, $pattern = '*') {
		return explode("\n", trim(shell_exec('find -L '.escapeshellarg($folder).' -iname '.escapeshellarg($pattern))));
	}

	/**
	* Recursive glob(). Note that glob and rglob does not search hidden files (starting from dot on linux/unix)
	*/
	function rglob($folder, $pattern = '*') {
		$folder = rtrim($folder, '/');
		// http://php.net/sql_regcase   !Warning! This function has been DEPRECATED as of PHP 5.3.0. Relying on this feature is highly discouraged.
		if (false === strpos($pattern, '[')) {
			$pattern = sql_regcase($pattern);
		}
		$files = (array)glob($folder.'/'.$pattern, GLOB_BRACE|GLOB_NOSORT);
		$dirs = (array)glob($folder.'/*', GLOB_BRACE|GLOB_ONLYDIR|GLOB_NOSORT);
		// Dotted dirs
		foreach (glob($folder.'/.**', GLOB_BRACE|GLOB_ONLYDIR|GLOB_NOSORT) as $path) {
			$d = basename($path);
			if ($d === '.' || $d === '..' || $d === '.git' || $d === '.svn') {
				continue;
			}
			$dirs[] = $path;
		}
		$func = __FUNCTION__;
		foreach ((array)$dirs as $dir) {
			$files = array_merge($files, $this->$func($dir, $pattern));
		}
		return $files;
	}

	/**
	* Fast implementation with old functions opendir/readdir
	*/
	function scan_fast($start_dir, $pattern = '~.+~') {
		$files = array();
		$dh	= @opendir($start_dir);
		if (!$dh) {
			return $files;
		}
		$func = __FUNCTION__;
		while (false !== ($f = readdir($dh))) {
			if ($f === '.' || $f === '..') {
				continue;
			}
			$item = $start_dir.'/'.$f;
			if (is_dir($item)) {
				$files = array_merge($files, $this->$func($item, $pattern));
			} elseif (is_file($item) && (!$pattern || preg_match($pattern, $item))) {
				$files[] = $item;
			}
		}
		closedir($dh);
		return $files;
	}

	/**
	* Recursive folder search, based on RecursiveDirectoryIterator 
	*/
	function riterate($folder, $pattern = '~.+~') {
		$out = array();
		$flags = FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::FOLLOW_SYMLINKS;
		foreach(new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, $flags)), $pattern, RegexIterator::GET_MATCH) as $path => $f) {
			$out[] = $path;
		}
		return $out;
	}

	/**
	* Alias
	*/
	function scan($start_dir, $_tmp = true, $pattern_include = '', $pattern_exclude = '') {
		return $this->scan_dir($start_dir, $_tmp, $pattern_include, $pattern_exclude);
	}

	/**
	* Recursively scanning directory structure (including subdirectories) //
	*/
	function scan_dir($start_dir, $_tmp = 1, $pattern_include = '', $pattern_exclude = '') {
		$func = __FUNCTION__;
		// Here we accept several start folders, result will be merged
		if (is_array($start_dir)) {
			foreach ((array)$start_dir as $_dir_name) {
				foreach ((array)$this->$func($_dir_name, 1, $pattern_include, $pattern_exclude) as $_file_path) {
					$_files[] = $_file_path;
				}
			}
			return $_files;
		}
		if (!$start_dir || !file_exists($start_dir)) {
			return false;
		}
		$start_dir = rtrim($start_dir, '/');
		$files	= array();
		$dh		= opendir($start_dir);
		while (false !== ($f = readdir($dh))) {
			if ($f == '.' || $f == '..') {
				continue;
			}
			$item		= $start_dir.'/'.$f;
			$_is_dir	= is_dir($item);
			if ($this->_skip_by_pattern($item, $_is_dir, $pattern_include, $pattern_exclude)) {
				continue;
			}
			if ($_is_dir) {
				$files = array_merge($files, $this->$func($item, 1, $pattern_include, $pattern_exclude));
			} elseif (is_file($item)) {
				$files[] = $item;
			}
		}
		closedir($dh);
		return $files;
	}

	/**
	* Alias
	*/
	function size($start_dir, $pattern_include = '', $pattern_exclude = '') {
		return $this->dirsize($start_dir, $pattern_include, $pattern_exclude);
	}

	/**
	* This function calculate directory size
	*/
	function dirsize($start_dir, $pattern_include = '', $pattern_exclude = '') {
		if (!$start_dir || !file_exists($start_dir)) {
			return false;
		}
		$start_dir = rtrim($start_dir, '/');

		$dh = opendir($start_dir);
		$size = 0;
		while (($f = readdir($dh)) !== false) {
			if ($f == '.' || $f == '..') {
				continue;
			}
			$path = $start_dir.'/'.$f;
			$_is_dir	= is_dir($path);
			// Check patterns
			if ($this->_skip_by_pattern($path, $_is_dir, $pattern_include, $pattern_exclude)) {
				continue;
			}
			if ($_is_dir) {
				$size += $this->dirsize($path.'/', $pattern_include, $pattern_exclude);
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
	function count_files($start_dir, $pattern_include = '', $pattern_exclude = '') {
		if (!$start_dir || !file_exists($start_dir)) {
			return false;
		}
		$start_dir = rtrim($start_dir, '/');

		$dh = opendir($start_dir);
		$num_files = 0;
		while (($f = readdir($dh)) !== false) {
			if ($f == '.' || $f == '..') {
				continue;
			}
			$path = $start_dir.'/'.$f;
			$_is_dir	= is_dir($path);
			// Check patterns
			if ($this->_skip_by_pattern($path, $_is_dir, $pattern_include, $pattern_exclude)) {
				continue;
			}
			if ($_is_dir) {
				$num_files += $this->count_files($path.'/', $pattern_include, $pattern_exclude);
			} elseif (is_file($path)) {
				$num_files++;
			}
		}
		closedir($dh);
		return $num_files;
	}

	/**
	* Alias
	*/
	function copy($path1, $path2, $pattern_include = '', $pattern_exclude = '', $level = null) {
		return $this->copy_dir($path1, $path2, $pattern_include, $pattern_exclude, $level);
	}

	/**
	* This function recursively copies contents of source directory to destination
	*/
	function copy_dir($path1, $path2, $pattern_include = '', $pattern_exclude = '', $level = null) {
		if (!$path1 || !file_exists($path1)) {
			return false;
		}
		$func = __FUNCTION__;
		$path1 = rtrim(str_replace("\\", '/', realpath($path1)), '/');
		$path2 = rtrim(str_replace("\\", '/', realpath($path2)), '/');

		$dh = opendir($path1);
		$old_mask = umask(0);
		if (!file_exists($path2)) {
			$this->mkdir_m($path2);
		}
		while (false !== ($f = readdir($dh))) {
			if ($f == '.' || $f == '..') {
				continue;
			}
			$item_1 = $path1.'/'.$f;
			$item_2 = $path2.'/'.$f;
			$_is_dir	= is_dir($item_1);
			// Check patterns
			if ($this->_skip_by_pattern($item_1, $_is_dir, $pattern_include, $pattern_exclude)) {
				continue;
			}
			if ($_is_dir) {
				if (!file_exists($item_2)) {
					$this->mkdir_m($item_2);
				}
				if (is_null($level) || $level > 0) {
					$this->$func($item_1, $item_2, $pattern_include, $pattern_exclude, is_null($level) ? $level : $level - 1);
				}
			} else {
				$this->_copy_file($item_1, $item_2);
			}
		}
		umask($old_mask);
	}

	/**
	* Alias
	*/
	function move($path1, $path2, $pattern_include = '', $pattern_exclude = '') {
		return $this->move_dir($path1, $path2, $pattern_include, $pattern_exclude);
	}

	/**
	* This function recursively move contents of source directory to destination
	*/
	function move_dir($path1, $path2, $pattern_include = '', $pattern_exclude = '') {
		if (!$path1 || !file_exists($path1)) {
			return false;
		}
		$func = __FUNCTION__;
		$path1 = rtrim($path1, '/');
		$path2 = rtrim($path2, '/');

		$dh = opendir($path1);
		if (!file_exists($path2)) {
			mkdir($path2, 0777);
		}
		while (false !== ($f = readdir($dh))) {
			if ($f == '.' || $f == '..') {
				continue;
			}
			$item_1 = $path1.'/'.$f;
			$item_2 = $path2.'/'.$f;
			$_is_dir	= is_dir($item_1);
			// Check patterns
			if ($this->_skip_by_pattern($item_1, $_is_dir, $pattern_include, $pattern_exclude)) {
				continue;
			}
			if ($_is_dir) {
				if (!file_exists($item_2)) {
					mkdir($item_2, 0777);
				}
				$this->$func($item_1, $item_2, $pattern_include, $pattern_exclude);
			} else {
				$this->_copy_file($item_1, $item_2);
				unlink ($item_1);
			}
		}
		rmdir($path1);
	}

	/**
	* Try to copy file
	*/
	function _copy_file($path_from = '', $path_to = '') {
		if (!$path_from || !$path_to) {
			return false;
		}
		$result = false;
		if (!file_exists($path_from)) {
			return false;
		}
		// Quick way
		$result = copy($path_from, $path_to);
		if (!$result) {
			$result = (bool)file_put_contents($path_to, file_get_contents($path_from));
		}
		return $result;
	}

	/**
	* Alias
	*/
	function delete($start_dir, $delete_start_dir = false) {
		return $this->delete_dir($start_dir, $delete_start_dir);
	}

	/**
	* Recursively delete directory structure (including subdirectories)
	*/
	function delete_dir($start_dir, $delete_start_dir = false) {
		if (!$start_dir || !file_exists($start_dir)) {
			return false;
		}
		$func = __FUNCTION__;
		$start_dir = rtrim($start_dir, '/');
		// Process folder contents
		$dh = opendir($start_dir);
		while (false !== ($f = readdir($dh))) {
			if ($f == '.' || $f == '..') {
				continue;
			}
			$item = str_replace('//', '/', $start_dir.'/'.$f);
			chmod($item, 0777);
			// Delete files immediatelly
			if (is_file($item)) {
				unlink($item);
			// Store folders to delete in stack and try to delete sub items
			} elseif (is_dir($item)) {
				$this->$func($item);
				$sub_dirs_list[] = $item;
			}
		}
		closedir($dh);
		// Now try to delete sub folders
		foreach ((array)$sub_dirs_list as $dir_name) {
			rmdir($dir_name);
		}
		// Do delete start dir if needed
		if ($delete_start_dir) {
			rmdir($start_dir);
		}
	}

	/**
	* Delete files in specified dir recursively using patterns
	*/
	function delete_files($start_dir, $pattern_include = '', $pattern_exclude = '') {
		foreach ((array)$this->scan_dir($start_dir, 1, $pattern_include, $pattern_exclude) as $file_path) {
			unlink($file_path);
		}
	}

	/**
	* Alias
	*/
	function chmod($start_dir, $new_mode = 0755, $pattern_include = '', $pattern_exclude = '') {
		return $this->chmod_dir($start_dir, $new_mode, $pattern_include, $pattern_exclude);
	}

	/**
	* Recursively chmod directory structure (including subdirectories)
	*/
	function chmod_dir($start_dir, $new_mode = 0755, $pattern_include = '', $pattern_exclude = '') {
		if (!$start_dir || !file_exists($start_dir) || empty($new_mode)) {
			return false;
		}
		$func = __FUNCTION__;
		$start_dir = rtrim($start_dir, '/');

		$dh = opendir($start_dir);
		while (false !== ($f = readdir($dh))) {
			if ($f == '.' || $f == '..') {
				continue;
			}
			$item = $start_dir.'/'.$f;
			$_is_dir	= is_dir($item);
			// Check patterns
			if ($this->_skip_by_pattern($item, $_is_dir, $pattern_include, $pattern_exclude)) {
				continue;
			}
			chmod($item, $new_mode);
			if ($_is_dir) {
				$this->$func($item, $new_mode);
			}
		}
	}

	/**
	* Alias
	*/
	function mkdir($dir_name, $dir_mode = 0755, $create_index_htmls = 0, $start_folder = '') {
		return $this->mkdir_m($dir_name, $dir_mode, $create_index_htmls, $start_folder);
	}

	/**
	* Create multiple dirs at one time (eg. mkdir_m('some_dir1/some_dir2/some_dir3'))
	* 
	* @access	public
	* @param	$dir_name			string
	* @param	$dir_mode			octal
	* @param	$create_index_htmls	bool
	* @param	$start_folder		string
	* @return	int		Status code
	*/
	function mkdir_m($dir_name, $dir_mode = 0755, $create_index_htmls = 0, $start_folder = '') {
		if (!$dir_name || !strlen($dir_name)) {
			return 0;
		}
		$dir_name = rtrim(str_replace(array("\\", '//'), '/', $dir_name), '/');
		// Default dir mode
		if (empty($dir_mode)) {
			$dir_mode = 0777;
		}
		// Use native recursive function if applicable
		if (!$create_index_htmls) {
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
		$start_folder	= str_replace(array("\\", '//'), '/', realpath($start_folder).'/');
		// Process given file name
		if (!file_exists($dir_name)) {
			$base_path = OS_WINDOWS ? '' : '/';
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
					trigger_error('DIR: directory: '.dirname($base_path).' is not writable', E_USER_WARNING);
				}
				// Try to create sub dir
				if (!mkdir($base_path, $dir_mode)) {
					trigger_error('DIR: Cannot create: '.$base_path, E_USER_WARNING);
					return -1;
				}
				chmod($base_path, $dir_mode);
			}
		} elseif (!is_dir($dir_name)) {
			trigger_error('DIR: '.$dir_name.' exists and is not a directory', E_USER_WARNING);
			return -2;
		}
		// Create empty index.html in new folder if needed
		if ($create_index_htmls) {
			$index_file_path = $dir_name. '/index.html';
			if (!file_exists($index_file_path)) {
				file_put_contents($index_file_path, '');
			}
		}
		umask($old_mask);
		return 0;
	}

	/**
	* generate user upload path and if need make generated dirs
	* 
	* @code
	* $user_id = 123456789;
	* 
	* // generate only path
	* $dir = _class('dir')->_gen_dir_path($user_id);
	* // $dir == '123/456/789/';
	* 
	* // generate only full path
	* $dir = _class('dir')->_gen_dir_path($user_id,INCLUDE_PATH);
	* // $dir == INCLUDE_PATH.'123/456/789/';
	* 
	* // generate full path and make dirs '123/456/789/'
	* $dir = _class('dir')->_gen_dir_path($user_id,INCLUDE_PATH,true);
	* // $dir == INCLUDE_PATH.'123/456/789/';
	* 
	* // generate full path and make dirs '123/456/789/' with permissions 0644
	* $dir = _class('dir')->_gen_dir_path($user_id,INCLUDE_PATH,true,0644);
	* // $dir == INCLUDE_PATH.'123/456/789/';
	* @endcode
	* @param $id user id
	* @param $path path to main dir
	* @param $make bool if create directories need
	* @param $dir_mode mode of the new dirs (octal)
	* @param $create_index_htmls bool Create index.html's in every new folder or not
	* @return user uploads path
	* @private
	*/
	function _gen_dir_path($id, $path = '', $make = false, $dir_mode = 0755, $create_index_htmls = 1) {
		// Make 3-level dir path
		$dirs = sprintf('%09s',$id);
		$dir3 = substr($dirs,-3,3);
		$dir2 = substr($dirs,-6,3);
		$dir1 = substr($dirs,0,-6);
		// 3-level path
		$mpath = $dir1.'/'.$dir2.'/'.$dir3.'/';
		// Add path prefix to string
		if (strlen($path) > 0) {
			// if last char in $path not '\' or '/' add '/'
			if((substr($path,-1,1) !== '/') && (substr($path,-1,1) !== "\\")) {
				$path .= '/';
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
		$target = rtrim(str_replace("\\", '/', $target), '/');
		$link	= rtrim(str_replace("\\", '/', $link), '/');
		// Check required stuff
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
	function search($start_dirs, $pattern_include = '', $pattern_exclude = '', $pattern_find) {
		if (!is_array($start_dirs)) {
			$start_dirs = array($start_dirs);
		}
		if (!$pattern_find) {
			return false;
		}
		if (!is_array($pattern_find)) {
			$pattern_find = array($pattern_find);
		}
		$files = array();
		foreach ((array)$start_dirs as $_dir_name) {
			foreach ((array)$this->scan_dir($_dir_name, 1, $pattern_include, $pattern_exclude) as $_file_path) {
				$files[] = $_file_path;
			}
		}
		$files_matched = array();
		foreach ((array)$files as $_id => $_file_path) {
			$contents = file_get_contents($_file_path);
			foreach ((array)$pattern_find as $p_find) {
				if (preg_match($p_find, $contents)) {
					$files_matched[$_id] = $files[$_id];
					continue;
				}
			}
		}
		return $files_matched;
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
	function replace($start_dirs, $pattern_include = '', $pattern_exclude = '', $pattern_find, $pattern_replace) {
		$files = array();
		if (!is_array($start_dirs)) {
			$start_dirs = array($start_dirs);
		}
		if (!$pattern_find || !isset($pattern_replace)) {
			return false;
		}
		if (!is_array($pattern_find)) {
			$pattern_find = array($pattern_find => $pattern_replace);
		}
		foreach ((array)$start_dirs as $_dir_name) {
			foreach ((array)$this->scan_dir($_dir_name, 1, $pattern_include, $pattern_exclude) as $_file_path) {
				$files[] = $_file_path;
			}
		}
		foreach ((array)$files as $_id => $_file_path) {
			$contents = file_get_contents($_file_path);
			$what = array();
			foreach ((array)$pattern_find as $p_find => $p_replace) {
				if (preg_match($p_find, $contents)) {
					$what[$p_find] = $p_replace;
				}
			}
			// This needed to not log/touch/override files that have no matches
			if (!$what) {
				unset($files[$_id]);
				continue;
			}
			$contents_new = preg_replace(array_keys($what), array_values($what), $contents);
			if ($contents_new !== $contents) {
				file_put_contents($_file_path, $contents_new);
			}
		}
		return $files;
	}

	/**
	*/
	function grep($pattern_find, $start_dirs, $pattern_path = '*', $extra = array()) {
		if (!$pattern_find) {
			return false;
		}
		if (!$start_dirs) {
			$start_dirs = APP_PATH;
		}
		if (!is_array($start_dirs)) {
			$start_dirs = array($start_dirs);
		}
		if (!is_array($pattern_find)) {
			$pattern_find = array($pattern_find);
		}
		$files = array();
		foreach ((array)$start_dirs as $start_dir) {
			$start_dir = rtrim($start_dir, '/');
			foreach ((array)$this->rglob($start_dir, $pattern_path) as $path) {
				$files[] = $path;
			}
		}
		$matched = array();
		foreach ((array)$files as $_id => $path) {
			if (isset($extra['exclude_paths']) && wildcard_compare($extra['exclude_paths'], $path)) {
				continue;
			}
			$contents = file_get_contents($path);
			foreach ((array)$pattern_find as $p_find) {
				if (preg_match_all($p_find, $contents, $m)) {
					$matched[$files[$_id]] = $extra['return_match'] && isset($m[$extra['return_match']]) ? $m[$extra['return_match']] : $m[0];
					continue;
				}
			}
		}
		return $matched;
	}

	/**
	* Implementation of the UNIX 'tail' command on pure PHP, memory safe on huge files
	*/
	function tail($file, $lines = 10) {
		if (!$file || !file_exists($file)) {
			return false;
		}
		$handle = fopen($file, 'r');
		$linecounter = $lines;
		$pos = -2;
		$beginning = false;
		$text = array();
		while ($linecounter > 0) {
			$t = ' ';
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

	/**
	* Check if we need to skip current path according to given patterns (unified method for whole dir module)
	*/
	function _skip_by_pattern($path = '', $_is_dir = false, $pattern_include = '', $pattern_exclude = '') {
		if (!$path) {
			return false;
		}
		if (!$pattern_include && !$pattern_exclude) {
			return false;
		}
		$_path_clean = trim(str_replace('//', '/', str_replace("\\", '/', $path)));
		// Include files only if they match the mask
		$_index = $_is_dir ? 0 : 1;
		if ($_is_dir) {
			$_path_clean	= rtrim($_path_clean, '/');
		}
		if (is_array($pattern_include)) {
			$pattern_include = $pattern_include[$_index];
		}
		if (is_array($pattern_exclude)) {
			$pattern_exclude = $pattern_exclude[$_index];
		}
		$MATCHED = false;
		if (!empty($pattern_include) && is_string($pattern_include)) {
			// Examples: "-f /\.(jpg|png)$/", -d /some_dir/
			$try_modifier = substr($pattern_include, 0, 3);
			if (in_array($try_modifier, array('-f ', '-d '))) {
				$pattern_include = substr($pattern_include, 3);
				$modifier = $try_modifier;
			}
			if (strlen($pattern_include) == 2 && $pattern_include{0} == '-') {
				if ($pattern_include == '-d' && !$_is_dir) {
					$MATCHED = true;
				} elseif ($pattern_include == '-f' && $_is_dir) {
					$MATCHED = true;
				}
			} else {
				$need_match = true;
				if ($modifier == '-f ' && $_is_dir) {
					$need_match = false;
				} elseif ($modifier == '-d ' && !$_is_dir) {
					$need_match = false;
				}
				if ($need_match && !preg_match($pattern_include.'ims', $_path_clean)) {
					$MATCHED = true;
				}
			}
		}
		// Exclude files from list by mask
		if (!empty($pattern_exclude) && is_string($pattern_exclude)) {
			// Examples: "-f /\.(jpg|png)$/", -d /some_dir/
			$try_modifier = substr($pattern_include, 0, 3);
			if (in_array($try_modifier, array('-f ', '-d '))) {
				$pattern_include = substr($pattern_include, 3);
				$modifier = $try_modifier;
			}
			if (strlen($pattern_exclude) == 2 && $pattern_exclude{0} == '-') {
				if ($pattern_exclude == '-d' && $_is_dir) {
					$MATCHED = true;
				} elseif ($pattern_exclude == '-f' && !$_is_dir) {
					$MATCHED = true;
				}
			} else {
				$need_match = true;
				if ($modifier == '-f ' && $_is_dir) {
					$need_match = false;
				} elseif ($modifier == '-d ' && !$_is_dir) {
					$need_match = false;
				}
				if ($need_match && preg_match($pattern_exclude.'ims', $_path_clean)) {
					$MATCHED = true;
				}
			}
		}
		return $MATCHED;
	}
}
