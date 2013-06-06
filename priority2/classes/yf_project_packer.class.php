<?php

/**
* Project packer methods
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_project_packer {

	/** @var bool @conf_skip */
	var $ADD_SOURCE_FILE_NAMES = 1;
	/** @var @conf_skip */
	var $_skip_files = array(
		"project_compiled_admin.php",
		"project_compiled_user.php",
		"framework_compiled_admin.php",
		"framework_compiled_user.php",
		"board_settings.php",
		"cache_rules.php",
		"common_code.php",
		"common_vars.php",
		"db_setup.php",
		"project_conf.php",
		"reviews_arrays.php",
		"smtp_config.php",
		"fast_init.php",
	);
	/** @var @conf_skip */
	var $_skip_files_preg = array(
		"/stpls_compiled_.*/"
	);
	/** @var array @conf_skip */
	var $_def_patterns = array(
		"include_php"	=> array("#\/(admin_modules|modules|classes)#", "#\.class\.php\$#"),
		"exclude_php"	=> array("#(svn|git)|\/(chat|forum|gallery)#", "#|__locale__#"),
		"include_stpl"	=> array("#\/(templates)#", "#\.stpl\$#"),
		"exclude_stpl"	=> array("#(svn|git)|\/(chat|forum|gallery)#", "#svn#"),
	);
	/** @var @conf_skip */
	var $_include_php_fwork_user = array(
		"/(bb_codes|cache|captcha|cats|common|custom_meta_info|db|dir|errors|graphics|installer|locale|logs|main|module|output_cache|se_keywords|sites_info|spider_detect|task_manager|tpl|unicode_funcs)\.class\.php\$/i",
		"/(auth_user|client_utils|utf8_clean|rewrite|l10n|db_mysql41|divide_pages|user_data|site_nav_bar)\.class\.php\$/i",
		"/(common_funcs|data_handlers)\.php\$/i",
	);
	/** @var @conf_skip */
	var $_include_php_fwork_admin = array();
	/** @var @conf_skip */
	var $_include_php_proj_user = array(
		"/(modules|classes)\/(.*?)\.class\.php\$/i",
	);
	/** @var @conf_skip */
	var $_include_php_proj_admin = array();
	/** @var bool @conf_skip */
	var $DEL_COMPRESSED_AFTER_COMPILE	= true;
	/** @var bool @conf_skip */
	var $TRY_COMPRESS_ON_COMPILE		= true;
	/** @var bool */
// TODO: connect me
	var $USE_LOCKING					= true;
	/** @var bool @conf_skip */
	var $LOCK_FILE						= "uploads/auto_packer.lock";

	/**
	* Constructor
	*/
	function _init () {
		$this->PACKER = array();
		$this->PACKER['CLASSES']			= array();
		$this->PACKER['CLASSES_PATHS']	= array();
		$this->PACKER["BUILT_IN_CLASSES"]= array();
		foreach ((array)get_declared_classes() as $class_name) {
			if (in_array($class_name, array("yf_main", "main", "profy_common", "common"))) {
				break;
			}
			$this->PACKER["BUILT_IN_CLASSES"][] = $class_name;
		}
		if (function_exists("spl_classes")) {
			foreach ((array)spl_classes() as $class_name) {
				$this->PACKER["BUILT_IN_CLASSES"][]	= $class_name;
			}
		}
		if (function_exists("get_declared_interfaces")) {
			foreach ((array)get_declared_interfaces() as $class_name) {
				$this->PACKER["BUILT_IN_CLASSES"][]	= $class_name;
			}
		}
		$this->PACKER["INHERIT_TREE"]	= array();
		$this->PACKER["INHERIT_CHILDREN"]= array();
		$this->PACKER["INHERIT_PARENTS"]	= array();
		$this->PACKER["INHERIT_TOP"]		= array();
		$this->PACKER["INHERIT_NONE"]	= array();
		$this->PACKER["ABSTRACT_CLASSES"]= array();
		$this->PACKER["INTERFACES"]		= array();
		// T_ML_COMMENT does not exist in PHP 5. The following three lines 
		// define it in order to preserve backwards compatibility.
		// The next two lines define the PHP 5 only T_DOC_COMMENT,	
		// which we will mask as T_ML_COMMENT for PHP 4.
		if (!defined('T_ML_COMMENT')) {
			define('T_ML_COMMENT', T_COMMENT);
		} else {
			define('T_DOC_COMMENT', T_ML_COMMENT);
		}
		$this->DIR_OBJ = main()->init_class("dir", "classes/");
	}

	/**
	* Auto pack PHP code by the request
	*/
// !!! EXPERIMENTAL
	function auto_pack($params = array()) {
		$PACK_PROJECT_PHP	= (bool)$params["PACK_PROJECT_PHP"];
		$PACK_PROJECT_STPLS	= (bool)$params["PACK_PROJECT_STPLS"];
		$PACK_FWORK_PHP		= (bool)$params["PACK_FWORK_PHP"];
		$PACK_FWORK_STPLS	= (bool)$params["PACK_FWORK_STPLS"];


		$base_path		= INCLUDE_PATH;
		$source_dir		= $base_path."classes/";
		$packed_dir		= INCLUDE_PATH."PHP_COMPILED/";
		$compiled_dir	= $packed_dir;
		$compiled_file	= $compiled_dir."__php_packed.php";

		$this->DIR_OBJ->delete_dir($packed_dir);

		$pattern_require = "/(require_once[\s\t]*[\"\'\(]+([^\'\"\(\)]+?)[\'\"\)]+[\s\t]*;[\s\t]*)/ims";

		$pattern_include_php	= $params["include_php"] ? $params["include_php"] : $this->_def_patterns["include_php"];
		$pattern_exclude_php	= $params["exclude_php"] ? $params["exclude_php"] : $this->_def_patterns["exclude_php"];
		$pattern_include_stpl	= $params["include_stpl"] ? $params["include_stpl"] : $this->_def_patterns["include_stpl"];
		$pattern_exclude_stpl	= $params["exclude_stpl"] ? $params["exclude_stpl"] : $this->_def_patterns["exclude_stpl"];

echo "<pre>";
		$time_start = microtime(true);

		// Get files array
		$source_dir		= INCLUDE_PATH."classes/";
		$files_to_compress = $this->DIR_OBJ->scan_dir($source_dir, true, $pattern_include_php, $pattern_exclude_php);
		foreach ((array)$files_to_compress as $cur_file_path) {
			$compressed_file_path	= $packed_dir. substr($cur_file_path, strlen($source_dir));
			$compressed_file_dir	= dirname($compressed_file_path);
			if (!file_exists($compressed_file_dir)) {
				_mkdir_m($compressed_file_dir, 0777);
			}
			// Do compress
			$this->_do_compress_php_file($cur_file_path, $compressed_file_path);
		}
		$source_dir		= INCLUDE_PATH."modules/";
		$files_to_compress = $this->DIR_OBJ->scan_dir($source_dir, true, $pattern_include_php, $pattern_exclude_php);
		foreach ((array)$files_to_compress as $cur_file_path) {
			$compressed_file_path	= $packed_dir. substr($cur_file_path, strlen($source_dir));
			$compressed_file_dir	= dirname($compressed_file_path);
			if (!file_exists($compressed_file_dir)) {
				_mkdir_m($compressed_file_dir, 0777);
			}
			// Do compress
			$this->_do_compress_php_file($cur_file_path, $compressed_file_path);
		}
// TODO: get only those files really needed from framework
/*
		$source_dir		= PF_PATH."classes/";
		$files_to_compress = $this->DIR_OBJ->scan_dir($source_dir, true, $pattern_include_php, $pattern_exclude_php);
		foreach ((array)$files_to_compress as $cur_file_path) {
			$compressed_file_path	= $packed_dir. substr($cur_file_path, strlen($source_dir));
			$compressed_file_dir	= dirname($compressed_file_path);
			if (!file_exists($compressed_file_dir)) {
				_mkdir_m($compressed_file_dir, 0777);
			}
			// Do compress
			$this->_do_compress_php_file($cur_file_path, $compressed_file_path);
		}
		$source_dir		= PF_PATH."modules/";
		$files_to_compress = $this->DIR_OBJ->scan_dir($source_dir, true, $pattern_include_php, $pattern_exclude_php);
		foreach ((array)$files_to_compress as $cur_file_path) {
			$compressed_file_path	= $packed_dir. substr($cur_file_path, strlen($source_dir));
			$compressed_file_dir	= dirname($compressed_file_path);
			if (!file_exists($compressed_file_dir)) {
				_mkdir_m($compressed_file_dir, 0777);
			}
			// Do compress
			$this->_do_compress_php_file($cur_file_path, $compressed_file_path);
		}
*/
		// Get classes names and paths
		$new_files_to_compile = array();
		foreach ((array)$this->DIR_OBJ->scan_dir($packed_dir, 1, "#.*\.(php)\$#", $exclude_pattern_user) as $cur_file_path) {
			$class_name = str_replace("/", "_", str_replace("\/", "/", substr($cur_file_path, strlen($packed_dir), -4)));
			$this->PACKER['CLASSES'][$class_name] = $class_name;
			$this->PACKER['CLASSES_PATHS'][$class_name] = $cur_file_path;
		}

		// Build inheritance tree
		foreach ((array)$this->PACKER['CLASSES'] as $class_name) {
			$text = substr(file_get_contents($this->PACKER['CLASSES_PATHS'][$class_name]), 6)."\r\n";
			$text = preg_replace($pattern_require, "", $text);
			if (preg_match("/abstract class ([a-z\_]+)/ims", $text, $m)) {
				$this->PACKER["ABSTRACT_CLASSES"][$class_name] = $class_name;
			}
			if (preg_match("/interface ([a-z\_]+)/ims", $text, $m)) {
				$this->PACKER["INTERFACES"][$class_name] = $class_name;
			}
			if (preg_match("/(class|interface) ([a-z\_]+) (extends|implements) ([a-z\_]+)/ims", $text, $m)) {
				$_child		= $m[2];
				$_parent	= $m[4];
				if (!in_array($_parent, $this->PACKER['BUILT_IN_CLASSES'])) {
					$this->PACKER["INHERIT_TREE"][$_child] = $_parent;
					$this->PACKER["INHERIT_CHILDREN"][$_parent][$_child] = $_child;
				}
			} else {
				$this->PACKER["INHERIT_NONE"][$class_name] = $class_name;
			}
		}

		// Build parents array
		foreach ((array)$this->PACKER["INHERIT_TREE"] as $_child => $_parent) {
			$this->PACKER["INHERIT_PARENTS"][$_child] = $this->_build_inherit_parents($_parent);
		}

		ksort($this->PACKER["INHERIT_NONE"]);
		ksort($this->PACKER["INHERIT_TOP"]);

		// Build result array
		$CLASSES_NEW = array();
		foreach (array_keys($this->PACKER["ABSTRACT_CLASSES"]) as $v) {
			if (!isset($CLASSES_NEW[$v])) {
				$CLASSES_NEW[$v] = $v;
			}
		}
		foreach (array_keys($this->PACKER["INTERFACES"]) as $v) {
			if (!isset($CLASSES_NEW[$v])) {
				$CLASSES_NEW[$v] = $v;
			}
		}
		foreach (array_keys($this->PACKER["INHERIT_NONE"]) as $v) {
			if (!isset($CLASSES_NEW[$v])) {
				$CLASSES_NEW[$v] = $v;
			}
		}
		foreach (array_keys($this->PACKER["INHERIT_TOP"]) as $v) {
			if (!isset($CLASSES_NEW[$v])) {
				$CLASSES_NEW[$v] = $v;
			}
		}
		foreach ((array)$this->PACKER["INHERIT_PARENTS"] as $v => $_parents) {
			foreach (array_reverse((array)$_parents, true) as $_parent => $_tmp) {
				if (!isset($CLASSES_NEW[$_parent])) {
					$CLASSES_NEW[$_parent] = $_parent;
				}
			}
			if (!isset($CLASSES_NEW[$v])) {
				$CLASSES_NEW[$v] = $v;
			}
		}
		foreach (array_keys($this->PACKER["CLASSES"]) as $v) {
			if (!isset($CLASSES_NEW[$v])) {
				$CLASSES_NEW[$v] = $v;
			}
		}
		$this->PACKER["CLASSES"] = $CLASSES_NEW;

		$output .= "<pre>";
		$output .= "<h1>INHERIT_TREE</h1>";
		$output .= print_r($this->PACKER["INHERIT_TREE"], 1);
		$output .= "<h1>INHERIT_CHILDREN</h1>";
		$output .= print_r($this->PACKER["INHERIT_CHILDREN"], 1);
		$output .= "<h1>INHERIT_PARENTS</h1>";
		$output .= print_r($this->PACKER["INHERIT_PARENTS"], 1);
		$output .= "<h1>INHERIT_TOP</h1>";
		$output .= print_r($this->PACKER["INHERIT_TOP"], 1);
		$output .= "<h1>INHERIT_NONE</h1>";
		$output .= print_r($this->PACKER["INHERIT_NONE"], 1);
		$output .= "<h1>ABSTRACT_CLASSES</h1>";
		$output .= print_r($this->PACKER["ABSTRACT_CLASSES"], 1);
		$output .= "<h1>INTERFACES</h1>";
		$output .= print_r($this->PACKER["INTERFACES"], 1);
		$output .= "<h1>RESULT_CLASSES</h1>";
		$output .= print_r($this->PACKER["CLASSES"], 1);
		$output .= "</pre>";

		//------------------------------------------
		// Finish
		//------------------------------------------
		$fh = fopen($compiled_file, "w");
		fwrite($fh, "<?php\r\n");
		$counter = 0;

		foreach ((array)$this->PACKER['CLASSES'] as $name) {
			if (!$name) {
				continue;
			}
			$cur_file_path = $this->PACKER['CLASSES_PATHS'][$name];
			if (empty($cur_file_path)) {
				$this->PACKER["NOT_FOUND"][$name] = $name;
				continue;
			}
			$cur_file_text = file_get_contents($cur_file_path);
			$text = substr($cur_file_text, 6)."\r\n";
			$text = preg_replace($pattern_require, "", $text);
			if (!strlen($text)) {
				continue;
			}
			if ($this->ADD_SOURCE_FILE_NAMES) {
				$text = "// source: ".$cur_file_path."\r\n".$text;
			}
			fwrite($fh, $text);
			$counter++;

			$output .= $name." # ".$cur_file_path." <b>".strlen($text)."</b><br />";
		}
		fclose($fh);

		$output .= "<pre style='color:red;'>\n";
		$output .= "<h1>NOT_FOUND_CLASSES</h1>\n";
		$output .= print_r($this->PACKER["NOT_FOUND"], 1);
		$output .= "</pre>\n";

		// prepare output info
		$output2 .= "\r\n<br /><b>".$counter."</b> files from dir \"".$source_dir."\"<br />\r\n compiled into result file \"".$compiled_file."\"<br />\r\n";
		$output2 .= "result file size: <b>".filesize($compiled_file)."</b> bytes<br />\r\n";
		$output2 .= "Generation time: <b>".round(microtime(true) - $time_start, 3)." secs</b>\r\n";
		$output = $output2. $output;

		echo $output;
	}

	/**
	* Do pack project
	*/
	function go() {
//		$body .= $this->_compress_project();
//		$body .= $this->_compress_framework();
//		$body .= $this->_compile_project();
//		$body .= $this->_compile_framework();

// TODO: add additional packing of the "__locale__ru.php" files
//		return $body;
	}

	/**
	* Prepare path for the manipulations
	*/
	function _prepare_path($path = "") {
		if (is_array($path)) {
			foreach ((array)$path as $k => $v) {
				$path[$k] = $this->_prepare_path($v);
			}
			return $path;
		}
		return str_replace("//", "/", str_replace("\\", "/", $path));
	}

	/**
	* Do pack project
	*/
	function _compress_project() {
		$params = array(
			"source_dir"	=> INCLUDE_PATH,
			"compressed_dir"=> INCLUDE_PATH."PROJ_COMPRESSED/",
			"only_these_php"=> $this->_include_php_proj_user,
		);
		return $this->_compress_source_dir($params);
	}

	/**
	* Do pack framework
	*/
	function _compress_framework() {
		$params = array(
			"source_dir"	=> PF_PATH,
			"compressed_dir"=> INCLUDE_PATH."PF_COMPRESSED/",
			"only_these_php"=> $this->_include_php_fwork_user,
		);
		return $this->_compress_source_dir($params);
	}

	/**
	* Do compile project files
	*/
	function _compile_project() {
		$source_dir		= $this->_prepare_path(INCLUDE_PATH."PROJ_COMPRESSED/");
		$compiled_dir	= $this->_prepare_path(INCLUDE_PATH."PHP_COMPILED/");

		if (!file_exists($source_dir) && $this->TRY_COMPRESS_ON_COMPILE) {
			$this->_compress_project();
		}
		if (!file_exists($source_dir)) {
			trigger_error("PROJECT_PACKER: ".__FUNCTION__.": source dir not exists!", E_USER_WARNING);
			return false;
		}
		if (!file_exists($compiled_dir)) {
			_mkdir_m($compiled_dir);
		}

		// Compile stpls dirs
		$stpl_dirs = $this->_get_stpls_dirs($source_dir."templates/");
		foreach ((array)$stpl_dirs as $_dir) {
			$output .= $this->_compile_stpls_dir(array(
				"source_path"	=> $_dir,
				"target_path"	=> $compiled_dir."stpls_compiled_".basename($_dir).".php",
			));
		}

		// PHP files for user section
		$output .= $this->_compile_php_dir(array(
			"source_path"	=> array(
				$source_dir."classes/",
				$source_dir."modules/",
			),
			"target_path"	=> $compiled_dir."project_compiled_user.php",
		));

		// PHP files for admin section
		$output .= $this->_compile_php_dir(array(
			"source_path"	=> array(
				$source_dir."classes/",
				$source_dir."admin_modules/",
			),
			"target_path"	=> $compiled_dir."project_compiled_admin.php",
		));

		if ($this->DEL_COMPRESSED_AFTER_COMPILE) {
			$this->DIR_OBJ->delete_dir($source_dir, 1);
		}

		return $output;
	}

	/**
	* Do compile framework files
	*/
	function _compile_framework() {
		$source_dir		= $this->_prepare_path(INCLUDE_PATH."PF_COMPRESSED/");
		$compiled_dir	= $this->_prepare_path(INCLUDE_PATH."PHP_COMPILED/");

		if (!file_exists($source_dir) && $this->TRY_COMPRESS_ON_COMPILE) {
			$this->_compress_framework();
		}
		if (!file_exists($source_dir)) {
			trigger_error("PROJECT_PACKER: ".__FUNCTION__.": source dir not exists!", E_USER_WARNING);
			return false;
		}
		if (!file_exists($compiled_dir)) {
			_mkdir_m($compiled_dir);
		}

		// Compile stpls dirs
		$stpl_dirs = $this->_get_stpls_dirs($source_dir."templates/");
		foreach ((array)$stpl_dirs as $_dir) {
			$output .= $this->_compile_stpls_dir(array(
				"source_path"	=> $_dir,
				"target_path"	=> $compiled_dir."stpls_compiled_".basename($_dir).".php",
			));
		}

		// PHP files for user section
		$output .= $this->_compile_php_dir(array(
			"source_path"	=> array(
				$source_dir."classes/",
				$source_dir."modules/",
			),
			"target_path"	=> $compiled_dir."framework_compiled_user.php",
		));

		// PHP files for admin section
		$output .= $this->_compile_php_dir(array(
			"source_path"	=> array(
				$source_dir."classes/",
				$source_dir."admin_modules/",
			),
			"target_path"	=> $compiled_dir."framework_compiled_admin.php",
		));

		if ($this->DEL_COMPRESSED_AFTER_COMPILE) {
			$this->DIR_OBJ->delete_dir($source_dir, 1);
		}

		return $output;
	}

	/**
	* Do pack files in selected directory
	*/
	function _compress_source_dir($params = array()) {
		$source_dir		= $this->_prepare_path($params["source_dir"]);
		$compressed_dir	= $this->_prepare_path($params["compressed_dir"]);

		$skip_files = $params["skip_files"] ? $params["skip_files"] : $this->_skip_files;

		$pattern_include_php	= $params["include_php"] ? $params["include_php"] : $this->_def_patterns["include_php"];
		$pattern_exclude_php	= $params["exclude_php"] ? $params["exclude_php"] : $this->_def_patterns["exclude_php"];
		$pattern_include_stpl	= $params["include_stpl"] ? $params["include_stpl"] : $this->_def_patterns["include_stpl"];
		$pattern_exclude_stpl	= $params["exclude_stpl"] ? $params["exclude_stpl"] : $this->_def_patterns["exclude_stpl"];

		if (!file_exists($source_dir)) {
			trigger_error("PROJECT_PACKER: ".__FUNCTION__.": source dir not exists!", E_USER_WARNING);
			return false;
		}
		// Create compressed files dir
		if (!file_exists($compressed_dir)) {
			_mkdir_m($compressed_dir);
		} else {
			// Cleanup target dir
			$this->DIR_OBJ->delete_dir($compressed_dir);
		}

		$php_files	= $this->DIR_OBJ->scan_dir($source_dir, 1, $pattern_include_php, $pattern_exclude_php);

		$_s_dir_length = strlen($source_dir);
		// Get files array
		foreach ((array)$php_files as $cur_file_path) {
			if ($params["only_these_php"]) {
				$need_continue = true;
				foreach ((array)$params["only_these_php"] as $_pattern) {
					if (preg_match($_pattern, $cur_file_path)) {
						$need_continue = false;
						break;
					}
				}
				if ($need_continue) {
					continue;
				}
			}
			$compressed_file_path	= $compressed_dir. substr($cur_file_path, $_s_dir_length);
			$compressed_file_dir	= dirname($compressed_file_path);
			$_file_name = basename($cur_file_path);
			if ($skip_files && in_array($_file_name, (array)$skip_files)) {
				continue;
			}
			// Skip by regexp
			if (!empty($this->_skip_files_preg)) {
				foreach ((array)$this->_skip_files_preg as $_pattern_skip) {
					if (preg_match($_pattern_skip, $_file_name)) {
						continue 2;
					}
				}
			}
			if (!file_exists($compressed_file_dir)) {
				_mkdir_m($compressed_file_dir);
			}
			// Do compress
			$output .= $this->_do_compress_php_file ($cur_file_path, $compressed_file_path);
		}

		$this->DIR_OBJ->copy_dir($source_dir, $compressed_dir, $pattern_include_stpl, $pattern_exclude_stpl);

		return $output;
	}

	/**
	* Get stpls dirs inside given folder
	*/
	function _get_stpls_dirs($start_dir = "") {
		if (!$start_dir) {
			return array();
		}
		$dirs = array();
		// Scan folder
		$dh = opendir($start_dir);
		while (false !== ($f = readdir($dh))) {
			if ($f == "." || $f == "..") {
				continue;
			}
			$item_name = $this->_prepare_path($start_dir."/".$f);
			if (is_dir($item_name)) {
				$dirs[$item_name] = $item_name;
			}
		}
		@closedir($dh);
		return $dirs;
	}

	/**
	* Do compile stpls into single file
	*/
	function _compile_stpls_dir($params = "") {
		$source_path = $this->_prepare_path($params["source_path"]);
		$target_path = $this->_prepare_path($params["target_path"]);
		if (!$source_path || !$target_path) {
			return false;
		}
		$pattern_include_stpl	= $params["include_stpl"] ? $params["include_stpl"] : $this->_def_patterns["include_stpl"];
		$pattern_exclude_stpl	= $params["exclude_stpl"] ? $params["exclude_stpl"] : $this->_def_patterns["exclude_stpl"];

		$source_files = $this->DIR_OBJ->scan_dir($source_path, 1, $pattern_include_stpl, $pattern_exclude_stpl);
		if (!$source_files) {
			return false;
		}
		// Open output file for writing
		$fh = fopen($target_path, "w");
		fwrite($fh, "<?php\r\n");
		//fwrite($fh, "define('TEMPLATES_COMPILED', 1);\r\n");
		fwrite($fh, "conf('_compiled_stpls', array(\r\n");
		// Get files to process
		$counter = 0;
		$_s_length = strlen(rtrim($source_path, "/")) + 1;
		foreach ((array)$source_files as $_cur_file_path) {
			$_cur_file_path = $this->_prepare_path($_cur_file_path);
			$stpl_name = substr($_cur_file_path, $_s_length, -5);
			// Add current file contents
			$text = "\"".$stpl_name."\" => \r\n'"
				.$this->_put_safe_slashes(file_get_contents($_cur_file_path))
				."'\r\n,\r\n";
			if ($this->ADD_SOURCE_FILE_NAMES) {
				$text = "// source: ".realpath($_cur_file_path)."\r\n".$text;
			}
			fwrite($fh, $text);
			$counter++;
		}
		fwrite($fh, "));\r\n");
		fwrite($fh, "\r\n?>");
		fclose($fh);
		// prepare output info
		$output .= "\r\n<br /><b>".$counter."</b> stpls from dir \"".print_r($source_path, 1)."\"<br />\r\n".
					" compiled into result file \"".$target_path."\"<br />\r\n";
		$output .= "result file size: <b>".filesize($target_path)."</b> bytes\r\n";

		return $output;
	}

	/**
	* Do compile php files into single file
	*/
	function _compile_php_dir($params = "") {
		$source_path = $this->_prepare_path($params["source_path"]);
		$target_path = $this->_prepare_path($params["target_path"]);
		if (!$source_path || !$target_path) {
			return false;
		}
		$pattern_include_php	= $params["include_php"] ? $params["include_php"] : $this->_def_patterns["include_php"];
		$pattern_exclude_php	= $params["exclude_php"] ? $params["exclude_php"] : $this->_def_patterns["exclude_php"];

		$source_files = $this->DIR_OBJ->scan_dir($source_path, 1, $pattern_include_php, $pattern_exclude_php);
		if (!$source_files) {
			return false;
		}
		// Open output file for writing
		$fh = fopen($target_path, "w");
		fwrite($fh, "<?php\r\n");
		fwrite($fh, "define('FRAMEWORK_IS_COMPILED', 1);\r\n");
		$counter = 0;
		foreach ((array)$source_files as $_cur_file_path) {
			if (empty($_cur_file_path)) {
				continue;
			}
			$_cur_file_path = $this->_prepare_path($_cur_file_path);
			// Add current file contents
			$text = substr(trim(file_get_contents($_cur_file_path)), 6);
			if (substr($text, -2) == "?".">") {
				$text = substr($text, 0, -2);
			}
			$text = $text."\r\n";
			if ($this->ADD_SOURCE_FILE_NAMES) {
				$text = "// source: ".realpath($_cur_file_path)."\r\n".$text;
			}
			fwrite($fh, $text);
			$counter++;
		}
		fwrite($fh, "\r\n?>");
		fclose($fh);
		
		// prepare output info
		$output .= "\r\n<br /><b>".$counter."</b> php files from dir \"".print_r($source_path, 1)."\"<br />\r\n".
					" compiled into result file \"".$target_path."\"<br />\r\n";
		$output .= "result file size: <b>".filesize($target_path)."</b> bytes<br />\r\n";

		return $output;
	}

	/**
	* Prepare text for cache
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
	* Method that allows to compress PHP code (removing comments, spaces, tabs, etc)
	*/
	function _do_compress_php_file ($file_to_open = "", $file_to_save = "") {
		$source = file_get_contents($file_to_open);
		// Removes comments
		foreach ((array)token_get_all($source) as $token) {
			if (is_string($token)) {
				// simple 1-character token
				$output .= $token;
			} else {
				// token array
				list($id, $text) = $token;
				switch ($id) { 
					case T_COMMENT: 
					case T_ML_COMMENT: // we've defined this
					case T_DOC_COMMENT: // and this
						// no action on comments
						$output .= " ";
						break;
					default:
						// anything else -> output "as is"
						$output .= $text;
						break;
				}
			}
		}
		$output = trim($output);
		$output = str_replace(array("\r","\n"), " ", $output);
		$output = str_replace("\t", " ", $output);
		$output = preg_replace("/[\s\t]{2,}/ims", " ", $output);
		// Write the file
		file_put_contents($file_to_save, $output);
		// Display compress ratio
		$body .= "compressed file \"".$file_to_open."\" saved into \"".$file_to_save."\"<br />\r\n";
		$opened_size	= @filesize($file_to_open);
		$saved_size		= @filesize($file_to_save);
		if (!$saved_size) {
			$saved_size = $opened_size ? $opened_size : 1;
		}
		$body .= "<b>compress ratio: ".(round($opened_size / $saved_size, 2) * 100)."% (".@filesize($file_to_open)." / ".@filesize($file_to_save)." bytes)</b><br />\r\n";
		return $body;
	}
	
	/**
	* Build other inheritance arrays
	*/
	function _build_inherit_parents ($parent = "") {
		$next_parent = $this->PACKER["INHERIT_TREE"][$parent];

		$parents = array();
		$parents[$parent] = $next_parent;
		if (!$next_parent) {
			$this->PACKER["INHERIT_TOP"][$parent]++;
		} else {
			$parents[$next_parent] = $this->PACKER["INHERIT_TREE"][$next_parent];
			foreach ((array)$this->_build_inherit_parents($next_parent) as $k => $v) {
				if (!isset($parents[$k])) {
					$parents[$k] = $v;
				}
			}
		}
		return $parents;
	}
}
