<?php

/**
* Template driver YF built-in
*/
class yf_tpl_driver_yf {

	/** @var array @conf_skip For "_process_ifs" */
	public $_cond_operators	= array(
		'eq' => '==',
		'ne' => '!=',
		'gt' => '>',
		'lt' => '<',
		'ge' => '>=',
		'le' => '<=',
		'mod'=> '%',
	);
	/** @var array @conf_skip For '_process_ifs' */
	public $_math_operators	= array(
		'and' => '&&',
		'xor' => 'xor',
		'or'  => '||',
		'+'   => '+',
		'-'   => '-'
	);
	/** @var @conf_skip */
	public $CACHE = array();

	/**
	* YF constructor
	*/
	function _init () {
		$this->tpl = &_class('tpl');
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
					'string'		=> $_cur_text,
					'calls'			=> 0,
					'force_storage' => 'cache',
				);
			}
		}
		$this->_init_patterns();
	}

	/**
	* Place your custom code in this method or inside ocverriden class inside project
	*/
	function _init_patterns () {
		// currently empty
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
	function _compile($name, $replace = array(), $string = '', $params = array()) {
		return _class('tpl_driver_yf_compile', 'classes/tpl/')->_compile($name, $replace, $string, $params);
	}

	/**
	* Simple template parser (*.stpl)
	*/
	function parse($name, $replace = array(), $params = array()) {
		$string = $params['string'] ?: false;

		$this->_fix_replace($replace, $name);

		$php_tpl = $this->_parse_get_php_tpl($name, $replace, $params);
		if (isset($php_tpl)) {
			return $php_tpl;
		}
		if ($this->tpl->COMPILE_TEMPLATES) {
			$compiled = $this->_parse_get_compiled($name, $replace, $params);
			if (isset($compiled)) {
				return $compiled;
			}
		}
		$string = $this->_parse_get_cached($name, $replace, $params, $string);
		if ($string === false) {
			return false;
		}
		$string = $this->_process_comments($string, $name);
		$string = $this->_process_executes($string, $replace, $name);
		$string = $this->_process_catches($string, $replace, $name);
		$string = $this->_replace_std_patterns($string, $name, $replace, $params);
		$string = $this->_process_foreaches($string, $replace, $name);
		$string = $this->_process_ifs($string, $replace, $name);
		if (!$params['no_include']) {
			$string = $this->_process_includes($string, $replace, $name);
			$string = $this->_process_executes($string, $replace, $name);
		}
		$string = $this->_process_replaces($string, $replace, $name);
		$string = $this->_process_js_css($string, $replace, $name);
		$string = $this->_replace_std_patterns($string, $name, $replace, $params);
		$string = $this->_process_executes_last($string, $replace, $name);
		return $string;
	}

	/**
	* STPL internal comment pattern. Examples: {{-- some content you want to comment inside template only --}}
	*/
	function _process_comments($string, $name) {
		return preg_replace('/(\{\{--.*?--\}\})/ims', '', $string);
	}

	/**
	*/
	function _fix_replace(array &$replace, $name) {
		foreach ($replace as $item => $value) {
			if (is_object($value) && !method_exists($value, 'render')) {
				$replace[$item] = obj2arr($value);
			}
		}
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
		$string = ob_get_clean();

		$this->_set_cache_details($name, $string, $stpl_time_start);
		return $string;
	}

	/**
	*/
	function _get_compiled_string($name, $replace = array(), $params = array()) {
		$compiled_string = null;
# TODO: add ability to use memcached or other fast cache-oriented storage instead of files => lower disk IO
		$compiled_storage = 'files';
		if ($compiled_storage == 'files') {
			$compiled_cache_name = $name.'@'.$params['force_storage'].'@'.MAIN_TYPE;
			if (isset($this->_compiled_cache[$compiled_cache_name])) {
				list($compiled_string, $compiled_mtime) = $this->_compiled_cache[$compiled_cache_name];
			} else {
				$compiled_path = STORAGE_PATH. $this->tpl->COMPILED_DIR.'c_'.MAIN_TYPE.'_'.urlencode($name).'.php';
				if (file_exists($compiled_path)) {
					$compiled_mtime = filemtime($compiled_path);
					if ((time() - $compiled_mtime) < $this->tpl->COMPILE_TTL) {
						$compiled_string = file_get_contents($compiled_path);
						$this->_compiled_cache[$compiled_cache_name] = array($compiled_string, $compiled_mtime);
					}
				}
			}
		}
		if ($compiled_mtime && strlen($compiled_string) && $this->tpl->COMPILE_CHECK_STPL_CHANGED) {
			$mtime_cache_name = $name.'@'.$params['force_storage'];
			if (isset($this->_stpl_mtimes[$mtime_cache_name])) {
				$source_mtime = $this->_stpl_mtimes[$mtime_cache_name];
			} else {
				$stpl_path = $this->tpl->_get_template_file($name, $params['force_storage'], 0, 1);
				$source_mtime = $stpl_path ? filemtime($stpl_path) : 0;
				$this->_stpl_mtimes[$mtime_cache_name] = $source_mtime;
			}
			if ($source_mtime > $compiled_mtime) {
				$compiled_string = null;
			}
		}
		if (!isset($compiled_string)) {
			$string = isset($params['string']) ? $params['string'] : $this->tpl->_get_template_file($name, $params['force_storage']);
			if (strlen($string)) {
				$compiled_string = $this->_compile($name, $replace, $string, $params);
			}
		}
		return $compiled_string;
	}

	/**
	*/
	function _parse_get_compiled($name, $replace = array(), $params = array()) {
		if (!$this->tpl->COMPILE_TEMPLATES) {
			return null;
		}
		$stpl_time_start = microtime(true);
		$compiled_string = $this->_get_compiled_string($name, $replace, $params);
		if (!isset($compiled_string)) {
			return null;
		}
		ob_start();
		eval('?>'.$compiled_string);
		$string = ob_get_clean();
		$this->_set_cache_details($name, $string, $stpl_time_start, $is_compiled = true);
		return $string;
	}

	/**
	*/
	function _set_cache_details($name, $string, $stpl_time_start, $is_compiled = false) {
		$this->CACHE[$name]['driver'] = 'yf';
		$this->CACHE[$name]['is_compiled'] = (int)$is_compiled;
		$this->CACHE[$name]['calls']++;
		if (!isset($this->CACHE[$name]['string'])) {
			$this->CACHE[$name]['string'] = $string;
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
	function _parse_get_cached($name, array &$replace, $params = array(), $string = false) {
		$force_storage = $params['force_storage'];
		if (isset($this->CACHE[$name]) && !$params['no_cache'] && !$force_storage) {
			$string = $this->CACHE[$name]['string'];
			$this->CACHE[$name]['calls']++;
			if (DEBUG_MODE) {
				$this->CACHE[$name]['s_length'] = strlen($string);
			}
		} else {
			if (empty($string) && !isset($params['string'])) {
				$string = $this->tpl->_get_template_file($name, $params['force_storage']);
			}
			if ($string === false) {
				return false;
			}
			if (isset($params['no_cache']) && !$params['no_cache']) {
				$this->CACHE[$force_storage. $name]['string'] = $string;
				$this->CACHE[$force_storage. $name]['calls'] = 1;
				$this->CACHE[$force_storage. $name]['driver'] = 'yf';
				$this->CACHE[$force_storage. $name]['force_storage'] = $force_storage;
			}
		}
		return $string;
	}

	/**
	*/
	function _process_includes($string, $replace = array(), $name = '') {
		$_this = $this;
		$pattern = '/\{(include|include_if_exists)\(\s*["\']{0,1}\s*([@:\w\\/\.]+)\s*["\']{0,1}?\s*[,;]{0,1}\s*([^"\'\)\}]*)\s*["\']{0,1}\s*\)\}/ims';
		$extra = array();
		$func = function($m) use ($replace, $name, $_this, $extra) {
			$if_exists = ($m[1] == 'include_if_exists');
			$stpl_name = $m[2];
			$_replace = $m[3];
			$force_storage = '';
			// Force to include template from special storage, example: @framework:script_js
			if ($stpl_name[0] == '@') {
				list($force_storage, $stpl_name) = explode(':', substr($stpl_name, 1));
			}
			if ($if_exists && !tpl()->exists($stpl_name, $force_storage)) {
				return false;
			}
			$prevent_name = $name.'__'.$m[0];
			// Here we merge/override incoming $replace with parsed params, to be passed to included template
			foreach ((array)explode(';', str_replace(array('\'','"'), '', $_replace)) as $v) {
				list($a_name, $a_val) = explode('=', trim($v));
				$a_name	= trim($a_name);
				if (strlen($a_name)) {
					$replace[$a_name] = trim($a_val);
				}
			}
			return $_this->parse($stpl_name, $replace, array('force_storage' => $force_storage));
		};
		return preg_replace_callback($pattern, $func, $string);
	}

	/**
	* Simple key=val replace processing with sub arrays too
	*/
	function _process_replaces($string, array &$replace, $name = '') {
		if (!strlen($string) || false === strpos($string, '{')) {
			return $string;
		}
		// Need to optimize complex replace arrays and templates not containing sub replaces
		$has_sub_pairs = preg_match('~\{[a-z0-9_-]+\.[a-z0-9_-]+\}~ims', $string);
		// Prepare pairs array of simple string replaces
		$pairs = array();
		$cleanup_keys = array();
		foreach ((array)$replace as $item => $value) {
			if (is_object($value) && !method_exists($value, 'render')) {
				$replace[$item] = obj2arr($value);
				$value = $replace[$item];
			}
			// Allow to replace simple 1-dimensional array items (some speed loss, but might be useful)
			if (is_array($value)) {
				if (!$has_sub_pairs) {
					continue;
				}
				// 2+ levels deep detected, but not supported
				if (is_array(current($value))) {
					continue;
				}
				foreach ((array)$value as $_sub_key => $_sub_val) {
					$pairs['{'.$item.'.'.$_sub_key.'}'] = $_sub_val;
				}
				$cleanup_keys[$item] = '';
			// Simple key=val replace
			} else {
				$pairs['{'.$item.'}'] = $value;
			}
		}
		if ($has_sub_pairs) {
			$avail_arrays = $this->tpl->_avail_arrays; // ('get' => '_GET')
			foreach ((array)$avail_arrays as $short => $v) {
				$v = eval('return $'.$v.';'); // !! Do not blindly change to $$v, need to figure out before why it does not work
				foreach ((array)$v as $key => $val) {
					if (is_array($val)) {
						continue;
					}
					$pairs['{'.$short.'.'.$key.'}'] = $val;
				}
				$cleanup_keys[$short] = '';
			}
		}
		if ($pairs) {
			$string = str_replace(array_keys($pairs), $pairs, $string);
		}
		// Cleanup, using regex pairs
		if ($cleanup_keys) {
			$regex_pairs = array();
			foreach ($cleanup_keys as $k => $v) {
				$regex_pairs['~\{'.preg_quote($k, '~').'\.[a-z0-9_-]+\}~i'] = '';
			}
			$string = preg_replace(array_keys($regex_pairs), '', $string);
		}
		return $string;
	}

	/**
	* If content need to be cleaned from unused tags - do that
	*/
	function _process_clear_unused($string, array &$replace, $name = '') {
		return preg_replace('/\{[\w_]+\}/i', '', $string);
	}

	/**
	* Evaluate given string as php code
	*/
	function _process_eval_string($string, array &$replace, $name = '') {
		return eval('return "'.str_replace('"', '\"', $string).'";');
	}

	/**
	* Replace '{execute' patterns
	*/
	function _process_executes($string, array &$replace, $name = '', $params = array()) {
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
	function _process_executes_last($string, array &$replace, $name = '', $params = array()) {
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
	* Replace JS/CSS related patterns
	*/
	function _process_js_css($string, $replace = array(), $name = '') {
		// CSS smart inclusion. Examples: {require_css(http//path.to/file.css)}, {catch(tpl_var)}.some_css_class {} {/catch} {require_css(tpl_var)}
		// JS smart inclusion. Examples: {require_js(http//path.to/file.js)}, {catch(tpl_var)} $(function(){...}) {/catch} {require_js(tpl_var)}
		$string = preg_replace_callback('/\{(css|require_css|js|require_js)\(\s*["\']{0,1}([^"\'\)\}]*?)["\']{0,1}\s*\)\}\s*(.+?)\s*{\/(\1)\}/ims', function($m) use ($_this) {
			$func = $m[1];
			if (substr($func, 0, strlen('require_')) != 'require_') {
				$func = 'require_'.$func;
			}
			return $func($m[3], _attrs_string2array($m[2]));
		}, $string);

		return $string;
	}

	/**
	* Replace standard patterns
	*/
	function _replace_std_patterns($string, $name = '', array &$replace, $params = array()) {
		$_this = $this;
		$tpl = tpl();

		$patterns = array(
			// Insert constant here (cutoff for eval_code). Examples: {const("SITE_NAME")}
			'/\{const\(\s*["\']{0,1}([a-z_][a-z0-9_]+?)["\']{0,1}\s*\)\}/i' => function($m) {
				return defined($m[1]) ? constant($m[1]) : '';
			},
			// Configuration item. Examples: {conf("TEST_DOMAIN")}
			'/\{conf\(\s*["\']{0,1}([a-z_][a-z0-9_:]+?)["\']{0,1}\s*\)\}/i' => function($m) {
				return conf($m[1]);
			},
			// Module Config item. Examples: {module_conf(gallery,MAX_SIZE)}
			'/\{module_conf\(\s*["\']{0,1}([a-z_][a-z0-9_:]+?)["\']{0,1}\s*,\s*["\']{0,1}([a-z_][a-z0-9_:]+?)["\']{0,1}\s*\)\}/i' => function($m) {
				return module_conf($m[1], $m[2]);
			},
			// Translate some items if needed. Examples: {t("Welcome")}
			'/\{(t|translate|i18n)\(\s*["\']{0,1}(.*?)["\']{0,1}\s*\)\}/ims' => function($m) use ($replace, $name, $tpl) {
				return $tpl->_i18n_wrapper($m[2], $replace);
			},
			// Trims whitespaces, removes. Examples: {cleanup()}some content here{/cleanup}
			'/\{cleanup\(\s*\)\}(.*?)\{\/cleanup\}/ims' => function($m) {
				return trim(str_replace(array("\r","\n","\t"), '', stripslashes($m[1])));
			},
			// Display help tooltip. Examples: {tip('register.login')} or {tip('form.some_field',2)}
			'/\{tip\(\s*["\']{0,1}([\w\-\.#]+)["\']{0,1}[,]{0,1}["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims' => function($m) use ($replace, $name) {
				return _class_safe('graphics')->_show_help_tip(array('tip_id' => $m[1], 'tip_type' => $m[2], 'replace' => $replace));
			},
			// Display help tooltip inline. Examples: {itip('register.login')}
			'/\{itip\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims' => function($m) use ($replace, $name) {
				return _class_safe('graphics')->_show_inline_tip(array('text' => $m[1], 'replace' => $replace));
			},
			// Display user level single (inline) error message by its name (keyword). Examples: {e('login')} or {user_error('name_field')}
			'/\{(e|user_error)\(\s*["\']{0,1}([\w\-\.]+)["\']{0,1}\s*\)\}/ims' => function($m) {
				return common()->_show_error_inline($m[2]);
			},
			// Advertising. Examples: {ad('AD_ID')}
			'/\{ad\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims' => function($m) {
				return module_safe('advertising')->_show(array('ad' => $m[1]));
			},
			// Url generation with params. Examples: {url(object=home_page;action=test)}
			'/\{url\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/ims' => function($m) use ($tpl) {
				return $tpl->_generate_url_wrapper($m[1]);
			},
			// Form item/row. Examples: {form_row("text","password","New Password")}
			'/\{form_row\(\s*["\']{0,1}[\s\t]*([a-z0-9\-_]+)[\s\t]*["\']{0,1}([\s\t]*,[\s\t]*["\']{1}([^"\']*)["\']{1})?([\s\t]*,'
				.'[\s\t]*["\']{1}([^"\']*)["\']{1})?([\s\t]*,[\s\t]*["\']{1}([^"\']*)["\']{1})?\s*\)\}/ims' => function($m) use ($replace, $name) {
				return _class('form2')->tpl_row($m[1], $replace, $m[3], $m[5], $m[7]);
			},
			// Variable filtering like in Smarty/Twig. Examples: {var1|trim}    {var1|urlencode|trim}   {var1|_prepare_html}   {var1|my_func}
			'/\{([a-z0-9\-\_]+)\|([a-z0-9\-\_\|]+)\}/ims' => function($m) use ($replace, $name, $tpl) {
				return $tpl->_process_var_filters($replace[$m[1]], $m[2]);
			},
			// Second level variables with filters. Examples: {sub1.var1|trim}
			'/\{([a-z0-9\-\_]+)\.([a-z0-9\-\_]+)\|([a-z0-9\-\_\|]+)\}/ims' => function($m) use ($replace, $name, $tpl) {
				return $tpl->_process_var_filters($replace[$m[1]][$m[2]], $m[3]);
			},
		);
		// Evaluate custom PHP code pattern. Examples: {eval_code(print_r(_class('forum')))}
		if ($tpl->ALLOW_EVAL_PHP_CODE) {
			$patterns['/(\{eval_code\()([^\}]+?)(\)\})/i'] = function($m) {
				return eval('return '.$m[2].' ;');
			};
		}
		// Custom patterns support (intended to be used inside modules/plugins)
		foreach ((array)$tpl->_custom_patterns as $pattern => $func) {
			$patterns[$pattern] = function($m) use ($replace, $name, $_this, $func) { return $func($m, $replace, $name, $_this); };
		}
		if (DEBUG_MODE) {
			// Evaluate custom PHP code pattern special for the DEBUG_MODE. Examples: {_debug_get_replace()}
			$patterns['/(\{_debug_get_replace\(\)\})/i'] = function($m) use ($replace, $name) {
				return is_array($replace) ? '<pre>'.print_r(array_keys($replace), 1).'</pre>' : '';
			};
			// Evaluate custom PHP code pattern special for the DEBUG_MODE. Examples: {_debug_stpl_vars()}
			$patterns['/(\{_debug_get_vars\(\)\})/i'] = function($m) use ($string, $tpl) {
				return $tpl->_debug_get_vars($string);
			};
		}
		foreach ((array)$patterns as $pattern => $callback) {
			$string = preg_replace_callback($pattern, $callback, $string);
		}
		return $string;
	}

	/**
	* Process 'catch' template statements
	*/
	function _process_catches ($string = '', array &$replace, $stpl_name = '') {
		if (false === strpos($string, '{/catch}') || empty($string)) {
			return $string;
		}
		$_this = $this;
		// Catch dynamic content into variable. Examples: {catch("widget_blog_last_post")} {execute(blog,_widget_last_post)} {/catch}
		$pattern = '/\{catch\([\s\t]*["\']{0,1}([\w_-]+?)["\']{0,1}[\s\t]*\)\}(.*?)\{\/catch\}/ims';
		return preg_replace_callback($pattern, function($m) use ($_this, &$replace, $stpl_name) {
			$catched_name	= $m[1];
			$catched_string	= $m[2];
			if (!empty($catched_name)) {
				if (strlen($catched_string) && strpos($catched_string, '{') !== false) {
					$catched_string = $_this->_replace_std_patterns($catched_string, $stpl_name, $replace);
					$catched_string = $_this->_process_foreaches($catched_string, $replace, $stpl_name);
					$catched_string = $_this->_process_ifs($catched_string, $replace, $stpl_name);
					$catched_string = $_this->_process_replaces($catched_string, $replace, $stpl_name);
					$catched_string = $_this->_process_js_css($catched_string, $replace, $stpl_name);
					$catched_string = $_this->_process_includes($catched_string, $replace, $stpl_name);
					$catched_string = $_this->_process_executes($catched_string, $replace, $stpl_name);
				}
				$replace[$catched_name] = trim($catched_string);
			}
			return '';
		}, $string);
	}

	/**
	* Conditional execution
	*/
	function _process_ifs ($string = '', array &$replace, $stpl_name = '') {
		if (false === strpos($string, '{/if}') || empty($string)) {
			return $string;
		}
		// Important!
		$string = str_replace(array('<'.'?', '?'.'>'), array('&lt;?', '?&gt;'), $string);
		$_this = $this;

		// Process common ifs matches. Examples: {if("name" eq "New")}<h1 style="color: white;">NEW</h1>{/if}
		$pattern = '/\{(?P<cond>if|elseif)\(\s*["\']{0,1}(?P<left>[\w\s\.+%-]+?)["\']{0,1}[\s\t]+(?P<op>eq|ne|gt|lt|ge|le|mod)[\s\t]+["\']{0,1}(?P<right>[\w#-]*)["\']{0,1}(?P<multi_conds>[^\(\)\{\}\n]*)\s*\)\}/ims';
		$string = preg_replace_callback($pattern, function($m) use ($_this, $replace, $stpl_name) {
			$cond = trim($m['cond']); // if | elseif
			$part_left = $_this->_prepare_cond_text($m['left'], $replace, $stpl_name);
			if (empty($part_left)) {
				$part_left = '""';
			}
			$part_right = trim($m['right']);
			if (strlen($part_right) && $part_right{0} == '#') {
				$part_right = $replace[ltrim($part_right, '#')];
			}
			if (!is_numeric($part_right)) {
				$part_right = '"'.$part_right.'"';
			}
			$cur_operator = $_this->_cond_operators[strtolower($m['op'])];
			// Special case for "mod". Examples: {if("id" mod 4)} content {/if}
			if ($cur_operator == '%') {
				$part_left = '!('.$part_left;
				$part_right = $part_right.')';
			}
			$add_cond = trim($m['multi_conds']);
			if ($add_cond) {
				$pattern = '/[\s\t]*(?P<cond>and|xor|or)[\s\t]+["\']{0,1}(?P<left>[\w\s\.\-\+\%]+?)["\']{0,1}[\s\t]+(?P<op>eq|ne|gt|lt|ge|le|mod)[\s\t]+["\']{0,1}(?P<right>[\w\s\-\#]*)["\']{0,1}/ims';
				$add_cond = preg_replace_callback($pattern, function($m) use ($_this, $replace, $stpl_name) {
					$a_cond	= trim($m['cond']);
					$a_left	= $_this->_prepare_cond_text($m['left'], $replace, $stpl_name);
					$a_op	= $_this->_cond_operators[strtolower(trim($m['op']))];
					$a_right = trim($m['right']);
					if (substr($a_right, 0, 1) == '#') {
						$a_right = '$replace[\''.ltrim($a_right, '#').'\']';
					}
					if (!is_numeric($a_right)) {
						$a_right = '\''.$a_right.'\'';
					}
					if (empty($a_left)) {
						$a_left = '\'\'';
					}
					// Special case for "mod". Examples: {if("id" mod 4)} content {/if}
					if ($a_op == '%') {
						$a_left = '!('.$a_left;
						$a_right = $a_right.')';
					}
					return $a_cond.' ('.$a_left.' '.$a_op.' '.$a_right.') ';
				}, $add_cond);
			}
			return '<'.'?p'.'hp '.($cond == 'elseif' ? '} '.$cond : $cond).'('. $part_left. ' '. $cur_operator. ' '. $part_right. $add_cond. ') { ?>';
		}, $string);

		// Shortcuts for conditional patterns. Examples: {if_empty(name)}<h1 style="color: white;">NEW</h1>{/if}
		$pattern = '/\{(?P<cond>if|elseif)_(?P<func>[a-z0-9_:]+)\(\s*["\']{0,1}(?P<left>[\w\s\.+%-]+?)["\']{0,1}[\s\t]*\)\}/ims';
		$string = preg_replace_callback($pattern, function($m) use ($_this, $replace, $stpl_name) {
			$cond = trim($m['cond']); // if | elseif
			$part_left = $_this->_prepare_cond_text($m['left'], $replace, $stpl_name);
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
			return '<'.'?p'.'hp '.($cond == 'elseif' ? '} '.$cond : $cond).'('. ($negate ? '!' : ''). $func. '('. (strlen($part_left) ? $part_left : '$replace["___not_existing_key__"]'). ')) { ?>';
		}, $string);

		$string = str_replace('{else}', '<'.'?p'.'hp } else { ?'.'>', $string);
		$string = str_replace('{/if}', '<'.'?p'.'hp } ?'.'>', $string);

		ob_start();
		$result = eval('?>'.$string.'<'.'?p'.'hp return 1;');
		$new_string = ob_get_clean();

		if (!$result) {
			$error_msg = 'STPL: ERROR: wrong condition in template "'.$stpl_name.'"';
			if (DEBUG_MODE) {
				$error_msg .= PHP_EOL.'<pre>'.PHP_EOL. _prepare_html(var_export($string, 1)). PHP_EOL.'</pre>'.PHP_EOL;
			}
			trigger_error($error_msg, E_USER_WARNING);
		}
		return $new_string;
	}

	/**
	* Prepare text for '_process_ifs' method
	*/
	function _prepare_cond_text ($cond_text = '', $replace = array(), $stpl_name = '') {
		$prepared_array = array();
		foreach (explode(' ', str_replace("\t",'',$cond_text)) as $tmp_k => $tmp_v) {
			$res_v = '';
			// Value from $replace array (DO NOT replace 'array_key_exists()' with 'isset()' !!!)
			if (array_key_exists($tmp_v, $replace)) {
				if (is_object($replace[$tmp_v]) && !method_exists($replace[$tmp_v], 'render')) {
					$res_v = get_object_vars($replace[$tmp_v]);
				}
				if (is_array($replace[$tmp_v])) {
					$res_v = $replace[$tmp_v] ? '("1")' : '("")';
				} else {
					$res_v = '$replace["'.$tmp_v.'"]';
				}
			// Arithmetic operators (currently we allow only '+' and '-')
			} elseif (isset($this->_math_operators[$tmp_v])) {
				$res_v = $this->_math_operators[$tmp_v];
			// Module config item
			} elseif (strpos($tmp_v, 'module_conf.') === 0) {
				list($mod_name, $mod_conf) = explode('.', substr($tmp_v, strlen('module_conf.')));
				$res_v = 'module_conf("'.$mod_name.'","'.$mod_conf.'")';
			// Configuration item
			} elseif (strpos($tmp_v, 'conf.') === 0) {
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
	*/
	function _range_foreach ($max) {
		$max = intval($max);
		if ($max < 1) {
			return array();
		}
		$limit = 10000; // Mostly for security (prevent buffer overflows) and for avoid mistakes
		if ($max > $limit) {
			$max = $limit;
		}
		return range(1, $max);
	}

	/**
	* Foreach patterns processing
	*/
	function _process_foreaches ($string = '', $replace = array(), $stpl_name = '') {
		if (false === strpos($string, '{/foreach}') || empty($string)) {
			return $string;
		}
		$_this = $this;
		// foreach processing pattern. Examples: {foreach("var")}<li>{#.value1}</li>{/foreach}
		$pattern = '/\{foreach\(\s*["\']{0,1}([\w\s\.-]+)["\']{0,1}\s*\)\}((?![^\{]*?\{foreach\(\s*["\']{0,1}?).*?)\{\/foreach\}/is';
		return preg_replace_callback($pattern, function($m) use ($_this, $replace, $stpl_name) {
			$matched_string = $m[0];
			$key_to_cycle = trim($m[1]);
			if (empty($key_to_cycle)) {
				return '';
			}
			$sub_template = str_replace('#.', $key_to_cycle.'.', $m[2]);

			// Example of elseforeach: {foreach(items)} {_key} = {_val} {elseforeach} No records {/foreach}
			$no_rows_text = '';
			$else_tag = '{elseforeach}';
			if (false !== strpos($sub_template, $else_tag)) {
				list($else_before, $no_rows_text) = explode($else_tag, $sub_template);
				$sub_template = str_replace($else_tag. $no_rows_text, '', $sub_template);
			}
			$var_filter_pattern = '/\{('.preg_quote($key_to_cycle, '/').')\.([a-z0-9\-\_]+)\|([a-z0-9\-\_\|]+)\}/ims'; // Example: {testarray.key1|trim}
			$has_var_filters = preg_match($var_filter_pattern, $sub_template);

			$data = null;
			$sub_array = array();
			// Sub array like this: {foreach(post.somekey)} or {foreach(data.sub)}
			if (false !== strpos($key_to_cycle, '.')) {
				list($sub_key1, $sub_key2) = explode('.', $key_to_cycle);
				if (!$sub_key1 || !$sub_key2) {
					return '';
				}
				$data = $replace[$sub_key1][$sub_key2];
				if (isset($data)) {
					if (is_array($data)) {
						$sub_array = $data;
					// Iteration by numberic var value, example: {foreach(data.number)}, number == 3
					} elseif (is_numeric($data)) {
						$sub_array = $_this->_range_foreach($data);
					}
				} else {
					$avail_arrays = $_this->tpl->_avail_arrays;
					if (isset($avail_arrays[$sub_key1])) {
						$v = eval('return $'.$avail_arrays[$sub_key1].';'); // !! Do not blindly replace this with $$v, because somehow it does not work
						if (isset($v[$sub_key2])) {
							$sub_array = $v[$sub_key2];
							// Iteration by numeric var value, example: {foreach(number)}, number == 3
							if ($sub_array && is_numeric($sub_array)) {
								$sub_array = $_this->_range_foreach($sub_array);
							}
						}
					}
				}
			// Standard iteration by array, example: {foreach(myarray)}
			} elseif (isset($replace[$key_to_cycle])) {
				$data = $replace[$key_to_cycle];
				if (is_array($data)) {
					$sub_array = $data;
				// Iteration by numberic var value, example: {foreach(number)}, number == 3
				} elseif (is_numeric($data)) {
					$sub_array = $_this->_range_foreach($data);
				}
			// Simple iteration within template, example: {foreach(10)}
			} elseif (is_numeric($key_to_cycle)) {
				$sub_array = $_this->_range_foreach($key_to_cycle);
			}
			if (empty($sub_array)) {
				return $no_rows_text;
			}
			// Process sub template (only cycle within correct keys)
			$_total = (int)count($sub_array);
			$_i = 0;
			$output = array();
			$sub_replace  = array();
			foreach ((array)$sub_array as $sub_k => $sub_v) {
				$_i++;
				$cur_output = $sub_template;
				$_is_first  = (int)($_i == 1);
				$_is_last   = (int)($_i == $_total);
				$_is_odd	= (int)($_i % 2);
				$_is_even   = (int)(!$_is_odd);
				$sub_replace = array(
					'_num'   => $_i,
					'_total' => $_total,
					'_key'   => $sub_k,
					'_val'   => is_array($sub_v) ? implode(',', $sub_v) : $sub_v,
					'_first' => $_is_first,
					'_last'  => $_is_last,
					'_even'  => $_is_odd,
					'_odd'   => $_is_even,
				);
				if (is_array($sub_v)) {
					foreach ($sub_v as $k => $v) {
						$sub_replace[$key_to_cycle.'.'.$k] = $v;
					}
				}
				$sub_tpl_replace = array();
				foreach ($sub_replace as $k => $v) {
					$sub_tpl_replace['{'.$k.'}'] = $v;
				}
				$cur_output = str_replace(array_keys($sub_tpl_replace), $sub_tpl_replace, $cur_output);
				unset($sub_tpl_replace);
				// Apply var filtering pattern, in case if such constructions found on the upper level
				if ($has_var_filters) {
					$cur_output = preg_replace_callback($var_filter_pattern, function($_m) use ($sub_v) {
						return _class('tpl')->_process_var_filters($sub_v[$_m[2]], $_m[3]);
					}, $cur_output);
				}

				// Try to process conditions in every cycle
				$sub_replace += $replace;
				$output[] = $_this->_process_ifs($cur_output, $sub_replace, $stpl_name);
			}
			return implode($output);
		}, $string);
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
	function _include_stpl ($stpl_name = '', $params = '', $replace = array(), $if_exists = false) {
		if (!is_array($replace)) {
			$replace = array();
		}
		$force_storage = '';
		// Force to include template from special storage, example: @framework:script_js
		if ($stpl_name[0] == '@') {
			list($force_storage, $stpl_name) = explode(':', substr($stpl_name, 1));
		}
		if ($if_exists && !tpl()->exists($stpl_name, $force_storage)) {
			return false;
		}
		$replace = (array)_attrs_string2array($params) + (array)$replace;
		return $this->parse($stpl_name, $replace);
	}

	/**
	* For compiled templates
	*/
	function call_custom_pattern($crc32_or_name, $args = '', $body = null, $replace = array(), $stpl_name = '') {
		$tpl = tpl();
		$pattern = $tpl->_custom_patterns_index[$crc32_or_name];
		if (strlen($pattern)) {
			$func = $tpl->_custom_patterns[$pattern];
		}
		if (!$func || !is_callable($func)) {
			return $body;
		}
		return $func(array('args' => $args, 'body' => $body), $replace, $stpl_name, $this);
	}
}
