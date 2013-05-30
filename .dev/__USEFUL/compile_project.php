<?php

/**
* Recursively scanning directory structure (including subdirectories) //
* 
* @access	public
* @param
* @return
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
* 
* @access	public
* @param	$ary	array	Array to process
* @return	array			Flat array of values
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
* Put string contents to the file
* 
* @access	public
* @param
* @return
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

/**
* Prepare text to store it in cache
* 
* @access	private
* @param
* @return
*/
if (!function_exists("_put_safe_slashes")) {
	function _put_safe_slashes ($text = "") {
		$text = str_replace("'", "&#039;", trim(/*stripslashes(*/$text/*)*/));
		$text = str_replace("\\&#039;", "\\'", $text);
		$text = str_replace("&#039;", "\\'", $text);
		return $text;
	}
}

// Get server OS
define('OS_WINDOWS', substr(PHP_OS, 0, 3) == 'WIN');

//-----------------------------------------------------------
//-----------------------------------------------------------
// settings
$source_dir					= "./PROFY_FRAMEWORK_COMPRESSED/";
$compiled_dir				= "./PROFY_FRAMEWORK_COMPILED/";
$compiled_file_path_admin	= $compiled_dir."framework_compiled_user.php";
$compiled_file_path_user	= $compiled_dir."framework_compiled_admin.php";
// templates
$stpls_folder_user			= "templates/user/";
$stpls_folder_admin			= "templates/admin/";
$stpls_source_dir_user		= $source_dir.$stpls_folder_user;
$stpls_source_dir_admin		= $source_dir.$stpls_folder_admin;
$compiled_stpls_path_admin	= $compiled_dir."stpls_compiled_admin.php";
$compiled_stpls_path_user	= $compiled_dir."stpls_compiled_user.php";

$ADD_SOURCE_FILE_NAMES		= 1;

// Create compiled files dir
if (!file_exists($compiled_dir)) {
	mkdir($compiled_dir, 0777);
}

//-----------------------------------------------------------
// compile php code
//-----------------------------------------------------------

// Get files array
$exclude_pattern_user	= "#(admin_modules|__SANDBOX|__DOCS|__SAMPLES|__TESTS|share|pear|xpm2|smarty|adodb|domit|html2fpdf|feedcreator|convertcharset)\/#";
$exclude_pattern_admin	= "#(\/modules|__SANDBOX|__DOCS|__SAMPLES|__TESTS|share|pear|xpm2|smarty|adodb|domit|html2fpdf|feedcreator|convertcharset)\/#";

// User section

$files_to_compile = scan_dir($source_dir, 1, "#.*\.(php)\$#", $exclude_pattern_user);
// Open output file for writing
$fh = fopen($compiled_file_path_admin, "w");
fwrite($fh, "<?php\r\n");
fwrite($fh, "define('FRAMEWORK_IS_COMPILED', 1);\r\n");
$counter = 0;
foreach ((array)$files_to_compile as $cur_file_path) {
	$cur_file_text = file_get_contents($cur_file_path);
	// Add current file contents
	$text = substr($cur_file_text, 6, -2)."\r\n";
	if ($ADD_SOURCE_FILE_NAMES) {
		$text = "// source:".$cur_file_path."\r\n".$text;
	}
	fwrite($fh, $text);
	$counter++;
}
fwrite($fh, "\r\n?>");
fclose($fh);

// prepare output info
$output .= "\r\n<br /><b>".$counter."</b> files from dir \"".$source_dir."\"<br />\r\n compiled into result file \"".$compiled_file_path_admin."\"<br />\r\n";
$output .= "result file size: <b>".filesize($compiled_file_path_admin)."</b> bytes<br />\r\n";

// Admin section

$files_to_compile = scan_dir($source_dir, 1, "#.*\.(php)\$#", $exclude_pattern_admin);
// Open output file for writing
$fh = fopen($compiled_file_path_user, "w");
fwrite($fh, "<?php\r\n");
fwrite($fh, "define('FRAMEWORK_IS_COMPILED', 1);\r\n");
$counter = 0;
foreach ((array)$files_to_compile as $cur_file_path) {
	$cur_file_text = file_get_contents($cur_file_path);
	// Add current file contents
	$text = substr($cur_file_text, 6, -2)."\r\n";
	if ($ADD_SOURCE_FILE_NAMES) {
		$text = "// source:".$cur_file_path."\r\n".$text;
	}
	fwrite($fh, $text);
	$counter++;
}
fwrite($fh, "\r\n?>");
fclose($fh);

// prepare output info
$output .= "\r\n<br /><b>".$counter."</b> files from dir \"".$source_dir."\"<br />\r\n compiled into result file \"".$compiled_file_path_user."\"<br />\r\n";
$output .= "result file size: <b>".filesize($compiled_file_path_user)."</b> bytes<br />\r\n";

//-----------------------------------------------------------
// compile (pack) templates
//-----------------------------------------------------------

// User section templates

// Get files to process
$stpls_to_compile = scan_dir($stpls_source_dir_user, 1, "#.*\.(stpl)\$#");
// Open output file for writing
$fh = fopen($compiled_stpls_path_user, "w");
fwrite($fh, "<?php\r\n");
//fwrite($fh, "define('TEMPLATES_COMPILED', 1);\r\n");
fwrite($fh, "\$GLOBALS['_compiled_stpls'] = array(\r\n");

$counter = 0;
foreach ((array)$stpls_to_compile as $cur_file_path) {
	$cur_file_path = str_replace("\\", "/", $cur_file_path);
	$stpl_name = substr($cur_file_path, strpos($cur_file_path, $stpls_source_dir_user) + strlen($stpls_source_dir_user), -5);
	// Add current file contents
	$cur_file_text = file_get_contents($cur_file_path);
	$text = "\"".$stpl_name."\" => \r\n'"._put_safe_slashes($cur_file_text)."'\r\n,\r\n";
	if ($ADD_SOURCE_FILE_NAMES) {
		$text = "// source:".$cur_file_path."\r\n".$text;
	}
	fwrite($fh, $text);
	$counter++;
}
fwrite($fh, ");\r\n");
fwrite($fh, "\r\n?>");
fclose($fh);

// prepare output info
$output .= "\r\n<br /><b>".$counter."</b> stpls from dir \"".$stpls_source_dir_user."\"<br />\r\n compiled into result file \"".$compiled_stpls_path_user."\"<br />\r\n";
$output .= "result file size: <b>".filesize($compiled_stpls_path_user)."</b> bytes\r\n";

// Admin section templates

// Get files to process
$stpls_to_compile = scan_dir($stpls_source_dir_admin, 1, "#.*\.(stpl)\$#");
// Open output file for writing
$fh = fopen($compiled_stpls_path_admin, "w");
fwrite($fh, "<?php\r\n");
//fwrite($fh, "define('TEMPLATES_COMPILED', 1);\r\n");
fwrite($fh, "\$GLOBALS['_compiled_stpls'] = array(\r\n");

$counter = 0;
foreach ((array)$stpls_to_compile as $cur_file_path) {
	$cur_file_path = str_replace("\\", "/", $cur_file_path);
	$stpl_name = substr($cur_file_path, strpos($cur_file_path, $stpls_source_dir_admin) + strlen($stpls_source_dir_admin), -5);
	// Add current file contents
	$cur_file_text = file_get_contents($cur_file_path);
	$text = "\"".$stpl_name."\" => \r\n'"._put_safe_slashes($cur_file_text)."'\r\n,\r\n";
	if ($ADD_SOURCE_FILE_NAMES) {
		$text = "// source:".$cur_file_path."\r\n".$text;
	}
	fwrite($fh, $text);
	$counter++;
}
fwrite($fh, ");\r\n");
fwrite($fh, "\r\n?>");
fclose($fh);

// prepare output info
$output .= "\r\n<br /><b>".$counter."</b> stpls from dir \"".$stpls_source_dir_admin."\"<br />\r\n compiled into result file \"".$compiled_stpls_path_admin."\"<br />\r\n";
$output .= "result file size: <b>".filesize($compiled_stpls_path_admin)."</b> bytes\r\n";

echo $output;
?>