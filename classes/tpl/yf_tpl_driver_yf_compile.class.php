<?php

/**
* Framework template engine compile extension code
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_tpl_driver_yf_compile {

	/**
	*/
	function _init() {
		$tpl_driver_yf = _class('tpl_driver_yf', 'classes/tpl/');
		$this->_cond_operators = $tpl_driver_yf->_cond_operators;
		$this->_math_operators = $tpl_driver_yf->_math_operators;
	}

	/**
	*/
	function _process_patterns($name, array $replace, $string) {
		$_this = $this;

		$start = '<'.'?p'.'hp ';
		$end = ' ?'.'>';

		$patterns = array(
			// !! Keep this pattern on top
			'/\{(else)\}/i' => function($m) use ($start, $end) {
				return $start. '} else {'. $end;
			},
			'/\{catch\([\s\t]*["\']{0,1}([\w_-]+?)["\']{0,1}[\s\t]*\)\}(.*?)\{\/catch\}/ims' => function($m) use ($start, $end) {
				return $start. 'ob_start();'. $end. $m[2]. $start. '$replace[\''.$m[1].'\'] = trim(ob_get_clean());'. $end;
			},
			'/\{(t|translate|i18n)\(\s*["\']{0,1}(.*?)["\']{0,1}\s*\)\}/ims' => function($m) use ($start, $end) {
				$str = addslashes($m[2]);
				$str = preg_replace('/\{([a-z0-9_-]+)\}/i', '\'.$replace[\'\1\'].\'', $str);
				return $start. 'echo _class(\'tpl\')->_i18n_wrapper(stripslashes(\''.$str.'\'), $replace);'. $end;
			},
			'/\{const\(\s*["\']{0,1}([a-z_][a-z0-9_]+?)["\']{0,1}\s*\)\}/i' => function($m) use ($start, $end) {
				$m[1] = trim($m[1]);
				return $start. 'echo (strlen(\''.$m[1].'\') && defined(\''.$m[1].'\') ? constant(\''.$m[1].'\') : \'\');'. $end;
			},
			'/\{conf\(\s*["\']{0,1}([a-z_][a-z0-9_:]+?)["\']{0,1}\s*\)\}/i' => function($m) use ($start, $end) {
				return $start. 'echo conf(\''.$m[1].'\');'. $end;
			},
			'/\{module_conf\(\s*["\']{0,1}([a-z_][a-z0-9_:]+?)["\']{0,1}\s*,\s*["\']{0,1}([a-z_][a-z0-9_:]+?)["\']{0,1}\s*\)\}/i' => function($m) use ($start, $end) {
				return $start. 'echo module_conf(\''.$m[1].'\',\''.$m[2].'\');'. $end;
			},
			// ifs compiling. NOTE: pattern differs from original adding \#\. symbols, etc
			'/\{if\(\s*["\']{0,1}([\w\s\.+%#-]+?)["\']{0,1}[\s\t]+(eq|ne|gt|lt|ge|le|mod)[\s\t]+["\']{0,1}([\w\-\#]*)["\']{0,1}([^\(\)\{\}\n]*)\s*\)\}/ims' => function($m) use ($start, $end, $_this) {
				return $start. 'if ('.$_this->_compile_prepare_condition($m[1], $m[2], $m[3], $m[4]).') {'. $end;
			},
			// if_funcs compiling
			'/\{if_(?P<func>[a-z0-9_:]+)\(\s*["\']{0,1}([\w\s\.+%-]+?)["\']{0,1}[\s\t]*\)\}/ims' => function($m) use ($start, $end, $_this) {
				return $start. 'if ('.$_this->_compile_if_func_condition($m).') {'. $end;
			},
			// foreach pattern compilation
			'/\{foreach\(\s*["\']{0,1}([\w\s\.-]+)["\']{0,1}\s*\)\}((?![^\{]*?\{foreach\(\s*["\']{0,1}?).*?)\{\/foreach\}/is' => function($m) use ($start, $end, $_this) {
				return $start. $_this->_compile_foreach($m). $end;
			},
			// if ending tag
			'/\{\/if\}/i' => function($m) use ($start, $end) {
				return $start. '}'. $end;
			},
			// Common replace vars
			'/\{([a-z0-9_-]+)\}/i' => function($m) use ($start, $end) {
				return $start. 'echo $replace[\''.$m[1].'\'];'. $end;
			},
			// Second level vars
			'/\{([a-z0-9_-]+)\.([a-z0-9_-]+)\}/i' => function($m) use ($start, $end) {
				$global_arrays = tpl()->_avail_arrays;
				$is_global = is_array($global_arrays) && array_key_exists($m[1], $global_arrays);
				return $start. 'echo '.($is_global ? '$'.$global_arrays[$m[1]].'[\''.$m[2].'\']' : '$replace[\''.$m[1].'\'][\''.$m[2].'\']').';'. $end;
			},
			// Variable filtering like in Smarty/Twig. Examples: {var1|trim} {var1|urlencode|trim} {var1|_prepare_html} {var1|my_func} {sub1.var1|trim}
			'/\{([a-z0-9_-]+)\|([a-z0-9_\|-]+)\}/i' => function($m) use ($start, $end) {
				return $start. 'echo _class(\'tpl\')->_process_var_filters($replace[\''.$m[1].'\'],\''.$m[2].'\');'. $end;
			},
			// Second level variables with filters
			'/\{([a-z0-9_-]+)\.([a-z0-9_-]+)\|([a-z0-9_\|-]+)\}/i' => function($m) use ($start, $end) {
				return $start. 'echo _class(\'tpl\')->_process_var_filters($replace[\''.$m[1].'\'][\''.$m[2].'\'],\''.$m[3].'\');'. $end;
			},
			// Vars inside foreach with filters
			'/\{\#\.([a-z0-9_-]+)\|([a-z0-9_\|-]+)\}/i' => function($m) use ($start, $end) {
				return $start. 'echo _class(\'tpl\')->_process_var_filters($_v[\''.$m[1].'\'],\''.$m[2].'\');'. $end;
			},
			'/\{(execute|exec_cached)\(\s*["\']{0,1}\s*([\w\-]+)\s*[,;]\s*([\w\-]+)\s*[,;]{0,1}\s*([^"\'\)\}]*)["\']{0,1}\s*\)\}/i' => function($m) use ($start, $end, $name) {
				$is_cached = (false !== strpos($m[1], '_cached'));
				return $start.'echo main()->_execute(\''.$m[2].'\',\''.$m[3].'\',\''.$m[4].'\',\''.$name.'\',0,'.($is_cached ? 'true' : 'false').');'.$end;
			},
			'/\{(exec_last|execute_shutdown)\(\s*["\']{0,1}\s*([\w\-]+)\s*[,;]\s*([\w\-]+)\s*[,;]{0,1}\s*([^"\'\)\}]*)["\']{0,1}\s*\)\}/i' => function($m) use ($start, $end) {
				return $start.'/*exec_last_start*/echo main()->_execute(\''.$m[2].'\',\''.$m[3].'\',\''.$m[4].'\',\''.$name.'\',0,false);/*exec_last_end*/'.$end;
			},
			'/\{block\(\s*([\w\-]+)\s*[,;]{0,1}\s*([^"\'\)\}]*)["\']{0,1}\s*\)\}/i' => function($m) use ($start, $end, $name) {
				return $start.'echo main()->_execute(\'graphics\',\'_show_block\',\'name='.$m[1].';'.$m[2].'\',\''.$name.'\',0,false);'.$end;
			},
			'/\{tip\(\s*["\']{0,1}([\w\.#-]+)["\']{0,1}[,]{0,1}["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims' => function($m) use ($start, $end) {
				return $start.'echo _class_safe("graphics")->_show_help_tip(array("tip_id"=>\''.$m[1].'\',"tip_type"=>\''.$m[2].'\'));'.$end;
			},
			'/\{itip\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims' => function($m) use ($start, $end) {
				return $start.'echo _class_safe("graphics")->_show_inline_tip(array("text"=>\''.$m[1].'\'));'.$end;
			},
			'/\{(e|user_error)\(\s*["\']{0,1}([\w\.-]+)["\']{0,1}\s*\)\}/ims' => function($m) use ($start, $end) {
				return $start.'echo common()->_show_error_inline(\''.$m[2].'\');'.$end;
			},
			'/(\{(include|include_if_exists)\(\s*["\']{0,1})\s*([@:\w\\/\.]+)\s*["\']{0,1}?\s*[,;]{0,1}\s*([^"\'\)\}]*)\s*(["\']{0,1}\s*\)\})/i' => function($m) use ($start, $end) {
				$if_exists = (false !== strpos($m[1], '_if_exists'));
				return $start. 'echo $this->_include_stpl(\''.$m[3].'\',\''.$m[4].'\',$replace,'.($if_exists ? 'true' : 'false').');'. $end;
			},
			'/(\{eval_code\()([^\}]+?)(\)\})/i' => function($m) use ($start, $end) {
				return $start. 'echo '.$m[2].';'. $end;
			},
			'/\{cleanup\(\s*\)\}(.*?)\{\/cleanup\}/ims' => function($m) use ($start, $end) {
				return $start. 'ob_start();'. $end. $m[1]. $start. '$__content_to_clean = ob_get_clean(); echo trim(str_replace(array("\r","\n","\t"),"",stripslashes($__content_to_clean))); '. $end;
			},
   			'/\{ad\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims' => function($m) use ($start, $end) {
				return $start. 'echo module_safe("advertising")->_show(array("ad"=>\''.$m[1].'\'));'. $end;
			},
			'/\{url\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims' => function($m) use ($start, $end) {
				return $start. 'echo _class(\'tpl\')->_generate_url_wrapper(\''.$m[1].'\');'. $end;
			},
			'/\{form_row\(\s*["\']{0,1}[\s\t]*([a-z0-9_-]+)[\s\t]*["\']{0,1}([\s\t]*,[\s\t]*["\']{1}([^"\']*)["\']{1})?([\s\t]*,[\s\t]*["\']{1}([^"\']*)["\']{1})?([\s\t]*,[\s\t]*["\']{1}([^"\']*)["\']{1})?\s*\)\}/ims' => function($m) use ($start, $end) {
				return $start. 'echo _class("form2")->tpl_row(\''.$m[1].'\',$replace,\''.$m[3].'\',\''.$m[5].'\',\''.$m[7].'\');'. $end;
			},
			'/\{(css|require_css|js|require_js)\(\s*["\']{0,1}([^"\'\)\}]*?)["\']{0,1}\s*\)\}\s*(.+?)\s*{\/(\1)\}/ims' => function($m) use ($start, $end) {
				return $start. 'echo '.$m[1].'(\''.$m[3].'\', _attrs_string2array(\''.$m[2].'\'));'. $end;
			},
			// DEBUG_MODE patterns
			'/(\{_debug_get_replace\(\)\})/i' => function($m) use ($start, $end) {
				return $start. 'echo (DEBUG_MODE && is_array($replace) ? "<pre>".print_r(array_keys($replace),1)."</pre>" : "");'. $end;
			},
			'/(\{_debug_get_vars\(\)\})/i' => function($m) use ($start, $end) {
				return $start. 'echo $this->_debug_get_vars($string);'. $end;
			},
		);
		foreach ((array)$patterns as $pattern => $callback) {
			$string = preg_replace_callback($pattern, $callback, $string);
		}
		// Move exec_last into very bottom of the template
		if (false !== strpos($string, '/*exec_last_start*/')) {
			$exec_lasts = array();
			$string = preg_replace_callback('~/\*exec_last_start\*/(.+?)/\*exec_last_end\*/~', function($m) use (&$exec_lasts) {
				$exec_lasts[] = $m[0];
				return '';
			}, $string);

			if ($exec_lasts) {
				$string .= $start. implode('', $exec_lasts). $end;
			}
		}
		return $string;
	}

	/**
	* Compile given template into pure PHP code
	*/
	function _compile($name, $replace = array(), $string = "") {
		$_time_start = microtime(true);

		// For later check for templates changes
		if (_class('tpl')->COMPILE_CHECK_STPL_CHANGED) {
			$_md5_string = md5($string);
		}
		$compiled_dir = PROJECT_PATH. _class('tpl')->COMPILED_DIR;
		// Do not check dir existence twice
		if (!isset($this->_stpl_compile_dir_check)) {
			_mkdir_m($compiled_dir);
			$this->_stpl_compile_dir_check = true;
		}
		$file_name = $compiled_dir.'c_'.MAIN_TYPE.'_'.urlencode($name).'.php';

		$start = '<'.'?p'.'hp ';
		$end	= ' ?'.'>';

		$string = $this->_process_patterns($name, $replace, $string);

		// Images and uploads paths compile
		$web_path		= MAIN_TYPE_USER ? 'MEDIA_PATH' : 'ADMIN_WEB_PATH';
		$images_path	= $web_path. '._class(\'tpl\')->TPL_PATH. _class(\'tpl\')->_IMAGES_PATH';
		$to_replace = array(
			'"images/'		=> '"'.$start. 'echo '.$images_path.';'. $end,
			'\'images/'		=> '\''.$start. 'echo '.$images_path.';'. $end,
			'"uploads/'		=> '"'.$start. 'echo MEDIA_PATH. _class(\'tpl\')->_UPLOADS_PATH;'. $end,
			'\'uploads/'	=> '\''.$start. 'echo MEDIA_PATH. _class(\'tpl\')->_UPLOADS_PATH;'. $end,
			'src="uploads/'	=> 'src="'.$start. 'echo '.$web_path.'._class(\'tpl\')->_UPLOADS_PATH;'. $end,
		);
		$string = str_replace(array_keys($to_replace), $to_replace, $string);

		$string = '<'.'?p'.'hp if(!defined(\'YF_PATH\')) exit(); /* '.
			'date: '.gmdate('Y-m-d H:i:s').' GMT; '.
			'compile_time: '.common()->_format_time_value(microtime(true) - $_time_start).'; '.
			'name: '.$name.'; '.
			' */ '.
			'?'.'>'. PHP_EOL. $string;

		file_put_contents($file_name, $string);

		return $string;
	}

	/**
	* Prepare condition for the compilation
	*/
	function _compile_prepare_condition ($part_left = '', $cond_operator = '', $part_right = '', $add_cond = '') {
		$part_left = $this->_compile_prepare_left($part_left);
		if ($part_right{0} == '#') {
			$part_right = '$replace[\''.ltrim($part_right, '#').'\']';
		} else {
			$part_right = "'".$part_right."'";
		}
		if ($add_cond) {
			$_tmp_parts = preg_split("/[\s\t]+(and|xor|or)[\s\t]+/ims", $add_cond, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			if ($_tmp_parts) {
				$_tmp_count = count($_tmp_parts);
			}
			$pattern = '/["\']{0,1}([\w\s\.\-\+\%]+?)["\']{0,1}[\s\t]+(eq|ne|gt|lt|ge|le|mod)[\s\t]+["\']{0,1}([\w\s\-\#]*)["\']{0,1}/ims';
			for ($i = 1; $i < $_tmp_count; $i+=2) {
				if (preg_match($pattern, stripslashes($_tmp_parts[$i]), $m)) {
					$a_part_left	= $this->_compile_prepare_left($m[1]);
					$a_cur_operator	= $this->_cond_operators[strtolower($m[2])];
					$a_part_right	= $m[3];
					if ($a_part_right{0} == '#') {
						$a_part_right = '$replace[\''.ltrim($a_part_right, '#').'\']';
					}
					if (!is_numeric($a_part_right)) {
						$a_part_right = "'".$a_part_right."'";
					}
					if (empty($a_part_left)) {
						$a_part_left = "''";
					}
					$_tmp_parts[$i] = $a_part_left." ".$a_cur_operator." ".$a_part_right;
				} else {
					$_tmp_parts[$i] = '';
				}
				if (!strlen($_tmp_parts[$i])) {
					unset($_tmp_parts[$i]);
					unset($_tmp_parts[$i - 1]);
				}
			}
			if ($_tmp_parts) {
				$add_cond = implode(' ', (array)$_tmp_parts);
			} else {
				$add_cond = '';
			}
		}
		$op = $this->_cond_operators[strtolower($cond_operator)];
		// Special case for "mod". 
		// Examples: {if("id" mod 4)} content {/if}
		if ($op == '%') {
			$part_left = '!('.$part_left;
			$part_right = $part_right.')';
		}
		return trim($part_left.' '.$op.' '.$part_right.' '.$add_cond);
	}

	/**
	* Prepare left part of the condition
	*/
	function _compile_prepare_left ($part_left = '') {
		$part_left = trim($part_left);
		$_array_magick = array(
			'_key'	=> '$_k',
			'_val'	=> '$_v',
			'_num'	=> '$__f_counter',
			'_total'=> '$__f_total',
			'_first'=> '($__f_counter == 1)',
			'_last'	=> '($__f_counter == $__f_total)',
			'_even'	=> '($__f_counter % 2)',
			'_odd'	=> '(!($__f_counter % 2))',
		);
/*
			// Value from $replace array (DO NOT replace 'array_key_exists()' with 'isset()' !!!)
			if (array_key_exists($part_left, $replace)) {
				if (is_object($replace[$part_left]) && !method_exists($replace[$part_left], 'render')) {
					$res_v = get_object_vars($replace[$part_left]);
				}
				if (is_array($replace[$part_left])) {
					$res_v = $replace[$part_left] ? '("1")' : '("")';
				} else {
					$res_v = '$replace["'.$part_left.'"]';
				}
*/
		// Array item
		if (substr($part_left, 0, 2) == '#.') {
			$part_left = '$_v[\''.substr($part_left, 2).'\']';
		// Arithmetic operators (currently we allow only '+' and '-')
		} elseif (isset($this->_math_operators[$part_left])) {
			$part_left = $this->_math_operators[$part_left];
		// Array special magic keyword
		} elseif (isset($_array_magick[$part_left])) {
			$part_left = $_array_magick[$part_left];
		// Module config item
		} elseif (strpos($part_left, 'module_conf.') === 0) {
			list($mod_name, $mod_conf) = explode('.', substr($part_left, strlen('module_conf.')));
			$part_left = 'module_conf(\''.$mod_name.'\',\''.$mod_conf.'\')';
		// Configuration item
		} elseif (strpos($part_left, 'conf.') === 0) {
			$part_left = 'conf(\''.substr($part_left, strlen('conf.')).'\')';
		// Constant
		} elseif (false !== strpos($part_left, 'const.')) {
			$part_left = substr($part_left, strlen('const.'));
			$part_left = '(defined(\''.$part_left.'\') ? '.$part_left.' : \'\')';
		// Global array element or sub array
		} elseif (false !== strpos($part_left, '.')) {
			$try_elm = substr($part_left, 0, strpos($part_left, '.'));
			$try_elm2 = '[\''.str_replace('.', '\'][\'', substr($part_left, strpos($part_left, '.') + 1)). '\']';
			// Global array
			$avail_arrays = (array)_class('tpl')->_avail_arrays;
			if (isset($avail_arrays[$try_elm])) {
				$part_left = '$'.$avail_arrays[$try_elm].$try_elm2;
			// Sub array
			} else {
				$part_left = '$replace["'.$try_elm.'"]'.$try_elm2;
			}
		// Simple number or string, started with '%'
		} elseif ($part_left{0} == '%' && strlen($part_left) > 1) {
			$part_left = '"'.str_replace('"', '\"', substr($part_left, 1)).'"';
		} else {
			$part_left = '$replace[\''.$part_left.'\']';
		}
		return $part_left;
	}

	/**
	*/
	function _compile_if_func_condition($m) {
		$part_left = $this->_compile_prepare_left($m[2]);
		$func = trim($m['func']);
		$negate = false;
		if (substr($func, 0, 4) == 'not_') {
			$func = substr($func, 4);
			$negate = true;
		}
		// Example of supported class: {if_validate:is_natural_no_zero(data)} good {/if}
		if (false !== strpos($func, ':')) {
			list($class_name, $_func) = explode(':', $func);
			if (in_array($class_name, array('validate'))) {
				$func = '_class_safe("'.$class_name.'")->'.$_func;
			} else {
				return '';
			}
		// Example of supported functions: {if_empty(data)} good {/if} {if_not_isset(data.sub1)} good {/if} 
		} elseif (!function_exists($func) && !in_array($func, array('empty','isset'))) {
			return '';
		}
		// We need these wrappers to make code compatible with PHP 5.3, As this direct code fails: php -r 'var_dump(empty(""));', php -r 'var_dump(isset(""));', 
		if ($func == 'empty') {
			$func = '_empty';
		} elseif ($func == 'isset') {
			$func = '_isset';
		}
		return ($negate ? '!' : ''). $func. '('. (strlen($part_left) ? $part_left : '$replace["___not_existing_key__"]'). ')';
	}

	/**
	*/
	function _compile_foreach ($m) {
		$start = '<'.'?p'.'hp ';
		$end	= ' ?'.'>';

		$orig_arr_name = trim($m[1]);
		$foreach_arr_name = $orig_arr_name;
		$foreach_body = $m[2];
		// Example of elseforeach: {foreach(items)} {_key} = {_val} {elseforeach} No records {/foreach}
		$no_rows_text = '';
		$else_tag = '{elseforeach}';
		if (false !== strpos($foreach_body, $else_tag)) {
			list($else_before, $no_rows_text) = explode($else_tag, $foreach_body);
			$foreach_body = str_replace($else_tag. $no_rows_text, '', $foreach_body);
		}
		// vars inside foreach
		$foreach_body = preg_replace_callback('/\{\#\.([a-z0-9_-]+)\}/i', function($m) use ($start, $end) {
			return $start. 'echo $_v[\''.$m[1].'\'];'. $end;
		}, $foreach_body);

		$special_vars = array(
			'{_key}'	=> $start. 'echo $_k;'. $end,
			'{_val}'	=> $start. 'echo (is_array($_v) ? implode(",", $_v) : $_v);'. $end,
			'{_num}'	=> $start. 'echo $__f_counter;'. $end,
			'{_total}'	=> $start. 'echo $__f_total;'. $end,
		);
		$foreach_body = str_replace(array_keys($special_vars), $special_vars, $foreach_body);

		$foreach_arr_tag = '';
		// Support for deep arrays as main array
		if (false !== strpos($foreach_arr_name, '.')) {
			list($v1, $v2) = explode('.', $foreach_arr_name);
			$global_arrays = tpl()->_avail_arrays;
			$is_global = is_array($global_arrays) && array_key_exists($v1, $global_arrays);
			if ($is_global) {
				$foreach_arr_tag = '$'.$global_arrays[$v1].'[\''.$v2.'\']';
			} else {
				$foreach_arr_name = trim(str_replace('.', '\'][\'', $foreach_arr_name));
				$foreach_arr_tag = '$replace[\''.$foreach_arr_name.'\']';
			}
		} else {
			$foreach_arr_tag = '$replace[\''.$foreach_arr_name.'\']';
		}
		return '$__foreach_data = is_array('.$foreach_arr_tag.') ? '.$foreach_arr_tag.' : $this->_range_foreach('.(is_numeric($foreach_arr_name) ? intval($foreach_arr_name) : $foreach_arr_tag).'); '. PHP_EOL
			.'$__f_total = count($__foreach_data); $__f_counter = 0;'. PHP_EOL
			.'if ($__foreach_data) {'.PHP_EOL.'foreach ($__foreach_data as $_k => $_v) { $__f_counter++; '. PHP_EOL
			.$end. $foreach_body. $start. PHP_EOL
			.'}'.PHP_EOL.'} else {'.PHP_EOL.$end. $no_rows_text. $start.'}';
	}
}