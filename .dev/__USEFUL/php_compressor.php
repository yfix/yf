<?php

$_file_to_open = "pclzip_cutted.php";

$_file_to_save = "pclzip_compressed.php";

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

// Do execute compression engine
$result = _do_compress_php_file($_file_to_open, $_file_to_save);
echo $result;
?>