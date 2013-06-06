<?php

if (!function_exists('_mkdir_m')) {
	function _mkdir_m($path_to_create = "", $dir_mode = 0755, $create_index_htmls = 0, $start_folder = "") {
		if (file_exists($path_to_create)) {
			return true;
		}
		$DIR_OBJ = $GLOBALS['main']->init_class("dir", "classes/");
		if (is_object($DIR_OBJ)) {
			return $DIR_OBJ->mkdir_m($path_to_create, $dir_mode, $create_index_htmls, $start_folder);
		}
		return false;
	}
}

class compat_main {

	function &init_class ($class_name, $custom_path = "", $params = "") {
		if (is_object($GLOBALS['modules'][$class_name])) {
			return $GLOBALS['modules'][$class_name];
		}
		$_class_name = $class_name;
		$fname = INCLUDE_PATH."classes/".$_class_name.".class.php";
		if (file_exists($fname)) {
			require $fname;
			$GLOBALS['modules'][$class_name] = new $_class_name($params);
		} else {
			$_class_name = "yf_".$class_name;
			$fname = INCLUDE_PATH."classes/".$_class_name.".class.php";
			if (file_exists($fname)) {
				require $fname;
				$GLOBALS['modules'][$class_name] = new $_class_name($params);
			}
		}
		// Return reference to the module object
		if (is_object($GLOBALS['modules'][$class_name])) {
			return $GLOBALS['modules'][$class_name];
		}
		return null;
	}

	/**
	* Evaluate given code as PHP code
	*/
	function _eval_code ($code_text = "", $as_string = 1) {
		return eval("return ".($as_string ? "\"".$code_text."\"" : $code_text)." ;");
	}
}

class compat_tpl {

	var $_STPL_EXT			= ".stpl";
	var $_THEMES_PATH		= "templates/";

	var $_cond_operators	= array("eq"=>"==","ne"=>"!=","gt"=>">","lt"=>"<","ge"=>">=","le"=>"<=");
	var $_math_operators	= array("and"=>"&&","xor"=>"xor","or"=>"||","+"=>"+","-"=>"-");
	var $_PATTERN_IF		= "/\{if\([\"']{0,1}([\w\s\.\-\+\%]+?)[\"']{0,1}[\s\t]+(eq|ne|gt|lt|ge|le)[\s\t]+[\"']{0,1}([\w\s\-\#]*)[\"']{0,1}([^\(\)\{\}\n]*)\)\}/ims";
	var $_STPL_PATTERNS		= array(
		// EXAMPLE:		{const("SITE_NAME")}
		"/(\{const\([\"']{0,1})([a-z_][a-z0-9_]+?)([\"']{0,1}\)\})/ie"
			=> "\$GLOBALS['main']->_eval_code('\$2', 0)",
	);

	/** Cutted version of the original parse method */
	function parse($name, $replace = array(), $params = array()) {
		$fpath = INCLUDE_PATH. $this->_THEMES_PATH. $name. $this->_STPL_EXT;
		$string = file_get_contents($fpath);

		$string = $this->_replace_std_patterns($string, $name, $replace, $params);
		$string = $this->_process_conditions($string, $replace, $name);

		foreach ((array)$replace as $item => $value) {
			$string = str_replace("{".$item."}", $value, $string);
		}
		return $string;
	}

	/**
	* Replace standard patterns
	*/
	function _replace_std_patterns($string, $name = "", $replace = array(), $params = array()) {
		return preg_replace(array_keys($this->_STPL_PATTERNS), str_replace("{tpl_name}", $name.$this->_STPL_EXT, array_values($this->_STPL_PATTERNS)), $string, --$this->STPL_REPLACE_LIMIT > 0 ? $this->STPL_REPLACE_LIMIT : -1);
	}

	/**
	* Conditional execution
	*/
	function _process_conditions ($string = "", $replace = array(), $stpl_name = "") {
		// Fast check for the patterns, also check for the resurse level
		if (false === strpos($string, "{/if}") || empty($string)) {
			return $string;
		}
		// Start processing
		if (!preg_match_all($this->_PATTERN_IF, $string, $m)) {
			return $string;
		}
		// Important!
		$string = str_replace(array("<"."?", "?".">"), array("&lt;?", "?&gt;"), $string);
		// Process matches
		foreach ((array)$m[0] as $k => $v) {
			$part_left		= $this->_prepare_cond_text($m[1][$k], $replace);
			$cur_operator	= $this->_cond_operators[strtolower($m[2][$k])];
			$part_right		= $m[3][$k];
			if ($part_right{0} == "#") {
				$part_right = $replace[ltrim($part_right, "#")];
			}
			if (!is_numeric($part_right)) {
				$part_right = "\"".$part_right."\"";
			}
			if (empty($part_left)) {
				$part_left = "\"\"";
			}
			$part_other		= "";
			// Possible multi-part condition found
			if ($m[4][$k]) {
				$_tmp_parts = preg_split("/[\s\t]+(and|xor|or)[\s\t]+/ims", $m[4][$k], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
				if ($_tmp_parts) {
					$_tmp_count = count($_tmp_parts);
				}
				for ($i = 1; $i < $_tmp_count; $i+=2) {
					$_tmp_parts[$i] = $this->_process_multi_conds($_tmp_parts[$i], $replace);
					if (!strlen($_tmp_parts[$i])) {
						unset($_tmp_parts[$i]);
						unset($_tmp_parts[$i - 1]);
					}
				}
				if ($_tmp_parts) {
					$part_other = " ". implode(" ", (array)$_tmp_parts);
				}
			}
			$new_code		= "<"."?p"."hp if(".$part_left." ".$cur_operator." ".$part_right.$part_other.") { ?>";
			$string			= str_replace($v, $new_code, $string);
		}
		$string = str_replace("{else}", "<"."?p"."hp } else { ?".">", $string);
		$string = str_replace("{/if}", "<"."?p"."hp } ?".">", $string);
		// Evaluate and catch result
		ob_start();
		$result = @eval("?>".$string."<"."?p"."hp return 1;");
		$string = ob_get_contents();
		ob_clean();
		// Throw warning if result is wrong
		if (!$result) {
			trigger_error("STPL: ERROR: wrong condition in template \"".$stpl_name."\"", E_USER_WARNING);
		}
		return $string;
	}

	/**
	* Multi-condition special parser
	*/
	function _process_multi_conds ($cond_text = "", $replace = array()) {
		if (!preg_match($this->_PATTERN_MULTI_COND, $cond_text, $m)) {
			return "";
		}
		// Process matches
		$part_left		= $this->_prepare_cond_text($m[1], $replace);
		$cur_operator	= $this->_cond_operators[strtolower($m[2])];
		$part_right		= $m[3];
		if ($part_right{0} == "#") {
			$part_right = $replace[ltrim($part_right, "#")];
		}
		if (!is_numeric($part_right)) {
			$part_right = "\"".$part_right."\"";
		}
		if (empty($part_left)) {
			$part_left = "\"\"";
		}
		return $part_left." ".$cur_operator." ".$part_right;
	}

	/**
	* Prepare text for "_process_conditions" method
	*/
	function _prepare_cond_text ($cond_text = "", $replace = array()) {
		$prepared_array = array();
		// Try to prepare left part
		foreach (explode(" ", str_replace("\t","",$cond_text)) as $tmp_k => $tmp_v) {
			$res_v = "";
			// Value from $replace array (DO NOT replace "array_key_exists()" with "isset()" !!!)
			if (array_key_exists($tmp_v, $replace)) {
				if (is_array($replace[$tmp_v])) {
					$res_v = $replace[$tmp_v] ? "(\"1\")" : "(\"\")";
				} else {
					$res_v = "\$replace['".$tmp_v."']";
				}
			// Arithmetic operators (currently we allow only "+" and "-")
			} elseif (isset($this->_math_operators[$tmp_v])) {
				$res_v = $this->_math_operators[$tmp_v];
			// Constant
			} elseif (false !== strpos($tmp_v, "const.")) {
				$res_v = substr($tmp_v, strlen("const."));
				if (!defined($res_v)) {
					$res_v = "";
				}
			// Global array element or sub array
			} elseif (false !== strpos($tmp_v, ".")) {
				$try_elm = substr($tmp_v, 0, strpos($tmp_v, "."));
				$try_elm2 = "['".str_replace(".","']['",substr($tmp_v, strpos($tmp_v, ".") + 1))."']";
				// Global array
				if (isset($this->_avail_arrays[$try_elm])) {
					$res_v = "\$".$this->_avail_arrays[$try_elm].$try_elm2;
				// Sub array
				} elseif (isset($replace[$try_elm]) && is_array($replace[$try_elm])) {
					$res_v = "\$replace['".$try_elm."']".$try_elm2;
				}
			// Simple number or string, started with "%"
			} elseif ($tmp_v{0} == "%" && strlen($tmp_v) > 1) {
				$res_v = "\"".str_replace("\"", "\\\"", substr($tmp_v, 1))."\"";
			} else {
				// Do not touch!
				// Variable or condition not found
			}
			// Add prepared element
			if ($res_v != "") {
				$prepared_array[$tmp_k] = $res_v;
			}
		}
		return implode(" ", $prepared_array);
	}
}

class compat_common {
	function get_file_ext ($file_path = "") {
		$_tmp = pathinfo($file_path);
		return $_tmp["extension"];
	}
}

$GLOBALS["main"]	= new compat_main();
$GLOBALS["tpl"]		= new compat_tpl();
$GLOBALS["common"]	= new compat_common();
