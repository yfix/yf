<?php
//-----------------------------------------------------------
// settings
//-----------------------------------------------------------
$base_path		= "d:/www/htdocs/zend/";
$source_dir		= $base_path."library/";
$packed_dir		= $base_path."packed/";
$compiled_dir	= $base_path;
$compiled_file	= $compiled_dir."zend_framework_min.php";

$ADD_SOURCE_FILE_NAMES		= 1;

$pattern_require = "/(require_once[\s\t]*[\"\'\(]+([^\'\"\(\)]+?)[\'\"\)]+[\s\t]*;[\s\t]*)/ims";
//-----------------------------------------------------------
$GLOBALS['CLASSES']			= array();
$GLOBALS['CLASSES_PATHS']	= array();
$GLOBALS["BUILT_IN_CLASSES"]= array_merge(get_declared_classes(), spl_classes(), get_declared_interfaces());
$GLOBALS["INHERIT_TREE"]	= array();
$GLOBALS["INHERIT_CHILDREN"]= array();
$GLOBALS["INHERIT_PARENTS"]	= array();
$GLOBALS["INHERIT_TOP"]		= array();
$GLOBALS["INHERIT_NONE"]	= array();
$GLOBALS["ABSTRACT_CLASSES"]= array();
$GLOBALS["INTERFACES"]		= array();
//-----------------------------------------------------------
$time_start = array_sum(explode(" ", microtime()));
//-----------------------------------------------------------


/**
* Put string contents to the file
*/
if (!function_exists("file_put_contents")) {
	function file_put_contents ($filename, $data) {
		if (!$fp = @fopen($filename, "w")) return false;
		flock ($fp, LOCK_EX);
		$res = fwrite($fp, $data, strlen($data));
		flock ($fp, LOCK_UN);
		fclose ($fp);
		return $res;
	}
}

if (!defined('T_ML_COMMENT')) {
	define('T_ML_COMMENT', T_COMMENT);
} else {
	define('T_DOC_COMMENT', T_ML_COMMENT);
}

// Method that allows to compress PHP code (removing comments, spaces, tabs, etc)
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
	$output = str_replace(array("\r","\n"), "", $output);
	$output = str_replace("\t", " ", $output);
	$output = preg_replace("/[\s\t]{2,}/ims", " ", $output);
	// Write the file
	$fh = @fopen($file_to_save, "w");
	fwrite($fh, $output);
	@fclose($fh);
	// Display compress ratio
	$body .= "compressed file \"".$file_to_open."\" saved into \"".$file_to_save."\"<br />\r\n";
	$body .= "<b>compress ratio: ".(round(@filesize($file_to_open) / @filesize($file_to_save), 2) * 100)."% (".@filesize($file_to_open)." / ".@filesize($file_to_save)." bytes)</b><br />\r\n";
	return $body;
}

/**
* Create dir structure
*/
function _mkdir_m($dir_name, $dir_mode = 0755, $create_index_htmls = 0, $start_folder = "") {
	if (!$dir_name || !strlen($dir_name)) {
		return 0;
	}
	$dir_name = rtrim($dir_name, "/");
	// Default start folder to look at
	if (!strlen($start_folder)) {
		$start_folder = INCLUDE_PATH;
	}
	$start_folder	= str_replace(array("\\", "//"), "/", realpath($start_folder)."/");
	$dir_name		= str_replace(array("\\", "//"), "/", $dir_name);
	$old_mask = umask(0);
	// Default dir mode
	if (empty($dir_mode)) {
		$dir_mode = 0755;
	}
	// Process given file name
	if (!file_exists($dir_name)) {
		$base_path = substr(PHP_OS, 0, 3) == 'WIN' ? "" : "/";
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
* Recursively scanning directory structure (including subdirectories) //
*/
function scan_dir ($start_dir, $FLAT_MODE = true, $include_pattern = "", $exclude_pattern = "") {
	if (!file_exists($start_dir)) {
		return false;
	}
	// Cut trailing slash
	if (substr($start_dir, -1) == "/") {
		$start_dir = substr($start_dir, 0, -1);
	}
	$files	= array();
	$dh		= opendir($start_dir);
	while (false !== ($f = readdir($dh))) {
		if ($f == "." || $f == "..") {
			continue;
		}
		$item_name = $start_dir."/".$f;
		// "Flat" mode (all filenames are stored as 1-dimension array, else - multi-dimension array)
		if (is_dir($item_name)) {
			$tmp_file = scan_dir($item_name, $FLAT_MODE, $include_pattern, $exclude_pattern);
		} else {
			$tmp_file = $FLAT_MODE ? $item_name : $f;
			// Include files only if they match the mask
			if (!empty($include_pattern)) {
				if (!preg_match($include_pattern."ims", $tmp_file)) {
					continue;
				}
			}
			// Exclude files from list by mask
			if (!empty($exclude_pattern)) {
				if (preg_match($exclude_pattern."ims", $tmp_file)) {
					continue;
				}
			}
		}
		// Add item to the result array
		$files[$item_name] = $tmp_file;
	}
	closedir($dh);
	// Prepare for the flat mode (if needed)
	if (is_array($files)) {
		if ($FLAT_MODE) {
			$files = array_values_recursive($files);
		}
		ksort($files);
	}
	return $files;
}

/**
* Get values from the multi-dimensional array
*/
function array_values_recursive($ary) {
	$lst = array();
	foreach (array_keys($ary) as $k) {
		$v = $ary[$k];
		if (is_scalar($v)) {
			$lst[] = $v;
		} elseif (is_array($v)) {
			$lst = array_merge($lst, array_values_recursive($v));
		}
	}
	return $lst;
}

/**
* Build other inheritance arrays
*/
function _build_inherit_parents ($parent = "") {
	$next_parent = $GLOBALS["INHERIT_TREE"][$parent];

	$parents = array();
	$parents[$parent] = $next_parent;
	if (!$next_parent) {
		$GLOBALS["INHERIT_TOP"][$parent]++;
	} else {
		$parents[$next_parent] = $GLOBALS["INHERIT_TREE"][$next_parent];
		foreach ((array)_build_inherit_parents($next_parent) as $k => $v) {
			if (!isset($parents[$k])) {
				$parents[$k] = $v;
			}
		}
	}
	return $parents;
}

//-----------------------------------------------------------
// START
//-----------------------------------------------------------

_mkdir_m($packed_dir, 0777);
_mkdir_m($compiled_dir, 0777);

// Get files array
$files_to_compress = scan_dir($source_dir, 1, "#.*\.(php)\$#");
//print_r($files_to_compress);
foreach ((array)$files_to_compress as $cur_file_path) {
	$compressed_file_path	= $packed_dir. substr($cur_file_path, strlen($source_dir));
	$compressed_file_dir	= dirname($compressed_file_path);
	if (!file_exists($compressed_file_dir)) {
		_mkdir_m($compressed_file_dir, 0777);
	}
	// Do compress
	_do_compress_php_file ($cur_file_path, $compressed_file_path);
}

// Get classes names and paths
$new_files_to_compile = array();
foreach ((array)scan_dir($packed_dir, 1, "#.*\.(php)\$#", $exclude_pattern_user) as $cur_file_path) {
	$class_name = str_replace("/", "_", str_replace("\/", "/", substr($cur_file_path, strlen($packed_dir), -4)));
	$GLOBALS['CLASSES'][$class_name] = $class_name;
	$GLOBALS['CLASSES_PATHS'][$class_name] = $cur_file_path;
}

// Build inheritance tree
foreach ((array)$GLOBALS['CLASSES'] as $class_name) {
	$text = substr(file_get_contents($GLOBALS['CLASSES_PATHS'][$class_name]), 6)."\r\n";
	$text = preg_replace($pattern_require, "", $text);
	if (preg_match("/abstract class ([a-z\_]+)/ims", $text, $m)) {
		$GLOBALS["ABSTRACT_CLASSES"][$class_name] = $class_name;
	}
	if (preg_match("/interface ([a-z\_]+)/ims", $text, $m)) {
		$GLOBALS["INTERFACES"][$class_name] = $class_name;
	}
	if (preg_match("/(class|interface) ([a-z\_]+) (extends|implements) ([a-z\_]+)/ims", $text, $m)) {
		$_child		= $m[2];
		$_parent	= $m[4];
		if (!in_array($_parent, $GLOBALS['BUILT_IN_CLASSES'])) {
			$GLOBALS["INHERIT_TREE"][$_child] = $_parent;
			$GLOBALS["INHERIT_CHILDREN"][$_parent][$_child] = $_child;
		}
	} else {
		$GLOBALS["INHERIT_NONE"][$class_name] = $class_name;
	}
}

// Build parents array
foreach ((array)$GLOBALS["INHERIT_TREE"] as $_child => $_parent) {
	$GLOBALS["INHERIT_PARENTS"][$_child] = _build_inherit_parents($_parent);
}

ksort($GLOBALS["INHERIT_NONE"]);
ksort($GLOBALS["INHERIT_TOP"]);

// Build result array
$CLASSES_NEW = array();
foreach (array_keys($GLOBALS["ABSTRACT_CLASSES"]) as $v) {
	if (!isset($CLASSES_NEW[$v])) {
		$CLASSES_NEW[$v] = $v;
	}
}
foreach (array_keys($GLOBALS["INTERFACES"]) as $v) {
	if (!isset($CLASSES_NEW[$v])) {
		$CLASSES_NEW[$v] = $v;
	}
}
foreach (array_keys($GLOBALS["INHERIT_NONE"]) as $v) {
	if (!isset($CLASSES_NEW[$v])) {
		$CLASSES_NEW[$v] = $v;
	}
}
foreach (array_keys($GLOBALS["INHERIT_TOP"]) as $v) {
	if (!isset($CLASSES_NEW[$v])) {
		$CLASSES_NEW[$v] = $v;
	}
}
foreach ((array)$GLOBALS["INHERIT_PARENTS"] as $v => $_parents) {
	foreach (array_reverse((array)$_parents, true) as $_parent => $_tmp) {
		if (!isset($CLASSES_NEW[$_parent])) {
			$CLASSES_NEW[$_parent] = $_parent;
		}
	}
	if (!isset($CLASSES_NEW[$v])) {
		$CLASSES_NEW[$v] = $v;
	}
}
foreach (array_keys($GLOBALS["CLASSES"]) as $v) {
	if (!isset($CLASSES_NEW[$v])) {
		$CLASSES_NEW[$v] = $v;
	}
}
$GLOBALS["CLASSES"] = $CLASSES_NEW;

$output .= "<pre>";
$output .= "<h1>INHERIT_TREE</h1>";
$output .= print_r($GLOBALS["INHERIT_TREE"], 1);
$output .= "<h1>INHERIT_CHILDREN</h1>";
$output .= print_r($GLOBALS["INHERIT_CHILDREN"], 1);
$output .= "<h1>INHERIT_PARENTS</h1>";
$output .= print_r($GLOBALS["INHERIT_PARENTS"], 1);
$output .= "<h1>INHERIT_TOP</h1>";
$output .= print_r($GLOBALS["INHERIT_TOP"], 1);
$output .= "<h1>INHERIT_NONE</h1>";
$output .= print_r($GLOBALS["INHERIT_NONE"], 1);
$output .= "<h1>ABSTRACT_CLASSES</h1>";
$output .= print_r($GLOBALS["ABSTRACT_CLASSES"], 1);
$output .= "<h1>INTERFACES</h1>";
$output .= print_r($GLOBALS["INTERFACES"], 1);
$output .= "<h1>RESULT_CLASSES</h1>";
$output .= print_r($GLOBALS["CLASSES"], 1);
$output .= "</pre>";


//------------------------------------------
// Finish
//------------------------------------------
$fh = fopen($compiled_file, "w");
fwrite($fh, "<?php\r\n");
$counter = 0;

foreach ((array)$GLOBALS['CLASSES'] as $name) {
	if (!$name) {
		continue;
	}
	$cur_file_path = $GLOBALS['CLASSES_PATHS'][$name];
	$cur_file_text = file_get_contents($cur_file_path);
	$text = substr($cur_file_text, 6)."\r\n";
	$text = preg_replace($pattern_require, "", $text);
	if (!strlen($text)) {
		continue;
	}
	if ($ADD_SOURCE_FILE_NAMES) {
		$text = "// source: ".$cur_file_path."\r\n".$text;
	}
	fwrite($fh, $text);
	$counter++;

	$output .= $name." # ".$cur_file_path." <b>".strlen($text)."</b><br />";
}
fclose($fh);

// prepare output info
$output2 .= "\r\n<br /><b>".$counter."</b> files from dir \"".$source_dir."\"<br />\r\n compiled into result file \"".$compiled_file."\"<br />\r\n";
$output2 .= "result file size: <b>".filesize($compiled_file)."</b> bytes<br />\r\n";
$output2 .= "Generation time: <b>".round(array_sum(explode(" ", microtime())) - $time_start, 3)." secs</b>\r\n";
$output = $output2. $output;

echo $output;
