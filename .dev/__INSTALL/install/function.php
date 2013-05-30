<?php
function error(){
	echo "Error #".mysql_errno().": ".mysql_error();
	exit;
}

function import ($sql_file, $prefix) {
	$splitted_sql = array();
	// Process SQL
	$sql_file_content = file_get_contents($sql_file);
	if (!empty($sql_file_content)) {
		split_sql($splitted_sql, $sql_file_content);
		// Execute SQL
		foreach ((array)$splitted_sql as $item_info) {
			if ($item_info["empty"] == 1) {
				continue;
			}
			$item_info["query"] = str_replace("%%prefix%%", $prefix, $item_info["query"]);

			// Try to use framework method if availiable
			if (is_object($GLOBALS['db'])) {
				$GLOBALS['db']->query($item_info["query"]);
			} else {
				mysql_query($item_info["query"]) or error();
			}
		}
	}
}

function split_sql(&$ret, $sql) {
	// do not trim
	$sql			= rtrim($sql, "\n\r");
	$sql_len		= strlen($sql);
	$char			= '';
	$string_start	= '';
	$in_string		= FALSE;
	$nothing	 	= TRUE;
	$time0			= time();
	$is_headers_sent = headers_sent();

	for ($i = 0; $i < $sql_len; ++$i) {
		$char = $sql[$i];
		// We are in a string, check for not escaped end of strings except for
		// backquotes that can't be escaped
		if ($in_string) {
			for (;;) {
				$i		 = strpos($sql, $string_start, $i);
				// No end of string found -> add the current substring to the
				// returned array
				if (!$i) {
					$ret[] = array('query' => $sql, 'empty' => $nothing);
					return TRUE;
				}
				// Backquotes or no backslashes before quotes: it's indeed the
				// end of the string -> exit the loop
				else if ($string_start == '`' || $sql[$i-1] != '\\') {
					$string_start	  = '';
					$in_string		 = FALSE;
					break;
				}
				// one or more Backslashes before the presumed end of string...
				else {
					// ... first checks for escaped backslashes
					$j					 = 2;
					$escaped_backslash	 = FALSE;
					while ($i-$j > 0 && $sql[$i-$j] == '\\') {
						$escaped_backslash = !$escaped_backslash;
						$j++;
					}
					// ... if escaped backslashes: it's really the end of the
					// string -> exit the loop
					if ($escaped_backslash) {
						$string_start  = '';
						$in_string	 = FALSE;
						break;
					}
					// ... else loop
					else {
						$i++;
					}
				}
			}
		}
		// lets skip comments (/*, -- and #)
		else if (($char == '-' && $sql_len > $i + 2 && $sql[$i + 1] == '-' && $sql[$i + 2] <= ' ') || $char == '#' || ($char == '/' && $sql_len > $i + 1 && $sql[$i + 1] == '*')) {
			$i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
			// didn't we hit end of string?
			if ($i === FALSE) {
				break;
			}
			if ($char == '/') $i++;
		}
		// We are not in a string, first check for delimiter...
		else if ($char == ';') {
			// if delimiter found, add the parsed part to the returned array
			$ret[]	  = array('query' => substr($sql, 0, $i), 'empty' => $nothing);
			$nothing	= TRUE;
			$sql		= ltrim(substr($sql, min($i + 1, $sql_len)));
			$sql_len	= strlen($sql);
			if ($sql_len) {
				$i	  = -1;
			} else {
				// The submited statement(s) end(s) here
				return TRUE;
			}
		}
		// ... then check for start of a string,...
		else if (($char == '"') || ($char == '\'') || ($char == '`')) {
			$in_string	= TRUE;
			$nothing	  = FALSE;
			$string_start = $char;
		} elseif ($nothing) {
			$nothing = FALSE;
		}
		// loic1: send a fake header each 30 sec. to bypass browser timeout
		$time1	 = time();
		if ($time1 >= $time0 + 30) {
			$time0 = $time1;
			if (!$is_headers_sent) {
				header('X-ProfyPing: Pong');
			}
		}
	}
	// add any rest to the returned array
	if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql)) {
		$ret[] = array('query' => $sql, 'empty' => $nothing);
	}
	return TRUE;
}

function ti($input_string, $args = 0) {
	$REPLACE_UNDERSCORE = true;
	$VARS_IGNORE_CASE	= true;
	$is_translated = false;
	$output_string = $input_string;
	// Prepare for case ignore
	if ($VARS_IGNORE_CASE) {
		$_source = $input_string;
		$input_string = strtolower($input_string);
		if ($REPLACE_UNDERSCORE) {
			$input_string = str_replace(" ", "_", $input_string);
		}
	}
	if (isset($GLOBALS['TI_VARS'][$_SESSION['INSTALL']["language_select"]][$input_string])) {
		$output_string = $GLOBALS['TI_VARS'][$_SESSION['INSTALL']["language_select"]][$input_string];
		$is_translated = true;
	} elseif (isset($GLOBALS['TI_VARS'][$_SESSION['INSTALL']["language_select"]][$output_string])) {
		$output_string = $GLOBALS['TI_VARS'][$_SESSION['INSTALL']["language_select"]][$output_string];
		$is_translated = true;
	}
	// Force replace underscore "_" chars into spaces " " (only if string not translated)
	if ($REPLACE_UNDERSCORE && !$is_translated) {
		$output_string = str_replace("_", " ", $_source);
	}
	// Replace with arguments
	if (!empty($args)) {
		$output_string = strtr($output_string, $args);
	}
	echo $output_string;
}

function rus2uni($str,$isTo = true) {
    $arr = array('ñ'=>'&#x451;','ð'=>'&#x401;');
    for($i=192;$i<256;$i++)
        $arr[chr($i)] = '&#x4'.dechex($i-176).';';
    $str =preg_replace(array('@([ -ï]) @i','@ ([ -ï])@i'),array('$1&#x0a0;','&#x0a0;$1'),$str);
    return strtr($str,$isTo?$arr:array_flip($arr));
}

// Recursive function that preserves keys of merged arrays
if (!function_exists('my_array_merge')) {
    function my_array_merge($a1, $a2) {
        foreach ((array)$a2 as $k => $v) { if (isset($a1[$k]) && is_array($a1[$k])) { if (is_array($a2[$k])) { 
            foreach ((array)$a2[$k] as $k2 => $v2) { if (isset($a1[$k][$k1]) && is_array($a1[$k][$k1])) { $a1[$k][$k2] += $v2; } else { $a1[$k][$k2] = $v2; } 
        } } else { $a1[$k] += $v; } } else { $a1[$k] = $v; } }
        return $a1;
    }
}
