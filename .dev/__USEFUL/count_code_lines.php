<?php
//-----------------------------------------------------------------------------
// Return file extension
function get_file_ext ($file_path = "") {
	return array_pop(explode(".", basename($file_path)));
}
//-----------------------------------------------------------------------------
// Recursively scanning directory structure (including subdirectories) //
function scan_dir ($d, $flat_mode = true) {
	if ($flat_mode) static $files = array();
	$dh = opendir($d);
	while (false !== ($f = readdir($dh))) {
		if ($f == "." || $f == "..") continue;
		$dir_name = $d."/".$f;
		// "Flat" mode (all filenames are stored as 1-dimension array, else - multi-dimension array)
		if ($flat_mode) {
			is_dir($dir_name) ? scan_dir($dir_name, $flat_mode) : $files[] = $dir_name;
		} else {
			$files[] = is_dir($dir_name) ? scan_dir($dir_name, $flat_mode) : $f;
		}
	}
	closedir($dh);
	if (is_array($files)) sort($files);
	return $files;
}
//-----------------------------------------------------------------------------
$files = scan_dir('.');
$num_lines = array();
//-----------------------------------------------------------------------------
// Skip images and files with no extension (parse "php", "tpl", "stpl")
if (is_array($files)) foreach ((array)$files as $file_name) {
	$ext = get_file_ext($file_name);
	if (!in_array($ext, array("php"/*, "tpl", "stpl"*/))) continue;
	// Check for valid folder
	if (!preg_match("/^\.\/(admin|admin_modules|classes|functions|modules)(?!\/pcl\/|\/adodb\/).+/i",$file_name)) continue;
	// Count number of lines in the current file
	$num_lines[$file_name] = count(file($file_name));
}
echo "Total lines in PHP files in this project = ".eval("return ".implode("+",$num_lines).";")." in ".count($num_lines)." files\r\n";
echo "<pre>".print_r($num_lines, 1)."</pre>\r\n";
//-----------------------------------------------------------------------------
?>