<?php
/*
	Converts old-style comments into the PEAR and JavaDoc style
*/
$DIR_TO_PROCESS		= "d:/www/htdocs/yf";
$DIR_FOR_PROCESSED	= "d:/www/htdocs/yf_PROCESSED";
$PACKAGE_NAME		= "Profy Framework";
$AUTHOR_NAME		= "Yuri Vysotskiy";
$AUTHOR_EMAIL		= "profy.net@gmail.com";
$VERSION			= "1.0";
$REVISION			= "\$Revision\$";
$class_header = <<<CLASS_HEADER
CLASS_HEADER;
//-----------------------------------------------------------------------------
// Return file extension
function get_file_ext ($file_path = "") {
	return array_pop(explode(".", basename($file_path)));
}
$GLOBALS['ignore_pattern']	= "/(^\.|^\.\.|^\.svn|^cvs|^_|\.jpg$|\.gif$|\.png$)/ims";
$GLOBALS['allowed_pattern']	= "/.*(\.php)$/ims";
//-----------------------------------------------------------------------------
// Recursively scanning directory structure (including subdirectories) //
function scan_dir ($d, $flat_mode = true) {
	if ($flat_mode) static $files = array();
	$dh = opendir($d);
	while (false !== ($f = readdir($dh))) {
		// Check for "black list"
		if (!empty($GLOBALS['ignore_pattern']) && preg_match($GLOBALS['ignore_pattern'], $f)) continue;
		// Getnew dir name
		$item_name = $d."/".$f;
		// "Flat" mode (all filenames are stored as 1-dimension array, else - multi-dimension array)
		if ($flat_mode) {
			if (is_dir($item_name)) {
				scan_dir($item_name, $flat_mode);
			} else {
				// Check for "white list"
				if (!empty($GLOBALS['allowed_pattern']) && !preg_match($GLOBALS['allowed_pattern'], $item_name)) continue;

				$files[] = $item_name;
			}
		} else {
			// Check for "white list"
			if (!empty($GLOBALS['allowed_pattern']) && !preg_match($GLOBALS['allowed_pattern'], $item_name)) continue;

			if (is_dir($item_name)) {
				$files[] = scan_dir($item_name, $flat_mode);
			} else {
				$files[] = $f;
			}
		}
	}
	closedir($dh);
	if (is_array($files)) sort($files);
	return $files;
}
//-----------------------------------------------------------------------------
// Put string contents to the file
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
//----------------------------------------------------------------------
// Create multiple dirs at one time (eg. mkdir_m("some_dir1/some_dir2/some_dir3"))
function mkdir_m($dir_name, $dir_mode = 0755, $create_index_htmls = 0) {
	if (empty($dir_name)) return 0;
	$old_mask = umask(0);
	// Default dir mode
	if (empty($dir_mode)) $dir_mode = 0755;
	// Process given file name
	if (!file_exists($dir_name)) {
		preg_match_all('/([^\/]*)\/?/i', $dir_name, $atmp);
		$base = "";
		foreach ((array)$atmp[0] as $val) {
			$base = $base. $val;
			// Skip if already exists
			if (file_exists($base)) continue;
			// Try to create sub dir
			if (!mkdir($base, $dir_mode)) {
				trigger_error("ProEngine Error: Cannot create ".$base, E_USER_WARNING);
				return -1;
			}
			chmod($base, $dir_mode);
		}
	} elseif (!is_dir($dir_name)) {
		trigger_error("ProEngine Error: ".$dir_name." exists and is not a directory", E_USER_WARNING);
		return -2;
	}
	// Create empty index.html in new folder if needed
	if ($create_index_htmls) {
		$index_file_path = $dir_name. (OS_WINDOWS ? "\\" : '/'). "index.html";
		if (!file_exists($index_file_path)) file_put_contents($index_file_path, "");
	}
	umask($old_mask);
	return 0;
}
//-----------------------------------------------------------------------------
echo "<pre>\r\n";
$files = scan_dir($DIR_TO_PROCESS);
$num_lines = array();
$class_ext = ".class.php";
//-----------------------------------------------------------------------------
// Pattern for classs header
$p_class = "/<\?php.*?\/\/[\-]{60,}[\r\n]+\/\/([^\r\n]*?)[\r\n]+(class)[\s\t]+([a-z_0-9]{3,})/is";
// Pattern for method (function) header
$p_function = "/[\t]+\/\/[\-]{60,}[\r\n]+[\t\/]*([^\r\n]*?)[\r\n]*[\s]{0,1}[\t]{1}(function)[\s]+([&a-z_0-9]{3,})/is";
// Process files
foreach ((array)$files as $file_name) {
	// Skip not matched files
	if (substr($file_name, -strlen($class_ext)) != $class_ext) continue;
	$old_text = file_get_contents($file_name);
	$text = $old_text;
	$new_file_path = str_replace($DIR_TO_PROCESS, $DIR_FOR_PROCESSED, $file_name);
	// Log process
	echo "\r\n".$file_name."\r\n => ".$new_file_path."\r\n";
	// Process class
	$class_result = preg_match($p_class, $text, $m_class);
	if (!empty($class_result)) {
		$c_desc = trim($m_class[1]);
		$c_name = $m_class[3];
		$class_replaced = 
			"<?php\r\n".
			$class_header.
			"\r\n\r\n".
			"/**\r\n".
			"* ".(!empty($c_desc) ? $c_desc : ucwords(str_replace("_", " ", $c_name)))."\r\n".
			"* \r\n".
			"* @package\t\t".$PACKAGE_NAME."\r\n".
			"* @author\t\t".$AUTHOR_NAME." <".$AUTHOR_EMAIL.">\r\n".
			"* @version\t\t".$VERSION."\r\n".
			"* @revision\t".$REVISION."\r\n".
			"*/\r\n".
			"class ".trim($c_name);
		$text = str_replace($m_class[0], $class_replaced, $text);
		// Log process
		echo "\r\nCLASS HEADER OLD: \r\n".$m_class[0]."\r\n\r\nCLASS HEADER NEW: \r\n".$class_replaced."\r\n";
	}
	// Process functions
	$function_result = preg_match_all($p_function, $text, $m_function);
	if (!empty($function_result)) {
		foreach ((array)$m_function[0] as $num => $f_text) {
			$f_desc = trim($m_function[1][$num]);
			$f_name = trim($m_function[3][$num]);
			$f_replaced = 
				"\t/**\r\n".
				"\t* ".(!empty($f_desc) ? $f_desc : ucwords(str_replace("_", " ", str_replace("&", "", $f_name))))."\r\n".
				"\t* \r\n".
				"\t* @access\t".(substr($f_name, 0, 1) == "_" ? "private" : "public")."\r\n".
				"\t* @param\r\n".
				"\t* @return\r\n".
				"\t*/\r\n".
				"\tfunction ".trim($f_name);
			$text = str_replace($f_text, $f_replaced, $text);
			// Log process
			echo "\r\nFUNCTION HEADER OLD: \r\n".$f_text."\r\n\r\nFUNCTION HEADER NEW: \r\n".$f_replaced."\r\n";
		}
	}
	// Save file contents
	if ($text != $old_text) {
		mkdir_m(dirname($new_file_path));
		file_put_contents($new_file_path, $text);
	}
}
echo "\r\n</pre>\r\n";
//-----------------------------------------------------------------------------
?>