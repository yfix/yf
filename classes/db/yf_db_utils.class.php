<?php

/**
 */
class yf_db_utils {

	/**
	*/
	function create_database($name, $options = array()) {
// TODO
	}

	/**
	*/
	function drop_database($name, $options = array()) {
// TODO
	}

	/**
	*/
	function alter_database($name, $options = array()) {
// TODO
	}

	/**
	*/
	function rename_database($name, $new_name) {
// TODO
	}

	/**
	*/
	function create_table($name, $options = array()) {
// TODO
	}

	/**
	*/
	function drop_table($name, $options = array()) {
// TODO
	}

	/**
	*/
	function alter_table($name, $options = array()) {
// TODO
	}

	/**
	*/
	function rename_table($name, $new_name) {
// TODO
	}

	/**
	*/
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
					else if ($string_start == '`' || $sql[$i-1] != "\\") {
						$string_start	  = '';
						$in_string		 = FALSE;
						break;
					}
					// one or more Backslashes before the presumed end of string...
					else {
						// ... first checks for escaped backslashes
						$j					 = 2;
						$escaped_backslash	 = FALSE;
						while ($i-$j > 0 && $sql[$i-$j] == "\\") {
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
					header('X-YFPing: Pong');
				}
			}
		}
		// add any rest to the returned array
		if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql)) {
			$ret[] = array('query' => $sql, 'empty' => $nothing);
		}
		return TRUE;
	}
}
