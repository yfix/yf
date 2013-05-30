<?php
//-----------------------------------------------------------------------------
// Return file extension
function get_file_ext ($file_path = "") {
	return array_pop(explode(".", basename($file_path)));
}
//-----------------------------------------------------------------------------
// Recursively scanning directory structure (including subdirectories) //
function scan_dir ($d, $flat_mode = true) {
	if ($flat_mode) {
		static $files = array();
	}
	$dh = opendir($d);
	if (false !== strpos($d, "svn")) {
		return $files;
	}
	while (false !== ($f = readdir($dh))) {
		if ($f == "." || $f == "..") {
			continue;
		}
		if (false !== strpos($file_name, "svn")) {
			continue;
		}
		$dir_name = $d."/".$f;
		// "Flat" mode (all filenames are stored as 1-dimension array, else - multi-dimension array)
		if ($flat_mode) {
			is_dir($dir_name) ? scan_dir($dir_name, $flat_mode) : $files[] = $dir_name;
		} else {
			$files[] = is_dir($dir_name) ? scan_dir($dir_name, $flat_mode) : $f;
		}
	}
	closedir($dh);
	if (is_array($files)) {
		sort($files);
	}
	return $files;
}
//-----------------------------------------------------------------------------
//$files = scan_dir('.');
$files = scan_dir('./PROFY_FRAMEWORK/');
//-----------------------------------------------------------------------------
// Skip images and files with no extension (parse "php", "tpl", "stpl")
if (is_array($files)) foreach ((array)$files as $file_name) {
//	$ext = get_file_ext($file_name);
//	if (!in_array($ext, array("php"))) continue;
	if (substr($file_name, -strlen(".class.php")) != ".class.php") {
		continue;
	}
	// Process file contents
	$text = file_get_contents($file_name);
	$pattern = "/(_e|_raise_error|_show_error_message)[\s]*\([\"\']*([^\)]+?)[\"\']*\)/ims";
	$match = preg_match_all($pattern, $text, $matches);
	// Filter repeating values
	foreach ((array)$matches['2'] as $v) {
//		$v = strtolower(str_replace(array("\"", "'"), "", trim($v)));
		// Skip varable names and incorrect names
		if (strpos($v, "\$") !== false) {
			continue;
		}
		if (strpos($v, "\\") !== false) {
			continue;
		}
		// Skip some reserved words like "__CLASS__", "__FUNCTION__"
		if (in_array($v, array("__class__", "__function__"))) {
			continue;
		}
		$translate_array[$v] = $v;
	}
}
if (is_array($translate_array)) {
	sort($translate_array);
}
//-----------------------------------------------------------------------------
echo "<pre>\r\n";
//print_r($translate_array);
if (is_array($translate_array)) foreach ((array)$translate_array as $n) {
	// Find max length of the variable name
	$max_len = strlen($n) > $max_len ? strlen($n) : $max_len;
}
if (is_array($translate_array)) foreach ((array)$translate_array as $n) {
//	$n = str_replace(" ","_",$n);
//	echo "\t\t\$this->vars['".$n."']".str_repeat(" ", floor(($max_len - strlen($n))) + 1)."= \"".$n."\";\r\n";
//	echo "\t'".$n."' = \"".$n."\";\r\n";
	echo $n."\r\n";
}
echo "</pre>\r\n";
//-----------------------------------------------------------------------------
?>