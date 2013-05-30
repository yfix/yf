<?php
//-----------------------------------------------------------------------------
// Physical path to the ProEngine Core files
define(INCLUDE_PATH, "d:/www/htdocs/SEXY_NET/");
// Physical path to the current folder
define(REAL_PATH, "d:/www/htdocs/SEXY_NET/site1/");
// Physical path to the library templates
define(TPLS_LIB_PATH, INCLUDE_PATH."templates/");
//-----------------------------------------------------------------------------
require INCLUDE_PATH."db_setup.php";
require INCLUDE_PATH."classes/db.class.php";
//-----------------------------------------------------------------------------
$GLOBALS['db'] = new db;
//-----------------------------------------------------------------------------
// Return file extension
function get_file_ext ($file_path = "") {
	return array_pop(explode(".", basename($file_path)));
}
//-----------------------------------------------------------------------------
// Recursively scanning directory structure (including subdirectories) //
function scan_dir ($d, $FLAT_MODE = true) {
	$files = array();
	$dh = opendir($d);
	while (false !== ($f = readdir($dh))) {
		if ($f == "." || $f == "..") continue;
		$file_path = $d."/".$f;
		// "Flat" mode (all filenames are stored as 1-dimension array, else - multi-dimension array)
		$files[$file_path] = is_dir($file_path) ? scan_dir($file_path, $FLAT_MODE) : ($FLAT_MODE ? $file_path : $f);
	}
	closedir($dh);
	if (is_array($files)) {
		if ($FLAT_MODE) $files = array_values_recursive($files);
		ksort($files);
	}
	return $files;
}
//-----------------------------------------------------------------------------
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
//-----------------------------------------------------------------------------
// Get themes names
function _get_themes ($d = TPLS_LIB_PATH) {
	$themes = array();
	$dh = opendir($d);
	while (false !== ($f = readdir($dh))) {
		$dirName = $d."/".$f;
		if (is_dir($dirName) && $f != "." && $f != "..") $themes[$f] = $f;
	}
	ksort($themes);
	return $themes;
}
//-----------------------------------------------------------------------------
$themes = _get_themes();
// Process themes
if (is_array($themes)) foreach ((array)$themes as $theme_name) {
	$files = scan_dir(TPLS_LIB_PATH.$theme_name);
	if (!is_array($files)) continue;
	// Show execution progress
	echo "<br><b>PROCESSING THEME \"".$theme_name."\":</b><br><br>\r\n";
	// Process files in the current theme
	foreach ((array)$files as $file_name) {
		// Skip all other files except templates
		if (get_file_ext($file_name) != "stpl") continue;
		$theme_name	= $GLOBALS['db']->real_escape_string($theme_name);
		$stpl_name	= $GLOBALS['db']->real_escape_string(str_replace(TPLS_LIB_PATH.$theme_name."/", "", substr($file_name, 0, -5)));
		$text		= $GLOBALS['db']->real_escape_string(file_get_contents($file_name));
		// Check if current template exists in the db
		list($record_id) = $GLOBALS['db']->query_fetch("SELECT `id` AS `0` FROM `".dbt_templates."` WHERE `theme_name`='".$theme_name."' AND `name`='".$stpl_name."'");
		// Insert or update record
		if ($record_id) $GLOBALS['db']->query("UPDATE `".dbt_templates."` SET `text`='".$text."' WHERE `id`=".intval($record_id));
		else $GLOBALS['db']->query("REPLACE INTO `".dbt_templates."` (`theme_name`,`name`,`text`) VALUES ('".$theme_name."','".$stpl_name."','".$text."')");
		// Show execution progress
		echo "<b>".$stpl_name."</b> (".strlen($text)." bytes) - <b style='color:green;'>OK</b><br>\r\n";
	}
}
//-----------------------------------------------------------------------------
?>