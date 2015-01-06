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
			// YF tpl comments
			'/(\{\{--.*?--\}\})/ims' => function($m) use ($start, $end) {
				return '';
			},
			// !! Keep this pattern on top
			'/\{(else)\}/i' => function($m) use ($start, $end) {
				return $start. '} else {'. $end;
			},
			'/\{catch\([\s\t]*["\']{0,1}([\w_-]+?)["\']{0,1}[\s\t]*\)\}(.*?)\{\/catch\}/ims' => function($m) use ($start, $end) {
				return $start. 'ob_start();'. $end. $m[2]. $start. '$replace[\''.$m[1].'\'] = trim(ob_get_clean());'. $end;
			},
			'/\{(t|translate|i18n)\(\s*["\']{0,1}(.*?)["\']{0,1}\s*\)\}/ims' => function($m) use ($start, $end) {
				$str = preg_replace('/\{([a-z0-9_-]+)\}/i', '\'.$replace[\'\1\'].\'', addslashes($m[2]));
				return $start. 'echo _class(\'tpl\')->_i18n_wrapper(stripslashes(\''.$str.'\'), $replace);'. $end;
			},
			'/\{const\(\s*["\']{0,1}([a-z_][a-z0-9_]+?)["\']{0,1}\s*\)\}/i' => function($m) use ($start, $end) {
				$c = trim($m[1]);
				return $start. 'echo (defined(\''.$c.'\') ? constant(\''.$c.'\') : null);'. $end;
			},
			'/\{conf\(\s*["\']{0,1}([a-z_][a-z0-9_:]+?)["\']{0,1}\s*\)\}/i' => function($m) use ($start, $end) {
				return $start. 'echo conf(\''.$m[1].'\');'. $end;
			},
			'/\{module_conf\(\s*["\']{0,1}([a-z_][a-z0-9_:]+?)["\']{0,1}\s*,\s*["\']{0,1}([a-z_][a-z0-9_:]+?)["\']{0,1}\s*\)\}/i' => function($m) use ($start, $end) {
				return $start. 'echo module_conf(\''.$m[1].'\',\''.$m[2].'\');'. $end;
			},
			// ifs compiling. NOTE: pattern differs from original adding \#\. symbols, etc
			'/\{(?P<cond>if|elseif)\(\s*["\']{0,1}(?P<left>[\w\s\.+%#-]+?)["\']{0,1}[\s\t]+(?P<op>eq|ne|gt|lt|ge|le|mod)[\s\t]+["\']{0,1}(?P<right>[\w\-\#]*)["\']{0,1}(?P<multi_conds>[^\(\)\{\}\n]*)\s*\)\}/ims' => function($m) use ($start, $end, $_this) {
				return $start. $_this->_compile_prepare_ifs($m). $end;
			},
			// if_funcs compiling
			'/\{(?P<cond>if_or|if_and|elseif_or|elseif_and|if|elseif)_(?P<func>[a-z0-9_:]+)\(\s*["\']{0,1}(?P<left>[\w\s\.,+%-]+?)["\']{0,1}[\s\t]*\)\}/ims' => function($m) use ($start, $end, $_this) {
				return $start. $_this->_compile_if_funcs($m). $end;
			},
			// foreach pattern compilation
			'/\{(?P<func>foreach|foreach_exec)\(\s*["\']{0,1}(?P<key>[a-z0-9_\s\.,;=@-]+)["\']{0,1}\s*\)\}(?P<body>(?![^\{]*?\{\1\(\s*["\']{0,1}?).*?)\{\/\1\}/ims' => function($m) use ($start, $end, $_this) {
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
#				return $start. 'echo '.($is_global ? '$'.$global_arrays[$m[1]].'[\''.$m[2].'\']' : '($replace[\''.$m[1].'\'][\''.$m[2].'\'] ?: _class_safe(\''.$m[1].'\')->'.$m[2].')').';'. $end;
				return $start. 'echo '.($is_global ? '$'.$global_arrays[$m[1]].'[\''.$m[2].'\']' : '$replace[\''.$m[1].'\'][\''.$m[2].'\']').';'. $end;
			},
			// Variable filtering like in Smarty/Twig. Examples: {var1|trim} {var1|urlencode|trim} {var1|_prepare_html} {var1|my_func} {sub1.var1|trim}
			'/\{([a-z0-9_-]+)\|([a-z0-9_\|-]+)\}/i' => function($m) use ($start, $end) {
				return $start. 'echo _class(\'tpl\')->_process_var_filters($replace[\''.$m[1].'\'],\''.$m[2].'\');'. $end;
			},
			// Second level variables with filters
			'/\{([a-z0-9_-]+)\.([a-z0-9_-]+)\|([a-z0-9_\|-]+)\}/i' => function($m) use ($start, $end) {
#				return $start. 'echo _class(\'tpl\')->_process_var_filters($replace[\''.$m[1].'\'][\''.$m[2].'\'] ?: _class_safe(\''.$m[1].'\')->'.$m[2].', \''.$m[3].'\');'. $end;
				return $start. 'echo _class(\'tpl\')->_process_var_filters($replace[\''.$m[1].'\'][\''.$m[2].'\'], \''.$m[3].'\');'. $end;
			},
			// Vars inside foreach with filters
			'/\{\#\.([a-z0-9_-]+)\|([a-z0-9_\|-]+)\}/i' => function($m) use ($start, $end) {
				return $start. 'echo _class(\'tpl\')->_process_var_filters($_v[\''.$m[1].'\'],\''.$m[2].'\');'. $end;
			},
			'/\{(execute|exec_cached)\(\s*["\']{0,1}\s*([\w@\-]+)\s*[,;]\s*([\w@\-]+)\s*[,;]{0,1}\s*([^"\'\)\}]*)["\']{0,1}\s*\)\}/i' => function($m) use ($start, $end, $name) {
				$is_cached = (false !== strpos($m[1], '_cached'));
				return $start.'echo main()->_execute(\''.$m[2].'\',\''.$m[3].'\',\''.$m[4].'\',\''.$name.'\',0,'.($is_cached ? 'true' : 'false').');'.$end;
			},
			'/\{(exec_last|execute_shutdown)\(\s*["\']{0,1}\s*([\w@\-]+)\s*[,;]\s*([\w@\-]+)\s*[,;]{0,1}\s*([^"\'\)\}]*)["\']{0,1}\s*\)\}/i' => function($m) use ($start, $end) {
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
			'/\{(?P<func>css|require_css|js|require_js|asset|jquery|angularjs|backbonejs|reactjs|emberjs|sass|less|coffee)\(\s*["\']{0,1}(?P<args>[^"\'\)\}]*?)["\']{0,1}\s*\)\}\s*(?P<content>.+?)\s*{\/(\1)\}/ims' => function($m) use ($start, $end) {
				return $start. 'echo '.$m['func'].'(\''.str_replace("'", "\\'", $m['content']).'\', _attrs_string2array(\''.str_replace("'", "\\'", $m['args']).'\'));'. $end;
			},
			// Custom function (compatible with non-compile for extending tpl engine)
			'/\{(?P<name>[a-z0-9_:-]+)\(\s*["\']{0,1}(?P<args>[a-z0-9_:\.]+?)["\']{0,1}\s*\)\}/ims' => function($m) use ($start, $end) {
				return $start. 'echo $this->call_custom_pattern(\''.$m['name'].'\', \''.str_replace("'", "\\'", $m['args']).'\', null, $replace, $name);'. $end;
			},
			// Custom section (compatible with non-compile for extending tpl engine)
			'/\{(?P<name>[a-z0-9_:-]+)\(\s*["\']{0,1}(?P<args>[^"\'\)\}]*?)["\']{0,1}\s*\)\}\s*(?P<body>.+?)\s*{\/(\1)\}/ims' => function($m) use ($start, $end) {
				return $start. 'echo $this->call_custom_pattern(\''.$m['name'].'\', \''.str_replace("'", "\\'", $m['args']).'\', \''.str_replace("'", "\\'", $m['body']).'\', $replace, $name);'. $end;
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
		$compiled_dir = STORAGE_PATH. _class('tpl')->COMPILED_DIR;
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
	function _compile_prepare_ifs (array $m) {
		$cond = $m['cond'] === 'elseif' ? '} '.$m['cond'] : $m['cond'];
		$part_left = $this->_compile_prepare_cond($m['left']);
		$part_right = $this->_compile_prepare_cond($m['right'], $for_right = true);
		$op = $this->_cond_operators[strtolower(trim($m['op']))];
		// Special case for "mod". Examples: {if("id" mod 4)} content {/if}
		if ($op === '%') {
			$part_left = '!('.$part_left;
			$part_right = $part_right.')';
		}
		$add_cond = trim($m['multi_conds']);
		if ($add_cond) {
			$_this = $this;
			$pattern = '/[\s\t]*(?P<cond>and|xor|or)[\s\t]+["\']{0,1}(?P<left>[\w\s\.\-\+\%]+?)["\']{0,1}[\s\t]+(?P<op>eq|ne|gt|lt|ge|le|mod)[\s\t]+["\']{0,1}(?P<right>[\w\s\-\#]*)["\']{0,1}/ims';
			$add_cond = preg_replace_callback($pattern, function($m) use ($_this) {
				$a_cond	= trim($m['cond']);
				$a_left	= $_this->_compile_prepare_cond($m['left']);
				$a_op	= $_this->_cond_operators[strtolower(trim($m['op']))];
				$a_right = $_this->_compile_prepare_cond($m['right'], $for_right = true);
				// Special case for "mod". Examples: {if("id" mod 4)} content {/if}
				if ($a_op === '%') {
					$a_left = '!('.$a_left;
					$a_right = $a_right.')';
				}
				return $a_cond.' ('.$a_left.' '.$a_op.' '.$a_right.') ';
			}, $add_cond);
		}
		return $cond.'('.trim($part_left.' '.$op.' '.$part_right.' '.$add_cond).') {';
	}

	/**
	* Prepare left part of the condition
	*/
	function _compile_prepare_cond ($cond = '', $for_right = false) {
		$cond = trim($cond);
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
		$tmp_len = strlen($cond);
		$tmp_first = substr($cond, 0, 1);
		// Variable hint, starting from # or @
		if (($tmp_first === '@' || $tmp_first == '#') && substr($cond, 0, 2) !== '#.' && $tmp_len > 1) {
			$cond = substr($cond, 1);
			$tmp_len--;
		}
		// Number, also support for decimals and floats
		if (is_numeric($cond)) {
			if (!ctype_digit($cond)) {
				$cond = '\''.$cond.'\'';
			}
		// Simple number or string, started with '%'
		} elseif ($tmp_first === '%' && $tmp_len > 1) {
			$cond = '\''.str_replace("'", "\\'", substr($cond, 1)).'\'';
		} elseif (substr($cond, 0, 2) === '#.') {
			$cond = '$_v[\''.substr($cond, 2).'\']';
		// Arithmetic operators (currently we allow only '+' and '-')
		} elseif (isset($this->_math_operators[$cond])) {
			$cond = $this->_math_operators[$cond];
		// Array special magic keyword
		} elseif (isset($_array_magick[$cond])) {
			$cond = $_array_magick[$cond];
		// Module config item
		} elseif (strpos($cond, 'module_conf.') === 0) {
			list($mod_name, $mod_conf) = explode('.', substr($cond, strlen('module_conf.')));
			$cond = 'module_conf(\''.$mod_name.'\',\''.$mod_conf.'\')';
		// Configuration item
		} elseif (strpos($cond, 'conf.') === 0) {
			$cond = 'conf(\''.substr($cond, strlen('conf.')).'\')';
		// Constant
		} elseif (strpos($cond, 'const.') === 0) {
			$cond = str_replace("'", "\\'", substr($cond, strlen('const.')));
			$cond = '(defined(\''.$cond.'\') ? constant(\''.$cond.'\') : null)';
		// Global array element or sub array
		} elseif (false !== strpos($cond, '.')) {
			$cond = $this->_cond_sub_array($cond);
		} elseif ($tmp_len) {
			$cond = str_replace("'", "\\'", $cond);
			if ($for_right) {
				$cond = '(isset($replace[\''.$cond.'\']) ? $replace[\''.$cond.'\'] : \''.$cond.'\')';
			} else {
				$cond = '$replace[\''.$cond.'\']';
			}
		}
		return strlen($cond) ? $cond : 'null';
	}

	/**
	*/
	function _cond_sub_array($cond) {
		$pos = strpos($cond, '.');
		if ($pos === false) {
			return '$replace[\''.str_replace("'", "\\'", $cond).'\']';
		}
		$try_elm = substr($cond, 0, $pos);
		$try_elm2 = '[\''.str_replace('.', '\'][\'', substr($cond, $pos + 1)). '\']';
		// Global array
		$avail_arrays = (array)_class('tpl')->_avail_arrays;
		if (isset($avail_arrays[$try_elm])) {
			$cond = '$'.$avail_arrays[$try_elm].$try_elm2;
		// Sub array
		} else {
			$cond = '$replace["'.$try_elm.'"]'.$try_elm2;
		}
		return $cond;
	}

	/**
	*/
	function _compile_if_funcs(array $m) {
		$cond = trim($m['cond']);
		$multiple_cond = 'AND';
		if (in_array($cond, array('if_or','elseif_or'))) {
			$multiple_cond = 'OR';
		}
		if (in_array($cond, array('if','if_or','if_and'))) {
			$cond = 'if';
		} elseif (in_array($cond, array('elseif','elseif_or','elseif_and'))) {
			$cond = 'elseif';
		}
		$is_multiple = (strpos($m['left'], ',') !== false);
		if ($is_multiple) {
			$part_left = array();
			foreach (explode(',',trim($m['left'])) as $v) {
				$part_left[] = $this->_compile_prepare_cond($v);
			}
		} else {
			$part_left = $this->_compile_prepare_cond($m['left']);
		}
		$func = trim($m['func']);
		// We need these wrappers to make code compatible with PHP 5.3, As this direct code fails: php -r 'var_dump(empty(""));', php -r 'var_dump(isset(""));', 
		$funcs_map = array(
			'empty'		=> '_empty',
			'not_ok'	=> '_empty',
			'false' 	=> '_empty',
			'not_true' 	=> '_empty',
			'isset'		=> '_isset',
			'not_isset'	=> 'not__isset',
			'not_empty'	=> 'not__empty',
			'ok'		=> 'not__empty',
			'true'		=> 'not__empty',
			'not_false'	=> 'not__empty',
		);
		if (isset($funcs_map[$func])) {
			$func = $funcs_map[$func];
		}
		$negate = false;
		if (substr($func, 0, 4) === 'not_') {
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
		if ($is_multiple) {
			$center_tmp = array();
			foreach ($part_left as $v) {
				$v = trim($v);
				if (strlen($v)) {
					$center_tmp[] = ($negate ? '!' : ''). $func.'('.$v.')';
				}
			}
			if (!count($center_tmp)) {
				$center_cond = ($negate ? '!' : ''). $func. '($replace["___not_existing_key__"])';
			} else {
				$center_cond = '('.implode(') '.$multiple_cond.' (', $center_tmp).')';
			}
		} else {
			$center_cond = ($negate ? '!' : ''). $func. '('. (strlen($part_left) ? $part_left : '$replace["___not_existing_key__"]'). ')';
		}
		return ($cond === 'elseif' ? '} '.$cond : $cond).'('.$center_cond.') {';
	}

	/**
	*/
	function _compile_foreach ($m) {
		$start = '<'.'?p'.'hp ';
		$end	= ' ?'.'>';

		$func = trim($m['func']);
		$orig_arr_name = trim($m['key']);
		$foreach_arr_name = $orig_arr_name;
		$foreach_body = $m['body'];
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
		$foreach_data_tag = '';
		// Ability to directly execute some class method and do foreach over it like {foreach_exec(test,give_me_array)} {_key}={_val} {/foreach}
		if ($func === 'foreach_exec') {
			$foreach_data_tag = 'array()';
			if (preg_match('/(?P<object>[\w@\-]+)\s*[,;]\s*(?P<action>[\w@\-]+)\s*[,;]{0,1}\s*(?P<args>.*?)$/ims', $foreach_arr_name, $m_exec)) {
				$foreach_data_tag = '(array)main()->_execute(\''.$m_exec['object'].'\', \''.$m_exec['action'].'\', \''.$m_exec['args'].'\', $name. $this->_STPL_EXT, 0, $use_cache = false)';
			}
		// Support for deep arrays as main array
		} elseif (false !== strpos($foreach_arr_name, '.')) {
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
		if (!$foreach_data_tag) {
			$foreach_data_tag = 'is_array('.$foreach_arr_tag.') ? '.$foreach_arr_tag.' : $this->_range_foreach('.(is_numeric($foreach_arr_name) ? intval($foreach_arr_name) : $foreach_arr_tag).')';
		}
		return '$__foreach_data = '.$foreach_data_tag.'; '. PHP_EOL
			.'$__f_total = count($__foreach_data); $__f_counter = 0;'. PHP_EOL
			.'if ($__foreach_data) {'.PHP_EOL.'foreach ($__foreach_data as $_k => $_v) { $__f_counter++; '. PHP_EOL
			.$end. $foreach_body. $start. PHP_EOL
			.'}'.PHP_EOL.'} else {'.PHP_EOL.$end. $no_rows_text. $start.'}';
	}
}