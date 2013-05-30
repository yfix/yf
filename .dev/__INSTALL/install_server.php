<?php

$PF_PATH = "./PROFY_FRAMEWORK/";

$exclude_array = array(
	"\.svn",
	"__DOCS",
	"__SAMPLES",
	"__SANDBOX",
	"__TESTS",
	"__USEFUL",
);
// Get selected file
if ($_GET["action"] == "get_file" || $_GET["id"]) {

	$file_name = urldecode($_GET["id"]);
	// Security
	$file_name = str_replace("..", "", $file_name);
	// Throw file contents
	if ($file_name && file_exists($PF_PATH. $file_name)) {
		// Throw headers
		header("Content-Type: application/force-download; name=\"".basename($file_name)."\"");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".@filesize($PF_PATH. $file_name));
		header("Content-Disposition: attachment; filename=\"".basename($file_name)."\"");
		// Throw content
		readfile($PF_PATH. $file_name);
	}
	exit;

// Show files list
} elseif (!$_GET["action"] || $_GET["action"] == "show") {

	foreach ((array)scan_dir($PF_PATH, 1, "", "/".implode("|", $exclude_array)."/i") as $_path) {
		echo substr($_path, strlen($PF_PATH))."\n";
	}

}

/**
* Get values from the multi-dimensional array
*/
//if (!function_exists("array_values_recursive")) {
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
//}

/**
* Scan dir
*/
function scan_dir ($start_dir, $FLAT_MODE = true, $include_pattern = "", $exclude_pattern = "", $level = null) {
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
			if (is_null($level) || $level > 0) {
				$tmp_file = scan_dir($item_name, $FLAT_MODE, $include_pattern, $exclude_pattern, is_null($level) ? $level : $level - 1);
			}
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
