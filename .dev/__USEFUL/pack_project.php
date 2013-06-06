<?php

/*
* T_ML_COMMENT does not exist in PHP 5.
* The following three lines define it in order to
* preserve backwards compatibility.
*
* The next two lines define the PHP 5 only T_DOC_COMMENT,
* which we will mask as T_ML_COMMENT for PHP 4.
*/
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
	// Do compress spaces
	$replace_pairs = array(
		"( "		=> "(",
		" )"		=> ")",
		"{ "		=> "{",
		" }"		=> "}",
		") "		=> ")",
		"} "		=> "}",
		"; "		=> ";",
		"if ("		=> "if(",
		"for ("		=> "for(",
		"while ("	=> "while(",
		", "		=> ",",
		" ="		=> "=",
		"= "		=> "=",
		" ? "		=> "?",
		"=> "		=> "=>",
		" =>"		=> "=>",
		" !="		=> "!=",
		" ||"		=> "||",
		"|| "		=> "||",
		" &&"		=> "&&",
		"&& "		=> "&&",
		" >"		=> ">",
		"> "		=> ">",
		" <"		=> "<",
		"< "		=> "<",
	);
	$output = str_replace(array("\r","\n"), "", $output);
	$output = str_replace("\t", " ", $output);
	$output = preg_replace("/[\s\t]{2,}/ims", " ", $output);
	$output = str_replace(array_keys($replace_pairs), array_values($replace_pairs), $output);
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

// Get server OS
define('OS_WINDOWS', substr(PHP_OS, 0, 3) == 'WIN');

/**
* Create multiple dirs at one time (eg. mkdir_m("some_dir1/some_dir2/some_dir3"))
* 
* @access	public
* @param	$dir_name			string
* @param	$dir_mode			octal
* @param	$create_index_htmls	bool
* @param	$start_folder		string
* @return	int		Status code
*/
function mkdir_m($dir_name, $dir_mode = 0755, $create_index_htmls = 0, $start_folder = "") {
	if (!strlen($dir_name)) {
		return 0;
	}
	// Default start folder to look at
	if (!strlen($start_folder)) {
//		$start_folder = INCLUDE_PATH;
//		$start_folder = realpath("./")."/";
	}
	$old_mask = umask(0);
	// Default dir mode
	if (empty($dir_mode)) {
		$dir_mode = 0755;
	}
	// Process given file name
	if (!file_exists($dir_name)) {
		$base_path = OS_WINDOWS ? "" : "/";
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
			} elseif ($CHECK_IF_WRITABLE && !is_writable(dirname($base_path))) {
				trigger_error("ProEngine Error: dir \"".dirname($base_path)."\" is not writable", E_USER_WARNING);
			}
			// Try to create sub dir
			if (!mkdir($base_path, $dir_mode)) {
				trigger_error("ProEngine Error: Cannot create \"".$base_path."\"", E_USER_WARNING);
				return -1;
			}
			chmod($base_path, $dir_mode);
		}
	} elseif (!is_dir($dir_name)) {
		trigger_error("ProEngine Error: ".$dir_name." exists and is not a directory", E_USER_WARNING);
		return -2;
	}
	// Create empty index.html in new folder if needed
	if ($create_index_htmls) {
		$index_file_path = str_replace(array('\/',"//"), "/", $dir_name. "/index.html");
		if (!file_exists($index_file_path)) {
			file_put_contents($index_file_path, "");
		}
	}
	umask($old_mask);
	return 0;
}

//-----------------------------------------------------------
//-----------------------------------------------------------
// dirs
$source_dir		= "./yf/";
$compressed_dir	= "./yf_COMPRESSED/";
// Get files array
$files_to_compress = scan_dir($source_dir, 1, "#.*\.(php)\$#", "#(__SANDBOX|__DOCS|__SAMPLES|__TESTS|share\/|pear|xpm2|smarty|adodb|domit|html2fpdf|feedcreator|convertcharset)\/#");
//print_r($files_to_compress);
foreach ((array)$files_to_compress as $cur_file_path) {
	$compressed_file_path	= str_replace($source_dir, $compressed_dir, $cur_file_path);
	$compressed_file_dir	= dirname($compressed_file_path);
	// Check if target folder exists
	if (!file_exists($compressed_file_dir)) {
//echo $compressed_file_dir."<br />";
		mkdir_m($compressed_file_dir, 0777);
	}
	// Do compress
	$output .= _do_compress_php_file ($cur_file_path, $compressed_file_path);
}
echo $output;

?>