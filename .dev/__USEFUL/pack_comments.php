<?php

define("DEBUG_MODE", 1);
define("PF_PATH", realpath("./PROFY_FRAMEWORK")."/");
require PF_PATH."classes/profy_main.class.php";
$GLOBALS['main'] = new profy_main("user", 1, 0);

$DIR_OBJ = $GLOBALS['main']->init_class("dir", "classes/");

$sub_dirs = array(
	PF_PATH."admin_modules/",
	PF_PATH."classes/",
	PF_PATH."modules/",
);
$pattern_include	= "/\.class\.php\$/i";
$pattern_exclude	= "/svn/ims";
$pattern_find		= "/\t[\s]{0,1}\*\s[\r\n]{1,2}\t[\s]{0,1}\*\s@access\t(public|private)[\r\n]{1,2}\t[\s]{0,1}\*\s@param[\r\n]{1,2}\t[\s]{0,1}\*\s@return[\r\n]{1,2}/ims";
$pattern_replace	= "";
$files = array();
foreach ((array)$sub_dirs as $_dir_name) {
	foreach ((array)$DIR_OBJ->scan_dir($_dir_name, 1, $pattern_include, $pattern_exclude) as $_file_path) {
		$files[] = $_file_path;
	}
}
foreach ((array)$files as $_id => $_file_path) {
	$contents = file_get_contents($_file_path);
	if (!preg_match($pattern_find, $contents)) {
		unset($files[$_id]);
		continue;
	}
	if (!is_null($pattern_replace)) {
		$contents = preg_replace($pattern_find, $pattern_replace, $contents);
		file_put_contents($_file_path, $contents);
	}
}

print_R($files);

?>