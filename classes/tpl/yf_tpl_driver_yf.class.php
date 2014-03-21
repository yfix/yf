<?php

/**
* Template driver YF built-in
*/
class yf_tpl_driver_yf {

	/** @var array @conf_skip Catch dynamic content into variable */
	// Examples: {catch("widget_blog_last_post")} {execute(blog,_widget_last_post)} {/catch}
	public $_PATTERN_CATCH = '/\{catch\(\s*["\']{0,1}([a-z0-9_\-]+?)["\']{0,1}\s*\)\}(.*?)\{\/catch\}/ims';
	/** @var array @conf_skip STPL internal comment pattern */
	// Examples: {{-- some content you want to comment inside template only --}}
	public $_PATTERN_COMMENT = '/(\{\{--.*?--\}\})/ims';
	/** @var string @conf_skip Conditional pattern */
	// Examples: {if("name" eq "New")}<h1 style="color: white;">NEW</h1>{/if}
	public $_PATTERN_IF	= '/\{if\(\s*["\']{0,1}([\w\s\.\-\+\%]+?)["\']{0,1}[\s\t]+(eq|ne|gt|lt|ge|le|mod)[\s\t]+["\']{0,1}([\w\s\-\#]*)["\']{0,1}([^\(\)\{\}\n]*)\s*\)\}/ims';
	/** @var string @conf_skip pattern for multi-conditions */
	public $_PATTERN_MULTI_COND = '/["\']{0,1}([\w\s\.\-\+\%]+?)["\']{0,1}[\s\t]+(eq|ne|gt|lt|ge|le|mod)[\s\t]+["\']{0,1}([\w\s\-\#]*)["\']{0,1}/ims';
	/** @var string @conf_skip Cycle pattern */
	// Examples: {foreach ("var")}<li>{var.value1}</li>{/foreach}
	public $_PATTERN_FOREACH = '/\{foreach\(\s*["\']{0,1}([\w\s\.\-]+)["\']{0,1}\s*\)\}((?![^\{]*?\{foreach\(\s*["\']{0,1}?).*?)\{\/foreach\}/is';
	/** @var array @conf_skip For "_process_conditions" */
	public $_cond_operators	= array('eq'=>'==','ne'=>'!=','gt'=>'>','lt'=>'<','ge'=>'>=','le'=>'<=','mod'=>'%');
	/** @var array @conf_skip For '_process_conditions' */
	public $_math_operators	= array('and'=>'&&','xor'=>'xor','or'=>'||','+'=>'+','-'=>'-');
	/** @var int Safe limit number of replacements (to avoid dead cycles) (type "-1" for unlimited number) */
	public $STPL_REPLACE_LIMIT	 = -1;
	/** @var int Cycles and conditions max recurse level (how deeply could be nested template constructs like "if") */
	public $_MAX_RECURSE_LEVEL = 4;
	/** @var @conf_skip */
	public $CACHE = array();

	/**
	* YF constructor
	*/
	function _init () {
		$this->tpl = _class('tpl');
		if (!function_exists('preg_match_all')) {
			trigger_error('STPL: PCRE Extension is REQUIRED for the template engine', E_USER_ERROR);
		}
		$this->CACHE = array(
			'stpl' => array()
		);
		if (defined('FRAMEWORK_IS_COMPILED')) {
			conf('FRAMEWORK_IS_COMPILED', (bool)FRAMEWORK_IS_COMPILED);
		}
		if (conf('FRAMEWORK_IS_COMPILED') && $this->AUTO_LOAD_PACKED_STPLS) {
			foreach ((array)conf('_compiled_stpls') as $_cur_name => $_cur_text) {
				$this->CACHE[$_cur_name] = array(
					'string'	=> $_cur_text,
					'calls'		=> 0,
					'storage'   => 'cache',
				);
			}
		}
		$this->_init_patterns();
	}

	/**
	*/
	function _init_patterns () {
	}

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Compile given template into pure PHP code
	*/
	function _compile($name, $replace = array(), $string = '') {
		return _class('tpl_driver_yf_compile', 'classes/tpl/')->_compile($name, $replace, $string);
	}

	/**
	* Simple template parser (*.stpl)
	*/
	function parse($name, $replace = array(), $params = array()) {
		$string = $params['string'] ?: false;

		$php_tpl = $this->_parse_get_php_tpl($name, $replace, $params);
		if (isset($php_tpl)) {
			return $php_tpl;
		}
		$compiled = $this->_parse_get_compiled($name, $replace, $params);
		if (isset($compiled)) {
			return $compiled;
		}
		$string = $this->_parse_get_cached($name, $replace, $params, $string);
		if ($string === false) {
			return false;
		}
		$string = $this->_process_executes($string, $replace, $name);
		$string = $this->_process_catches($string, $replace, $name);
		$string = $this->_replace_std_patterns($string, $name, $replace, $params);
		$string = $this->_process_cycles($string, $replace, $name);
		$string = $this->_process_conditions($string, $replace, $name);
		if (!$params['no_include']) {
			$string = $this->_process_includes($string, $replace, $name);
			$string = $this->_process_executes($string, $replace, $name);
		}
		$string = $this->_process_replaces($string, $replace, $name);
		$string = $this->_replace_std_patterns($string, $name, $replace, $params);
		
		$string = $this->_process_executes_last($string, $replace, $name);
		
		return $string;
	}

	/**
	*/
	function _parse_get_php_tpl($name, $replace = array(), $params = array()) {
		if (!$this->tpl->ALLOW_PHP_TEMPLATES) {
			return null;
		}
		$path = PROJECT_PATH. $this->tpl->TPL_PATH. $name.'.tpl.php';
		if (!file_exists($path)) {
			return null;
		}
		$stpl_time_start = microtime(true);

		ob_start();
		include ($path);
		$string = ob_get_contents();
		ob_end_clean();

		$this->_set_cache_details($name, $string, $stpl_time_start);
		return $string;
	}

	/**
	*/
	function _parse_get_compiled($name, $replace = array(), $params = array()) {
		if (!$this->tpl->COMPILE_TEMPLATES) {
			return null;
		}
		$stpl_time_start = microtime(true);

# TODO: add ability to use memcached or other fast cache-oriented storage instead of files => lower disk IO
		$compiled_path = PROJECT_PATH. $this->tpl->COMPILED_DIR.'c_'.MAIN_TYPE.'_'.urlencode($name).'.php';
		if (!file_exists($compiled_path)) {
			return null;
		}
		$compiled_ok = false;
		$compiled_mtime = filemtime($compiled_path);
		if ((time() - $compiled_mtime) < $this->tpl->COMPILE_TTL) {
			$compiled_ok = true;
		}
		if (!$compiled_ok) {
			return null;
		}
		if ($compiled_ok) {

			ob_start();
			include ($compiled_path);
			$string = ob_get_contents();
			ob_end_clean();

			if ($this->tpl->COMPILE_CHECK_STPL_CHANGED) {
				$stpl_path = $this->tpl->_get_template_file($name, $params['get_from_db'], 0, 1);
				if ($stpl_path) {
					$source_mtime = filemtime($stpl_path);
				}
				if (!$stpl_path || $source_mtime > $compiled_mtime) {
					$compiled_ok = false;
					$string = false;
				}
			}
			if ($compiled_ok) {
				$this->_set_cache_details($name, $string, $stpl_time_start);
				return $string;
			}
		}
		return null;
	}

	/**
	*/
	function _set_cache_details($name, $string, $stpl_time_start) {
		$this->CACHE[$name]['calls']++;
		if (!isset($this->CACHE[$name]['string'])) {
			$this->CACHE[$name]['string']   = $string;
		}
		if (!isset($this->CACHE[$name]['s_length'])) {
			$this->CACHE[$name]['s_length'] = strlen($string);
		}
		if (DEBUG_MODE && MAIN_TYPE_USER) {
			$this->CACHE[$name]['exec_time'] += (microtime(true) - $stpl_time_start);
		}
	}

	/**
	*/
	function _parse_get_cached($name, $replace = array(), $params = array(), $string = false) {
		if (isset($this->CACHE[$name]) && !$params['no_cache']) {
			$string = $this->CACHE[$name]['string'];
			$this->CACHE[$name]['calls']++;
			if (DEBUG_MODE) {
				$this->CACHE[$name]['s_length'] = strlen($string);
			}
		} else {
			if (empty($string) && !isset($params['string'])) {
				$string = $this->tpl->_get_template_file($name, $params['get_from_db']);
			}
			if ($string === false) {
				return false;
			}
			$string = preg_replace($this->_PATTERN_COMMENT, '', $string);
			if ($this->tpl->COMPILE_TEMPLATES) {
				$this->_compile($name, $replace, $string);
			}
			if (isset($params['no_cache']) && !$params['no_cache']) {
				$this->CACHE[$name]['string']   = $string;
				$this->CACHE[$name]['calls']	= 1;
			}
		}
		return $string;
	}

	/**
	*/
	function _process_includes($string, $replace = array(), $name = '') {
		$_this = $this;
		$pattern = '/(\{include\(\s*["\']{0,1})\s*([\w\\/\.]+)\s*["\']{0,1}?\s*[,;]{0,1}\s*([^"\'\)\}]*)\s*(["\']{0,1}\s*\)\})/i';
		$func = function($m) use ($replace, $name, $_this) {
			$stpl_name = $m[2];
			$_replace = $m[3];
			// Here we merge/override incoming $replace with parsed params, to be passed to included template
			foreach ((array)explode(';', str_replace(array('\'','"'), '', $_replace)) as $v) {
				list($a_name, $a_val) = explode('=', trim($v));
				$a_name	= trim($a_name);
				if (strlen($a_name)) {
					$replace[$a_name] = trim($a_val);
				}
			}
			return $_this->parse($stpl_name, $replace);
		};
		return preg_replace_callback($pattern, $func, $string);
	}

	/**
	*/
	function _process_replaces($string, $replace = array(), $name = '') {
		// Replace given items (if exists ones)
		foreach ((array)$replace as $item => $value) {
			if (!is_array($value)) {
				$string = str_replace('{'.$item.'}', $value, $string);
			}
			// Allow to replace simple 1-dimensional array items (some speed loss, but might be useful)
			if (is_array($value) && !is_array(current($value))) {
				foreach ((array)$value as $_sub_key => $_sub_val) {
					$string = str_replace('{'.$item.'.'.$_sub_key.'}', $_sub_val, $string);
				}
			}
		}
		return $string;
	}

	/**
	*/
	function _process_clear_unused($string, $replace = array(), $name = '') {
		// If content need to be cleaned from unused tags - do that
		return preg_replace('/\{[\w_]+\}/i', '', $string);
	}

	/**
	*/
	function _process_eval_string($string, $replace = array(), $name = '') {
		eval('$string = "'.str_replace('"', '\"', $string).'";');
		return $string;
	}

	/**
	* Replace '{execute' patterns
	*/
	function _process_executes($string, $replace = array(), $name = '', $params = array()) {
		if (empty($string)) {
			return $string;
		}
		$_this = $this;
		// Examples: {execute(graphics, translate, value = blabla; extra = strtoupper)
		if (strpos($string, '{exec') !== false) {
			$string = preg_replace_callback(
				'/\{(execute|exec_cached)\(\s*["\']{0,1}\s*([\w\-]+)\s*[,;]\s*([\w\-]+)\s*[,;]{0,1}\s*([^"\'\)\}]*)["\']{0,1}\s*\)\}/i', 
				function($m) use ($replace, $name, $_this) {
					$use_cache = false;
					if ($m[1] == 'exec_cached') {
						$use_cache = true;
					}
					return main()->_execute($m[2], $m[3], $m[4], $name. $_this->_STPL_EXT, 0, $use_cache);
				}
			, $string);
		}
		// Examples: {block(center_area))   {block(center_area;param1=val1;param2=val2))
		if (strpos($string, '{block(') !== false) {
			$string = preg_replace_callback(
				'/\{block\(\s*([\w\-]+)\s*[,;]{0,1}\s*([^"\'\)\}]*)["\']{0,1}\s*\)\}/i',
				function($m) use ($replace, $name, $_this) {
					return main()->_execute('graphics', '_show_block', 'name='.$m[1].';'.$m[2], $name. $_this->_STPL_EXT, 0, $use_cache = false);
				}
			, $string);
		}
		return $string;
	}

	/**
	* Replace '{exec_last' patterns
	* This code block needed to be executed inside template after all other patterns
	*/
	function _process_executes_last($string, $replace = array(), $name = '', $params = array()) {
		if (empty($string)) {
			return $string;
		}
		$_this = $this;
		// Examples: {exec_last(graphics, translate, value = blabla; extra = strtoupper)
		if (strpos($string, '{exec_last') !== false || strpos($string, '{execute_shutdown') !== false) {
			$string = preg_replace_callback(
				'/\{(exec_last|execute_shutdown)\(\s*["\']{0,1}\s*([\w\-]+)\s*[,;]\s*([\w\-]+)\s*[,;]{0,1}\s*([^"\'\)\}]*)["\']{0,1}\s*\)\}/i', 
				function($m) use ($replace, $name, $_this) {
					return main()->_execute($m[2], $m[3], $m[4], $name. $_this->_STPL_EXT, 0, $use_cache = false);
				}
			, $string);
		}
		return $string;
	}

	/**
	* Replace standard patterns
	*/
	function _replace_std_patterns($string, $name = '', $replace = array(), $params = array()) {
		$_this = $this;

		// Insert constant here (cutoff for eval_code). Examples: {const("SITE_NAME")}
		$string = preg_replace_callback('/\{const\(\s*["\']{0,1}([a-z_][a-z0-9_]+?)["\']{0,1}\s*\)\}/i', function($m) {
			return defined($m[1]) ? constant($m[1]) : '';
		}, $string);

		// Configuration item. Examples: {conf("TEST_DOMAIN")}
		$string = preg_replace_callback('/\{conf\(\s*["\']{0,1}([a-z_][a-z0-9_:]+?)["\']{0,1}\s*\)\}/i', function($m) {
			return conf($m[1]);
		}, $string);

		// Translate some items if needed. Examples: {t("Welcome")}
		$string = preg_replace_callback('/\{(t|translate|i18n)\(\s*["\']{0,1}(.*?)["\']{0,1}\s*\)\}/ims', function($m) use ($replace, $name, $_this) {
			return $_this->tpl->_i18n_wrapper($m[2], $replace);
		}, $string);

		// Trims whitespaces, removes. Examples: {cleanup()}some content here{/cleanup}
		$string = preg_replace_callback('/\{cleanup\(\s*\)\}(.*?)\{\/cleanup\}/ims', function($m) {
			return trim(str_replace(array("\r","\n","\t"), '', stripslashes($m[1])));
		}, $string);

		// Display help tooltip. Examples: {tip('register.login')} or {tip('form.some_field',2)}
		$string = preg_replace_callback('/\{tip\(\s*["\']{0,1}([\w\-\.#]+)["\']{0,1}[,]{0,1}["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims', function($m) use ($replace, $name) {
			return _class_safe('graphics')->_show_help_tip(array('tip_id' => $m[1], 'tip_type' => $m[2], 'replace' => $replace));
		}, $string);

		// Display help tooltip inline. Examples: {itip('register.login')}
		$string = preg_replace_callback('/\{itip\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims', function($m) use ($replace, $name) {
			return _class_safe('graphics')->_show_inline_tip(array('text' => $m[1], 'replace' => $replace));
		}, $string);

		// Display user level single (inline) error message by its name (keyword). Examples: {e('login')} or {user_error('name_field')}
		$string = preg_replace_callback('/\{(e|user_error)\(\s*["\']{0,1}([\w\-\.]+)["\']{0,1}\s*\)\}/ims', function($m) {
			return common()->_show_error_inline($m[2]);
		}, $string);

		// Advertising. Examples: {ad('AD_ID')}
		$string = preg_replace_callback('/\{ad\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims', function($m) {
			return module_safe('advertising')->_show(array('ad' => $m[1]));
		}, $string);

		// Url generation with params. Examples: {url(object=home_page;action=test)}
		$string = preg_replace_callback('/\{url\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims', function($m) use ($_this) {
			return $_this->tpl->_generate_url_wrapper($m[1]);
		}, $string);

// TODO: unit tests, try in the wild, add compile rules, enable
#		// CSS smart inclusion. Examples: {require_css(http//path.to/file.css)}
#		$string = preg_replace_callback('/\{require_css\(\s*["\']{0,1}([^"\'\)\}]+)["\']{0,1}\s*\)\}/ims', function($m) use ($_this) {
#			return require_css($m[1]);
#		}, $string);

// TODO: unit tests, try in the wild, add compile rules, enable
#		// JS smart inclusion. Examples: {require_js(http//path.to/file.js)}
#		$string = preg_replace_callback('/\{require_js\(\s*["\']{0,1}([^"\'\)\}]+)["\']{0,1}\s*\)\}/ims', function($m) use ($_this) {
#			return require_js($m[1]);
#		}, $string);

		// Form item/row. Examples: {form_row("text","password","New Password")}
		$string = preg_replace_callback('/\{form_row\(\s*["\']{0,1}[\s\t]*([a-z0-9\-_]+)[\s\t]*["\']{0,1}([\s\t]*,[\s\t]*["\']{1}([^"\']*)["\']{1})?([\s\t]*,'
			.'[\s\t]*["\']{1}([^"\']*)["\']{1})?([\s\t]*,[\s\t]*["\']{1}([^"\']*)["\']{1})?\s*\)\}/ims', function($m) use ($replace, $name) {
			return _class('form2')->tpl_row($m[1], $replace, $m[3], $m[5], $m[7]);
		}
		, $string);

		// Variable filtering like in Smarty/Twig. Examples: {var1|trim}    {var1|urlencode|trim}   {var1|_prepare_html}   {var1|my_func}
		$string = preg_replace_callback('/\{([a-z0-9\-\_]+)\|([a-z0-9\-\_\|]+)\}/ims', function($m) use ($replace, $name, $_this) {
			return $_this->tpl->_process_var_filters($replace[$m[1]], $m[2]);
		}, $string);

		// Second level variables with filters. Examples: {sub1.var1|trim}
		$string = preg_replace_callback('/\{([a-z0-9\-\_]+)\.([a-z0-9\-\_]+)\|([a-z0-9\-\_\|]+)\}/ims', function($m) use ($replace, $name, $_this) {
			return $_this->tpl->_process_var_filters($replace[$m[1]][$m[2]], $m[3]);
		}, $string);

		// Evaluate custom PHP code pattern. Examples: {eval_code(print_r(_class('forum')))}
		if ($this->tpl->ALLOW_EVAL_PHP_CODE) {
			$string = preg_replace_callback('/(\{eval_code\()([^\}]+?)(\)\})/i', function($m) {
				return eval('return '.$m[2].' ;');
			}, $string);
		}
		if (DEBUG_MODE) {
			// Evaluate custom PHP code pattern special for the DEBUG_MODE. Examples: {_debug_get_replace()}
			$string = preg_replace_callback('/(\{_debug_get_replace\(\)\})/i', function($m) use ($replace, $name) {
				return is_array($replace) ? '<pre>'.print_r(array_keys($replace), 1).'</pre>' : '';
			}, $string);

			// Evaluate custom PHP code pattern special for the DEBUG_MODE. Examples: {_debug_stpl_vars()}
			$string = preg_replace_callback('/(\{_debug_get_vars\(\)\})/i', function($m) use ($string, $_this) {
				return $_this->tpl->_debug_get_vars($string);
			}, $string);
		}
		return $string;
	}

	/**
	* Process 'catch' template statements
	*/
	function _process_catches ($string = '', &$replace, $stpl_name = '') {
		if (false === strpos($string, '{/catch}') || empty($string)) {
			return $string;
		}
		if (!preg_match_all($this->_PATTERN_CATCH, $string, $m)) {
			return $string;
		}
		foreach ((array)$m[0] as $k => $v) {
			$string = str_replace($v, '', $string);
			// Add replace var
			$_new_var_name  = $m[1][$k];
			$_new_var_value = $m[2][$k];
			if (!empty($_new_var_name)) {
				$replace[$_new_var_name] = trim($_new_var_value);
			}
		}
		return $string;
	}

	/**
	* Conditional execution
	*/
	function _process_conditions ($string = '', $replace = array(), $stpl_name = '') {
		if (false === strpos($string, '{/if}') || empty($string)) {
			return $string;
		}
		if (!preg_match_all($this->_PATTERN_IF, $string, $m)) {
			return $string;
		}
		// Important!
		$string = str_replace(array('<'.'?', '?'.'>'), array('&lt;?', '?&gt;'), $string);
		// Process matches
		foreach ((array)$m[0] as $k => $v) {
			$part_left	  = $this->_prepare_cond_text($m[1][$k], $replace);
			$cur_operator = $this->_cond_operators[strtolower($m[2][$k])];
			$part_right	 = trim($m[3][$k]);
			if (strlen($part_right) && $part_right{0} == '#') {
				$part_right = $replace[ltrim($part_right, '#')];
			}
			if (!is_numeric($part_right)) {
				$part_right = '"'.$part_right.'"';
			}
			if (empty($part_left)) {
				$part_left = '""';
			}
			$part_other	 = '';
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
					$part_other = ' '. implode(' ', (array)$_tmp_parts);
				}
			}
			// Special case for "mod". 
			// Examples: {if("id" mod 4)} content {/if}
			if ($cur_operator == '%') {
				$part_left = '!('.$part_left;
				$part_right = $part_right.')';
			}
			$new_code	= '<'.'?p'.'hp if('.$part_left.' '.$cur_operator.' '.$part_right.$part_other.') { ?>';
			$string		= str_replace($v, $new_code, $string);
		}
		$string = str_replace('{else}', '<'.'?p'.'hp } else { ?'.'>', $string);
		$string = str_replace('{/if}', '<'.'?p'.'hp } ?'.'>', $string);

		ob_start();
		$result = eval('?>'.$string.'<'.'?p'.'hp return 1;');
		$string = ob_get_contents();
		ob_clean();

		if (!$result) {
			trigger_error('STPL: ERROR: wrong condition in template "'.$stpl_name.'"', E_USER_WARNING);
		}
		return $string;
	}

	/**
	* Multi-condition special parser
	*/
	function _process_multi_conds ($cond_text = '', $replace = array()) {
		if (!preg_match($this->_PATTERN_MULTI_COND, $cond_text, $m)) {
			return '';
		}
		$part_left		= $this->_prepare_cond_text($m[1], $replace);
		$cur_operator	= $this->_cond_operators[strtolower($m[2])];
		$part_right		= strval($m[3]);
		if (strlen($part_right) && $part_right{0} == '#') {
			$part_right = $replace[ltrim($part_right, '#')];
		}
		if (!is_numeric($part_right)) {
			$part_right = '"'.$part_right.'"';
		}
		if (empty($part_left)) {
			$part_left = '""';
		}
		return $part_left.' '.$cur_operator.' '.$part_right;
	}

	/**
	* Prepare text for '_process_conditions' method
	*/
	function _prepare_cond_text ($cond_text = '', $replace = array()) {
		$prepared_array = array();
		foreach (explode(' ', str_replace("\t",'',$cond_text)) as $tmp_k => $tmp_v) {
			$res_v = '';
			// Value from $replace array (DO NOT replace 'array_key_exists()' with 'isset()' !!!)
			if (array_key_exists($tmp_v, $replace)) {
				if (is_array($replace[$tmp_v])) {
					$res_v = $replace[$tmp_v] ? '("1")' : '("")';
				} else {
					$res_v = '$replace["'.$tmp_v.'"]';
				}
			// Arithmetic operators (currently we allow only '+' and '-')
			} elseif (isset($this->_math_operators[$tmp_v])) {
				$res_v = $this->_math_operators[$tmp_v];
			// Configuration item
			} elseif (false !== strpos($tmp_v, 'conf.')) {
				$res_v = 'conf("'.substr($tmp_v, strlen('conf.')).'")';
			// Constant
			} elseif (false !== strpos($tmp_v, 'const.')) {
				$res_v = substr($tmp_v, strlen('const.'));
				if (!defined($res_v)) {
					$res_v = '';
				}
			// Global array element or sub array
			} elseif (false !== strpos($tmp_v, '.')) {
				$try_elm = substr($tmp_v, 0, strpos($tmp_v, '.'));
				$try_elm2 = "['".str_replace('.',"']['",substr($tmp_v, strpos($tmp_v, '.') + 1))."']";
				// Global array
				$avail_arrays = (array)$this->tpl->_avail_arrays;
				if (isset($avail_arrays[$try_elm])) {
					$res_v = '$'.$avail_arrays[$try_elm].$try_elm2;
				// Sub array
				} elseif (isset($replace[$try_elm]) && is_array($replace[$try_elm])) {
					$res_v = '$replace["'.$try_elm.'"]'.$try_elm2;
				}
			// Simple number or string, started with '%'
			} elseif ($tmp_v{0} == '%' && strlen($tmp_v) > 1) {
				$res_v = '"'.str_replace('"', "\\\"", substr($tmp_v, 1)).'"';
			} else {
				// Do not touch!
				// Variable or condition not found
			}
			// Add prepared element
			if ($res_v != '') {
				$prepared_array[$tmp_k] = $res_v;
			}
		}
		return implode(' ', $prepared_array);
	}

	/**
	* Cycled execution
	*/
	function _process_cycles ($string = '', $replace = array(), $stpl_name = '') {
		if (false === strpos($string, '{/foreach}') || empty($string)) {
			return $string;
		}
		if (!preg_match_all($this->_PATTERN_FOREACH, $string, $m)) {
			return $string;
		}
		$a_for = array();
		// Prepare non-array replace values
		foreach ((array)$replace as $k5 => $v5) {
			if (is_array($v5)) {
				continue;
			}
			$non_array_replace[$k5] = $v5;
		}
		foreach ((array)$m[0] as $match_id => $matched_string) {
			$output       = '';
			$sub_array    = array();
			$sub_replace  = array();
			$key_to_cycle = trim($m[1][$match_id]);
			$sub_template = $m[2][$match_id];
			$sub_template = str_replace('#.', $key_to_cycle.'.', $sub_template);
			$var_filter_pattern = '/\{('.preg_quote($key_to_cycle, '/').')\.([a-z0-9\-\_]+)\|([a-z0-9\-\_\|]+)\}/ims'; // Example: {testarray.key1|trim}
			$has_var_filters = preg_match($var_filter_pattern, $sub_template);
			// Needed here for graceful quick exit from cycle
			$a_for[$matched_string] = '';
			if (empty($key_to_cycle)) {
				continue;
			}
			// Standard iteration by array
			if (isset($replace[$key_to_cycle])) {
				if (is_array($replace[$key_to_cycle])) {
					$sub_array  = $replace[$key_to_cycle];
				} elseif (is_numeric($replace[$key_to_cycle])) {
					$sub_array = range(1, $replace[$key_to_cycle]);
				}
			// Simple iteration within template
			} else {
				if (is_numeric($key_to_cycle)) {
					$sub_array = range(1, $key_to_cycle);
				}
			}
			if (empty($sub_array)) {
				continue;
			}
			// Process sub template (only cycle within correct keys)
			$_total = (int)count($sub_array);
			$_i = 0;
			foreach ((array)$sub_array as $sub_k => $sub_v) {
				$_is_first  = (int)(++$_i == 1);
				$_is_last   = (int)($_i == $_total);
				$_is_odd	= (int)($_i % 2);
				$_is_even   = (int)(!$_is_odd);
				// Try to get sub keys to replace (exec only one time per one 'foreach')
				if (empty($sub_replace)) {
					if (is_array($sub_v)) {
						foreach ((array)$sub_v as $k3 => $v3) {
							$sub_replace[] = '{'.$key_to_cycle.'.'.$k3.'}';
						}
					} else {
						$sub_replace = '{'.$key_to_cycle.'.'.$key_to_cycle.'}';
					}
				}
				// Add output and replace template keys with array values
				if (!empty($sub_replace)) {
					// Process output for this iteration
					$cur_output = $sub_template;
					$cur_output = str_replace($sub_replace, is_array($sub_v) ? array_values($sub_v) : $sub_v, $cur_output);
					$cur_output = str_replace(array('{_num}','{_total}'), array($_i, $_total), $cur_output);
					// For 2-dimensional arrays
					if (is_array($sub_v)) {
						$cur_output = str_replace('{_key}', $sub_k, $cur_output);
					// For 1-dimensional arrays
					} else {
						$cur_output = str_replace(array('{_key}', '{_val}') , array($sub_k, $sub_v), $cur_output);
					}
					// Apply var filtering pattern, in case if such constructions found on the upper level
					if ($has_var_filters) {
						$cur_output = preg_replace_callback($var_filter_pattern, function($m) use ($replace, $sub_k) {
							return _class('tpl')->_process_var_filters($replace[$m[1]][$sub_k][$m[2]], $m[3]);
						}, $cur_output);
					}
					// Prepare items for condition
					$tmp_array = $non_array_replace;
					foreach ((array)$sub_v as $k6 => $v6) {
						$tmp_array[$key_to_cycle.'.'.$k6] = $v6;
					}
					$tmp_array['_num']   = $_i;
					$tmp_array['_total'] = $_total;
					$tmp_array['_first'] = $_is_first;
					$tmp_array['_last']  = $_is_last;
					$tmp_array['_even']  = $_is_odd;
					$tmp_array['_odd']   = $_is_even;
					$tmp_array['_key']   = $sub_k;
					$tmp_array['_val']   = is_array($sub_v) ? strval($sub_v) : $sub_v;
					// Try to process conditions in every cycle
					$output .= $this->_process_conditions($cur_output, $tmp_array, $stpl_name);
				}
			}
			// Create array element to replace whole cycle
			$a_for[$matched_string] = $output;
		}
		// Replace all found template cycles with values
		if (count($a_for)) {
			$string = str_replace(array_keys($a_for), array_values($a_for), $string);
		}
		return $string;
	}

	/**
	* Collect all template vars and display in pretty way
	*/
	function _debug_get_vars ($string = '') {
		$not_replaced = array();
		$patterns = array(
			'/\{([a-z0-9\_]{1,64})\}/ims',
			'/\{if\([\'"]*([a-z0-9\_]{1,64})[\'"]*[^\}\)]+?\)\}/ims',
			'/\{foreach\([\'"]*([a-z0-9\_]{1,64})[\'"]*\)\}/ims',
		);
		// Parse simple vars
		foreach ((array)$patterns as $pattern) {
			if (!preg_match_all($pattern, $string, $m)) {
				continue;
			}
			$cur_matches = $m[1];
			foreach ((array)$cur_matches as $v) {
				$v = str_replace(array('{','}'), '', $v);
				// Skip internal vars
				if ($v{0} == '_' || $v == 'else') {
					continue;
				}
				$not_replaced[$v] = $v;
			}
		}
		ksort($not_replaced);
		if (!empty($not_replaced)) {
			$body .= '<pre>array('.PHP_EOL;
			foreach ((array)$not_replaced as $v) {
				$body .= "\t".'"'._prepare_html($v, 0).'" => "",'.PHP_EOL;
			}
			$body .= ');</pre>'.PHP_EOL;
		}
		return $body;
	}

	/**
	* Wrapper for '_PATTERN_INCLUDE', allows you to include stpl, optionally pass $replace params to it
	*/
	function _include_stpl ($stpl_name = '', $params = '', $replace = array()) {
		if (!is_array($replace)) {
			$replace = array();
		}
		// Try to process method params (string like attrib1=value1;attrib2=value2)
		foreach ((array)explode(';', str_replace(array("'",''), '', $params)) as $v) {
			$attrib_name	= '';
			$attrib_value   = '';
			if (false !== strpos($v, '=')) {
				list($attrib_name, $attrib_value) = explode('=', trim($v));
			}
			$replace[trim($attrib_name)] = trim($attrib_value);
		}
		return $this->parse($stpl_name, $replace);
	}
}
