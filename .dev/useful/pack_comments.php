<?php

define("DEBUG_MODE", 1);
define("YF_PATH", realpath("./yf")."/");
require YF_PATH."classes/yf_main.class.php";
$GLOBALS['main'] = new yf_main("user", 1, 0);

$DIR_OBJ = $GLOBALS['main']->init_class("dir", "classes/");

$sub_dirs = [
	YF_PATH."admin_modules/",
	YF_PATH."classes/",
	YF_PATH."modules/",
];
$pattern_include	= "/\.class\.php\$/i";
$pattern_exclude	= "/svn/ims";
$pattern_find		= "/\t[\s]{0,1}\*\s[\r\n]{1,2}\t[\s]{0,1}\*\s@access\t(public|private)[\r\n]{1,2}\t[\s]{0,1}\*\s@param[\r\n]{1,2}\t[\s]{0,1}\*\s@return[\r\n]{1,2}/ims";
$pattern_replace	= "";
$files = [];
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