<?php
//-----------------------------------------------------------------------------
// Physical path to the ProEngine Core files
define(INCLUDE_PATH, "d:/www/htdocs/SEXY_NET/");
// Physical path to the current folder
define(REAL_PATH, "d:/www/htdocs/SEXY_NET/site1/");
// Physical path to the library templates (currently creating new dir "templates2")
define(TPLS_LIB_PATH, INCLUDE_PATH."templates2/");
//-----------------------------------------------------------------------------
require INCLUDE_PATH."db_setup.php";
require INCLUDE_PATH."classes/db.class.php";
//-----------------------------------------------------------------------------
$GLOBALS['db'] = new db;
//----------------------------------------------------------------------
// Create multiple dirs at one time (eg. mkdir_m("some_dir1/some_dir2/some_dir3"))
// !ADDITION : create index.html inside every dir (to prevent directory listing)
function mkdir_m_with_indexhtm($dir_name, $dir_mode = 0700) {
	if (empty($dir_name)) return 0;
	if (!file_exists($dir_name)) {
		preg_match_all('/([^\/]*)\/?/i', $dir_name, $atmp);
		$base = "";
		foreach ((array)$atmp[0] as $val) {
			$base = $base. $val;
			// Skip if already exists
			if (file_exists($base)) {
				file_put_contents($base."/index.html", "");
				continue;
			}
			// Try to create sub dir
			if (!mkdir($base, $dir_mode)) {
				trigger_error("ProEngine Error: Cannot create ".$base, E_USER_WARNING);
				return -1;
			}
			file_put_contents($base."/index.html", "");
		}
	} elseif (!is_dir($dir_name)) {
		trigger_error("ProEngine Error: ".$dir_name." exists and is not a directory", E_USER_WARNING);
		return -2;
	}
	file_put_contents($dir_name."/index.html", "");
	return 0;
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
// Get unique themes names from database
function _get_unique_themes_from_db () {
	$Q = $GLOBALS['db']->query("SELECT DISTINCT(`theme_name`) AS `theme` FROM `".dbt_templates."` WHERE `active`='1'");
	while ($A = @$GLOBALS['db']->fetch_assoc($Q)) if (strlen($A["theme"])) $themes[$A["theme"]] = $A["theme"];
	return $themes;
}
//-----------------------------------------------------------------------------
$themes = _get_unique_themes_from_db();
// Process themes
if (is_array($themes)) foreach ((array)$themes as $theme_name) {
	// Show execution progress
	echo "<br><b>PROCESSING THEME \"".$theme_name."\":</b><br><br>\r\n";
	// Create theme folder
	mkdir_m_with_indexhtm(TPLS_LIB_PATH. $theme_name);
	// Get templates from the current theme
	$Q = $GLOBALS['db']->query("SELECT * FROM `".dbt_templates."` WHERE `theme_name`='".$theme_name."' AND `active`='1'");
	// Process files
	while ($A = @$GLOBALS['db']->fetch_assoc($Q)) {
		$stpl_name	= $A["name"];
		$text		= stripslashes($A["text"]);
		// Create subfolder if needed for template
		$sub_dir_name = substr($stpl_name, 0, -strlen(basename($stpl_name)));
		if (strlen($sub_dir_name)) mkdir_m_with_indexhtm(TPLS_LIB_PATH.$theme_name."/".$sub_dir_name);
		// Put template file contents
		file_put_contents(TPLS_LIB_PATH.$theme_name."/".$stpl_name.".stpl", $text);
		// Show execution progress
		echo "<b>".$stpl_name."</b> (".strlen($text)." bytes) - <b style='color:green;'>OK</b><br>\r\n";
	}
}
//-----------------------------------------------------------------------------
?>