<?php

/**
* Framework template engine compile extension code
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_tpl_driver_yf_compile {

	/** @var array @conf_skip For "_process_conditions" */
	public $_cond_operators	= array(
		'eq' => '==',
		'ne' => '!=',
		'gt' => '>',
		'lt' => '<',
		'ge' => '>=',
		'le' => '<=',
		'mod' => '%',
	);

	/**
	*/
	function _init() {
		$start = '<'.'?p'.'hp ';
		$end = ' ?'.'>';

		$_this = $this;

		// Patterns replaces
		$this->_patterns = array(
			'/\{(t|translate|i18n)\(\s*["\']{0,1}(.*?)["\']{0,1}\s*\)\}/ims' => function($m) use ($start, $end) {
// TODO: better execute some wrapper that will convert this into simple call t('changeme %num', array('%num' => 5), 'ru')
				return $start. 'echo _class(\'tpl\')->_i18n_wrapper(\''.$m[2].'\', $replace);'. $end;
			},
			'/(\{const\(\s*["\']{0,1})([a-z_][a-z0-9_]+?)(["\']{0,1}\s*\)\})/i' => function($m) use ($start, $end) {
				return $start. 'echo (defined(\''.$m[2].'\') ? '.$m[2].' : \'\');'. $end;
			},
			'/(\{conf\(\s*["\']{0,1})([a-z_][a-z0-9_:]+?)(["\']{0,1}\s*\)\})/i' => function($m) use ($start, $end) {
				return $start. 'echo conf(\''.$m[2].'\');'. $end;
			},
			'/(\{module_conf\(\s*["\']{0,1})([a-z_][a-z0-9_:]+?)(["\']{0,1}\s*,\s*["\']{0,1})([a-z_][a-z0-9_:]+?)(["\']{0,1}\s*\)\})/i' => function($m) use ($start, $end) {
				return $start. 'echo module_conf(\''.$m[2].'\',\''.$m[3].'\');'. $end;
			},
			// Common replace vars
			'/\{([a-z0-9_-]+)\}/i' => function($m) use ($start, $end) {
				return $start. 'echo $replace[\''.$m[1].'\'];'. $end;
			},
			// Second level vars
			'/\{([a-z0-9_-]+)\.([a-z0-9_-]+)\}/i' => function($m) use ($start, $end) {
				return $start. 'echo $replace[\''.$m[1].'\'][\''.$m[2].'\'];'. $end;
			},
			// Variable filtering like in Smarty/Twig
			// Examples:	 {var1|trim}    {var1|urlencode|trim}   {var1|_prepare_html}   {var1|my_func}   {sub1.var1|trim}
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
			'/\{(else)\}/i' => function($m) use ($start, $end) {
				return $start. '} else {'. $end;
			},
			// !!! This pattern also differs from original adding \#\. symbols
// TODO: if funcs
			'/\{if\(\s*["\']{0,1}([\w\s\.+%#-]+?)["\']{0,1}[\s\t]+(eq|ne|gt|lt|ge|le|mod)[\s\t]+["\']{0,1}([\w\-\#]*)["\']{0,1}([^\(\)\{\}\n]*)\s*\)\}/ims' => function($m) use ($start, $end) {
				return $start. 'if ('.$this->_compile_prepare_condition($m[1], $m[2], $m[3], $m[4]).') {'. $end;
			},
			// !!! This is a completely written from scratch pattern for compilation only
// TODO: elseforeach
			'/\{foreach\(\s*["\']{0,1}([\w\s\.-]+)["\']{0,1}\s*\)\}/is' => function($m) use ($start, $end) {
				return $start.'$__f_total = count($replace[\''.$m[1].'\']); foreach (is_array($replace[\''.$m[1].'\']) ? $replace[\''.$m[1].'\'] : range(1, (int)$replace[\''.$m[1].'\']) as $_k => $_v) {$__f_counter++;'.$end;
			},
			// vars inside foreach
			'/\{\#\.([a-z0-9_-]+)\}/i' => function($m) use ($start, $end) {
				return $start. 'echo $_v[\''.$m[1].'\'];'. $end;
			},
			// Just closing foreach tags
			'/\{\/(if|foreach)\}/i' => function($m) use ($start, $end) {
				return $start. '}'. $end;
			},
			'/(\{execute\(\s*["\']{0,1})\s*([\w-]+)\s*[,;]\s*([\w-]+)\s*[,;]{0,1}([^"\'\)\}]*)(["\']{0,1}\s*\)\})/i' => function($m) use ($start, $end) {
// TODO: $name
				return $start.'echo main()->_execute(\''.$m[2].'\',\''.$m[3].'\',\''.$m[4].'\',\''.$name.'\',0,false);'.$end;
			},
			'/(\{exec_cached\(\s*["\']{0,1})\s*([\w-]+)\s*[,;]\s*([\w-]+)\s*[,;]{0,1}([^"\'\)\}]*)(["\']{0,1}\s*\)\})/i' => function($m) use ($start, $end) {
// TODO: $name
				return $start.'echo main()->_execute(\''.$m[2].'\',\''.$m[3].'\',\''.$m[4].'\',\''.$name.'\',0,true);'.$end;
			},
			'/\{block\(\s*([\w\-]+)\s*[,;]{0,1}\s*([^"\'\)\}]*)["\']{0,1}\s*\)\}/i' => function($m) use ($start, $end) {
// TODO: $name
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
			'/(\{include\(\s*["\']{0,1})\s*([@:\w\\/\.]+)\s*["\']{0,1}?\s*[,;]{0,1}\s*([^"\'\)\}]*)\s*(["\']{0,1}\s*\)\})/i' => function($m) use ($start, $end) {
				return $start. 'echo $this->_include_stpl(\''.$m[2].'\',\''.$m[3].'\',$replace);'. $end;
			},
			'/(\{include_if_exists\(\s*["\']{0,1})\s*([@:\w\\/\.]+)\s*["\']{0,1}?\s*[,;]{0,1}\s*([^"\'\)\}]*)\s*(["\']{0,1}\s*\)\})/i' => function($m) use ($start, $end) {
				return $start. 'echo $this->_include_stpl(\''.$m[2].'\',\''.$m[3].'\',$replace,true);'. $end;
			},
			'/(\{eval_code\()([^\}]+?)(\)\})/i' => function($m) use ($start, $end) {
				return $start. 'echo '.$m[2].';'. $end;
			},
			'/\{catch\(\s*["\']{0,1}([a-z0-9_]+?)["\']{0,1}\s*\)\}(.*?)\{\/catch\}/ims' => function($m) use ($start, $end) {
				return $start. 'ob_start();'. $end. $m[2]. $start. '$replace["'.$m[1].'"] = ob_get_clean();'. $end;
			},
			'/\{cleanup\(\s*\)\}(.*?)\{\/cleanup\}/ims' => function($m) use ($start, $end) {
				return $start. 'echo trim(str_replace(array("\r","\n","\t"),"",stripslashes(\''.$m[1].'\')));'. $end;
			},
   			'/\{ad\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims' => function($m) use ($start, $end) {
				return $start. 'echo module_safe("advertising")->_show(array("ad"=>\''.$m[1].'\'));'. $end;
			},
			'/\{url\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims' => function($m) use ($start, $end) {
				return $start. 'echo _class(\'tp\')->_generate_url_wrapper(\''.$m[1].'\');'. $end;
			},
			'/\{form_row\(\s*["\']{0,1}[\s\t]*([a-z0-9_-]+)[\s\t]*["\']{0,1}([\s\t]*,[\s\t]*["\']{1}([^"\']*)["\']{1})?([\s\t]*,[\s\t]*["\']{1}([^"\']*)["\']{1})?([\s\t]*,[\s\t]*["\']{1}([^"\']*)["\']{1})?\s*\)\}/ims' => function($m) use ($start, $end) {
				return $start. 'echo _class("form2")->tpl_row(\''.$m[1].'\',$replace,\''.$m[3].'\',\''.$m[5].'\',\''.$m[7].'\');'. $end;
			},
			'/\{(css|require_css|js|require_js)\(\s*["\']{0,1}([^"\'\)\}]*?)["\']{0,1}\s*\)\}\s*(.+?)\s*{\/(\1)\}/ims' => function($m) use ($start, $end) {
				return $start. 'echo '.$m[1].'(\''.$m[3].'\', _attrs_string2array(\''.$m[2].'\'));'. $end;
			},
			'/(\{exec_last|execute_shutdown\(\s*["\']{0,1})\s*([\w-]+)\s*[,;]\s*([\w-]+)\s*[,;]{0,1}([^"\'\)\}]*)(["\']{0,1}\s*\)\})/i' => function($m) use ($start, $end) {
// TODO: this code needed to be executed last, but if compiled - this will be hard to achieve
// TODO: convert this into events after center block was processed
				return $start.'echo main()->_execute(\''.$m[2].'\',\''.$m[3].'\',\''.$m[4].'\',\''.$name.'\',0,false);'.$end;
			},
			// DEBUG_MODE patterns
			'/(\{_debug_get_replace\(\)\})/i' => function($m) use ($start, $end) {
				return $start. 'echo (DEBUG_MODE && is_array($replace) ? "<pre>".print_r(array_keys($replace),1)."</pre>" : "");'. $end;
			},
			'/(\{_debug_get_vars\(\)\})/i' => function($m) use ($start, $end) {
				return $start. 'echo $this->_debug_get_vars($string);'. $end;
			},
		);
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

		// Simple replaces
		$_my_replace = array(
			// Special tags for foreach
			'{_key}'	=> $start. 'echo $_k;'. $end,
			'{_val}'	=> $start. 'echo (is_array($_v) ? implode(",", $_v) : $_v);'. $end,
		);
		$string = str_replace(array_keys($_my_replace), $_my_replace, $string);

		foreach ((array)$this->_patterns as $pattern => $callback) {
			$string = preg_replace_callback($pattern, $callback, $string);
		}

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
	}

	/**
	* Prepare condition for the compilation
	*/
	function _compile_prepare_condition ($part_left = '', $cond_operator = '', $part_right = '', $add_cond = '') {
		// Left part processing
		$part_left = $this->_compile_prepare_left($part_left);
		// Right part processing
		if ($part_right{0} == '#') {
			$part_right = '$replace[\''.ltrim($part_right, '#').'\']';
		} else {
			$part_right = "'".$part_right."'";
		}
		// Additional condition
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
		$_array_magick = array(
			'_num'	=> '$__f_counter',
			'_total'=> '$__f_total',
			'_first'=> '($__f_counter == 1)',
			'_last'	=> '($__f_counter == $__f_total)',
			'_even'	=> '(!($__f_counter % 2))',
			'_odd'	=> '($__f_counter % 2)',
			'_total'=> '$__f_total',
			'_key'	=> '$_k',
			'_val'	=> '$_v',
		);
		// Array item
		if (substr($part_left, 0, 2) == '#.') {
			$part_left = '$_v[\''.substr($part_left, 2).'\']';
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
		// Global array item in left part
		} elseif (false !== strpos($part_left, '.')) {
			list($k, $v) = explode('.', $part_left);
			$avail_arrays = (array)_class('tpl')->_avail_arrays;
			$part_left = '$'.str_replace(array_keys($avail_arrays), $avail_arrays, $k).'[\''.$v.'\']';
		// Simple number or string, started with '%'
		} elseif ($part_left{0} == '%' && strlen($part_left) > 1) {
			$part_left = '"'.str_replace('"', '\"', substr($part_left, 1)).'"';
		} else {
			$part_left = '$replace[\''.$part_left.'\']';
		}
		return $part_left;
	}

	/**
	* fix translation of the dynamic vars like: {t('num vars in {vertical}')}
	*/
#	function _prepare_translate2 ($string = '', $for_params = false) {
#		if ($for_params) {
#			$string = str_replace("'", '', $string);
#		}
#		return preg_replace('/\{([a-z0-9\-\_]+)\}/i', "'.\$replace['\\1'].'", $string);
#	}
}