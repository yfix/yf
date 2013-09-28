<?php

/**
*/
class yf_tpl_driver_yf {

	public $STPL_REPLACE_LIMIT	 = -1;
	/** @var int Cycles and conditions max recurse level
	*   (how deeply could be nested template constructs like "if")
	*/
	public $_MAX_RECURSE_LEVEL = 4;
	/** @var array @conf_skip Patterns array for the STPL engine
	*   (you can add additional patterns if you need)
	*/
	public $_STPL_PATTERNS	 = array(
		// Insert constant here (cutoff for eval_code)
		// EXAMPLE:	 {const("SITE_NAME")}
		'/(\{const\(\s*["\']{0,1})([a-z_][a-z0-9_]+?)(["\']{0,1}\s*\)\})/ie'
			=> 'defined(\'$2\') ? main()->_eval_code(\'$2\', 0) : ""',
		// Configuration item
		// EXAMPLE:	 {conf("TEST_DOMAIN")}
		'/(\{conf\(\s*["\']{0,1})([a-z_][a-z0-9_:]+?)(["\']{0,1}\s*\)\})/ie'
			=> 'conf(\'$2\')',
		// Translate some items if needed
		// EXAMPLE:	 {t("Welcome")}
		'/\{(t|translate|i18n)\(\s*["\']{0,1}(.*?)["\']{0,1}\s*\)\}/imse'
			=> 'tpl()->_i18n_wrapper(\'$2\', $replace)',
		// Trims whitespaces, removes
		// EXAMPLE:	 {cleanup()}some content here{/cleanup}
		'/\{cleanup\(\s*\)\}(.*?)\{\/cleanup\}/imse'
			=> 'trim(str_replace(array("\r","\n","\t"),"",stripslashes(\'$1\')))',
		// Display help tooltip
		// EXAMPLE:	 {tip('register.login')} or {tip('form.some_field',2)}
		'/\{tip\(\s*["\']{0,1}([\w\-\.#]+)["\']{0,1}[,]{0,1}["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/imse'
			=> 'main()->_execute("graphics", "_show_help_tip", array("tip_id"=>"$1","tip_type"=>"$2","replace"=>$replace))',
		// Display help tooltip inline
		// EXAMPLE:	 {itip('register.login')}
		'/\{itip\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/imse'
			=> 'main()->_execute("graphics", "_show_inline_tip", array("text"=>"$1","replace"=>$replace))',
		// Display user level single (inline) error message by its name (keyword)
		// EXAMPLE:	 {e('login')} or {user_error('name_field')}
		'/\{(e|user_error)\(\s*["\']{0,1}([\w\-\.]+)["\']{0,1}\s*\)\}/imse'
			=> 'common()->_show_error_inline(\'$2\')',
		// Advertising
		// EXAMPLE:	 {ad('AD_ID')}
		'/\{ad\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/imse'
			=> 'main()->_execute("advertising", "_show", array("ad"=>\'$1\'))',
		// Url generation with params
		// EXAMPLE:	 {url(object=home_page;action=test)}
		'/\{url\(\s*["\']{0,1}([^"\'\)\}]*)["\']{0,1}\s*\)\}/imse'
			=> 'tpl()->_generate_url_wrapper(\'$1\')',
		// EXAMPLE:	 {form_row("text","password","New Password")}
		'/\{form_row\(\s*["\']{1}([\s\w\-]+)["\']{1}([\s\t]*,[\s\t]*["\']{1}([\s\w\-]*)["\']{1})?([\s\t]*,[\s\t]*["\']{1}([\s\w\-]*)["\']{1})?([\s\t]*,[\s\t]*["\']{1}([\s\w\-]*)["\']{1})?\s*\)\}/imse'
			=> '_class("form2")->tpl_row(\'$1\',$replace,\'$3\',\'$5\',\'$7\')',
	);
	/** @var array @conf_skip Show custom class method output pattern */
	public $_PATTERN_EXECUTE   = array(
		// EXAMPLE:	 {execute(graphics, translate, value = blabla; extra = strtoupper)
		'/(\{execute\(["\']{0,1})([\s\w\-]+),([\s\w\-]+)[,]{0,1}([^"\'\)\}]*)(["\']{0,1}\)\})/ie'
			=> 'main()->_execute(\'$2\',\'$3\',\'$4\',"{tpl_name}",0,false)',
		'/(\{exec_cached\(["\']{0,1})([\s\w\-]+),([\s\w\-]+)[,]{0,1}([^"\'\)\}]*)(["\']{0,1}\)\})/ie'
			=> 'main()->_execute(\'$2\',\'$3\',\'$4\',"{tpl_name}",0,true)',
	);
	/** @var array @conf_skip Include template pattern */
	public $_PATTERN_INCLUDE   = array(
		// EXAMPLE:	 {include("forum/custom_info")}, {include("forum/custom_info", value = blabla; extra = strtoupper)}
		'/(\{include\(["\']{0,1})([\s\w\\/\.]+)["\']{0,1}?[,]{0,1}([^"\'\)\}]*)(["\']{0,1}\)\})/ie'
			=> '$this->_include_stpl(\'$2\',\'$3\')',
	);
	/** @var array @conf_skip Evaluate custom PHP code pattern */
	public $_PATTERN_EVAL	  = array(
		// EXAMPLE:	 {eval_code(print_r(_class('forum')))}
		'/(\{eval_code\()([^\}]+?)(\)\})/ie'
			=> 'main()->_eval_code(\'$2\', 0)',
	);
	/** @var array @conf_skip Evaluate custom PHP code pattern special for the DEBUG_MODE */
	public $_PATTERN_DEBUG	 = array(
		// EXAMPLE:	 {_debug_get_replace()}
		'/(\{_debug_get_replace\(\)\})/ie'
			=> 'is_array($replace) ? "<pre>".print_r(array_keys($replace),1)."</pre>" : "";',
		// EXAMPLE:	 {_debug_stpl_vars()}
		'/(\{_debug_get_vars\(\)\})/ie'
			=> '$this->_debug_get_vars($string)',
	);
	/** @var array @conf_skip Catch dynamic content into variable */
	// EXAMPLE: {catch("widget_blog_last_post")} {execute(blog,_widget_last_post)} {/catch}
	public $_PATTERN_CATCH	 = '/\{catch\(\s*["\']{0,1}([a-z0-9_\-]+?)["\']{0,1}\)\}(.*?)\{\/catch\}/ims';
	/** @var array @conf_skip STPL internal comment pattern */
	// EXAMPLE:	 {{-- some content you want to comment inside template only --}}
	public $_PATTERN_COMMENT   = '/(\{\{--.*?--\}\})/ims';
	/** @var string @conf_skip Conditional pattern */
	// EXAMPLE: {if("name" eq "New")}<h1 style="color: white;">NEW</h1>{/if}
	public $_PATTERN_IF		= '/\{if\(\s*["\']{0,1}([\w\s\.\-\+\%]+?)["\']{0,1}[\s\t]+(eq|ne|gt|lt|ge|le|mod)[\s\t]+["\']{0,1}([\w\s\-\#]*)["\']{0,1}([^\(\)\{\}\n]*)\)\}/ims';
	/** @var string @conf_skip pattern for multi-conditions */
	public $_PATTERN_MULTI_COND= '/["\']{0,1}([\w\s\.\-\+\%]+?)["\']{0,1}[\s\t]+(eq|ne|gt|lt|ge|le|mod)[\s\t]+["\']{0,1}([\w\s\-\#]*)["\']{0,1}/ims';
	/** @var string @conf_skip Cycle pattern */
	// EXAMPLE: {foreach ("var")}<li>{var.value1}</li>{/foreach}
	public $_PATTERN_FOREACH   = '/\{foreach\(\s*["\']{0,1}([\w\s\.\-]+)["\']{0,1}\)\}((?![^\{]*?\{foreach\(["\']{0,1}?).*?)\{\/foreach\}/is';
	/** @var array @conf_skip For "_process_conditions" */
	public $_cond_operators	= array('eq'=>'==','ne'=>'!=','gt'=>'>','lt'=>'<','ge'=>'>=','le'=>'<=','mod'=>'%');
	/** @var array @conf_skip For '_process_conditions' */
	public $_math_operators	= array('and'=>'&&','xor'=>'xor','or'=>'||','+'=>'+','-'=>'-');
	/** @var array @conf_skip
		For '_process_conditions',
		Will be availiable in conditions with such form: {if('get.object' eq 'login_form')} Hello from login form {/if}
	*/
	public $_avail_arrays	  = array(
		'get'	   => '_GET',
		'post'	  => '_POST',
	);
	/** @var bool Use backtrace to get STPLs source (where called from) FOR DEBUG_MODE ONLY ! */
	public $USE_SOURCE_BACKTRACE	   = true;
	/** @var bool Allow to compile templates */
	public $COMPILE_TEMPLATES		  = false;
	/** @var bool Compile templates folder */
	public $COMPILED_DIR			   = 'stpls_compiled/';
	/** @var bool TTL for compiled stpls */
	public $COMPILE_TTL				= 3600;
	/** @var bool TTL for compiled stpls */
	public $COMPILE_CHECK_STPL_CHANGED = false;

	/**
	* Constructor
	*/
	function __construct () {
		// Try to find PCRE module
		if (!function_exists('preg_match_all')) {
			trigger_error('STPL: PCRE Extension is REQUIRED for the template engine', E_USER_ERROR);
		}
		// Merge eval pattern with main patterns
		if ($this->ALLOW_EVAL_PHP_CODE) {
			foreach ((array)$this->_PATTERN_EVAL as $k => $v) {
				$this->_STPL_PATTERNS[$k] = $v;
			}
		}
		if (DEBUG_MODE) {
			foreach ((array)$this->_PATTERN_DEBUG as $k => $v) {
				$this->_STPL_PATTERNS[$k] = $v;
			}
		}
	}

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Framework constructor
	*/
	function _init () {
		$this->_init_global_tags();
	}

	/**
	* Global scope tags
	*/
	function _init_global_tags () {
		$data = array(
			'is_logged_in'  => intval((bool) main()->USER_ID),
			'is_spider'     => (int)conf('IS_SPIDER'),
			'is_https'      => isset($_SERVER['HTTPS']) || isset($_SERVER['SSL_PROTOCOL']) ? 1 : 0,
			'site_id'       => (int)conf('SITE_ID'),
			'lang_id'       => conf('language'),
			'debug_mode'    => (int)((bool)DEBUG_MODE),
			'tpl_path'      => MEDIA_PATH. $this->TPL_PATH,
		);
		foreach ($data as $k => $v) {
			$this->_global_tags[$k] = $v;
		}
	}

	/**
	* Process output filters for the given text
	*/
	function _apply_output_filters ($text = '') {
		foreach ((array)$this->_OUTPUT_FILTERS as $cur_filter) {
			if (is_callable($cur_filter)) {
				$text = call_user_func($cur_filter, $text);
			}
		}
		return $text;
	}

	/**
	* Initialization of the main template in the theme (could be overwritten to match design)
	* Return contents of the main template
	*/
	function _init_main_stpl ($tpl_name = '') {
		return $this->parse($tpl_name);
	}

	/**
	* Wrapper to parse given template string
	*/
	function parse_string($name = '', $replace = array(), $string = '', $params = array()) {
		if (!strlen($string)) {
			$string = ' ';
		}
		$params['string'] = $string;
		return $this->parse(!empty($name) ? $name : abs(crc32($string)), $replace, $params);
	}

	/**
	* Simple template parser (*.stpl)
	*/
	function parse($name, $replace = array(), $params = array()) {
		$name = strtolower($name);
		// Support for the framework calls
		$yf_prefix = 'yf_';
		$yfp_len = strlen($yf_prefix);
		if (substr($name, 0, $yfp_len) == $yf_prefix) {
			$name = substr($name, $yfp_len);
		}
		if (!is_array($params)) {
			$params = array();
		}
		$string = $params['string'] ?: false;
		$params['replace_images'] = $params['replace_images'] ?: true;
		$params['no_cache'] = $params['no_cache'] ?: false;
		$params['get_from_db'] = $params['get_from_db'] ?: false;
		$params['no_include'] = $params['no_include'] ?: false;
		if (DEBUG_MODE) {
			$stpl_time_start = microtime(true);
		}
		$replace = (array)$replace + (array)$this->_global_tags;
		$replace['error'] = $this->_parse_get_user_errors($name, $replace['error']);
		if (isset($replace[''])) {
			unset($replace['']);
		}
		if ($this->ALLOW_CUSTOM_FILTER) {
			$this->_custom_filter($name, $replace);
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
		if (isset($params['clear_all'])) {
			$string = $this->_process_eval_unused($string, $replace, $name);
		}
		if (isset($params['eval_content'])) {
			$string = $this->_process_eval_string($string, $replace, $name);
		}
		if ($params['replace_images']) {
			$string = common()->_replace_images_paths($string);
		}
		if (DEBUG_MODE) {
			$this->_parse_set_debug_info($name, $replace, $params, $string, $stpl_time_start);
		}
		return $string;
	}

	/**
	*/
	function _parse_get_compiled($name, $replace = array(), $params = array()) {
		if (!$this->COMPILE_TEMPLATES) {
			return null;
		}
# TODO: add ability to use memcached or other fast cache-oriented storage instead of files => lower disk IO
		$compiled_path = PROJECT_PATH. $this->COMPILED_DIR.'c_'.MAIN_TYPE.'_'.urlencode($name).'.php';
		if (file_exists($compiled_path) && ($_compiled_mtime = filemtime($compiled_path)) > (time() - $this->COMPILE_TTL)) {
			$_compiled_ok = true;

			ob_start();
			include ($compiled_path);
			$string = ob_get_contents();
			ob_end_clean();

			if ($this->COMPILE_CHECK_STPL_CHANGED) {
				$_stpl_path = $this->_get_template_file($name, $params['get_from_db'], 0, 1);
				if ($_stpl_path) {
					$_source_mtime = filemtime($_stpl_path);
				}
				if (!$_stpl_path || $_source_mtime > $_compiled_mtime) {
					$_compiled_ok = false;
					$string = false;
				}
			}
			if ($_compiled_ok) {
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
				return $string;
			}
		}
		return null;
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
				$string = $this->_get_template_file($name, $params['get_from_db']);
			}
			if ($string === false) {
				return false;
			}
			$string = preg_replace($this->_PATTERN_COMMENT, '', $string);
			if ($this->COMPILE_TEMPLATES) {
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
	function _parse_get_user_errors($name, $err) {
		if (isset($err)) {
			return $err;
		}
		$err = '';
		if ($name != 'main' && common()->_error_exists()) {
			if (!isset($this->_user_error_msg)) {
				$this->_user_error_msg = common()->_show_error_message('', false);
			}
			$err = $this->_user_error_msg;
		}
		return $err;
	}

	/**
	*/
	function _parse_set_debug_info($name = '', $replace = array(), $params = array(), $string = '', $stpl_time_start) {
		if (!DEBUG_MODE) {
			return false;
		}
		if (!isset($this->CACHE[$name]['exec_time'])) {
			$this->CACHE[$name]['exec_time'] = 0;
		}
		$this->CACHE[$name]['exec_time'] += (microtime(true) - $stpl_time_start);
		// For debug store information about variables used while processing template
		if ($this->DEBUG_STPL_VARS) {
			$d = debug('STPL_REPLACE_VARS::'.$name);
			$next = is_array($d) ? count($d) : 0;
			debug('STPL_REPLACE_VARS::'.$name.'::'.$next, $replace);
		}
		if ($this->USE_SOURCE_BACKTRACE) {
			debug('STPL_TRACES::'.$name, main()->trace_string());
		}
		if ($this->ALLOW_INLINE_DEBUG && strlen($string) > 20 && !in_array($name, array('main', 'system/debug_info', 'system/js_inline_editor')) ) {
			if (preg_match('/^<([^>]*?)>/ims', ltrim($string), $m)) {
				$string = '<'.$m[1].' stpl_name="'.$name.'">'.substr(ltrim($string), strlen($m[0]));
			}
		}
		return true;
	}

	/**
	*/
	function _process_includes($string, $replace = array(), $name = '') {
		return preg_replace(key($this->_PATTERN_INCLUDE), current($this->_PATTERN_INCLUDE), $string);
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
		if (false === strpos($string, '{execute(') || empty($string)) {
			return $string;
		}
		// Replace template vars, marked with '#' sign, before do execute pattern
		if (false !== strpos($string, '#') && !empty($replace)) {
			$pairs = array();
			foreach ((array)$replace as $k => $v) {
				$pairs['#'.$k] = $v;
			}
			$string = str_replace(array_keys($pairs), array_values($pairs), $string);
		}
		return preg_replace(array_keys($this->_PATTERN_EXECUTE), str_replace('{tpl_name}', $name.$this->_STPL_EXT, array_values($this->_PATTERN_EXECUTE)), $string, --$this->STPL_REPLACE_LIMIT > 0 ? $this->STPL_REPLACE_LIMIT : -1);
	}

	/**
	* Replace standard patterns
	*/
	function _replace_std_patterns($string, $name = '', $replace = array(), $params = array()) {
		return preg_replace(array_keys($this->_STPL_PATTERNS), str_replace('{tpl_name}', $name.$this->_STPL_EXT, array_values($this->_STPL_PATTERNS)), $string, --$this->STPL_REPLACE_LIMIT > 0 ? $this->STPL_REPLACE_LIMIT : -1);
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
			$cur_operator   = $this->_cond_operators[strtolower($m[2][$k])];
			$part_right	 = $m[3][$k];
			if ($part_right && $part_right{0} == '#') {
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
			// Special case for "mod". Example: {if("id" mod 4)} content {/if}
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
		$part_right		= $m[3];
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
				if (isset($this->_avail_arrays[$try_elm])) {
					$res_v = '$'.$this->_avail_arrays[$try_elm].$try_elm2;
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
			$output		 = '';
			$sub_array	  = array();
			$sub_replace	= array();
			$key_to_cycle   = &$m[1][$match_id];
			$sub_template   = &$m[2][$match_id];
			$sub_template   = str_replace('#.', $key_to_cycle.'.', $sub_template);
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
					// Prepare items for condition
					$tmp_array = $non_array_replace;
					foreach ((array)$sub_v as $k6 => $v6) {
						$tmp_array[$key_to_cycle.'.'.$k6] = $v6;
					}
					$tmp_array['_num']	= $_i;
					$tmp_array['_total']= $_total;
					$tmp_array['_first']= $_is_first;
					$tmp_array['_last']	= $_is_last;
					$tmp_array['_even']	= $_is_odd;
					$tmp_array['_odd']	= $_is_even;
					$tmp_array['_key']	= $sub_k;
					$tmp_array['_val']	= is_array($sub_v) ? strval($sub_v) : $sub_v;
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
	* Wrapper for '_PATTERN_INCLUDE', allows you to include stpl, optionally pass $replace params to it
	*/
	function _include_stpl ($stpl_name = '', $params = '') {
		$replace = array();
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

	/**
	* Registers custom function to be used in templates
	*/
	function register_output_filter($callback_impl, $filter_name = '') {
		if (empty($filter_name)) {
			$filter_name = substr(abs(crc32(microtime(true))),0,8);
		}
		$this->_OUTPUT_FILTERS[$filter_name] = $callback_impl;
	}

	/**
	* Simple cleanup (compress) output
	*/
	function _simple_cleanup_callback ($text = '') {
		if (DEBUG_MODE) {
			debug('compress_output_size_1', strlen($text));
		}
		$text = str_replace(array("\r","\n","\t"), '', $text);
		$text = preg_replace('#[\s]{2,}#ms', ' ', $text);
		// Remove comments
		$text = preg_replace('#<\!--[\w\s\-\/]*?-->#ms', '', $text);
		if (DEBUG_MODE) {
			debug('compress_output_size_2', strlen($text));
		}
		return $text;
	}

	/**
	* Custom text replacing method
	*/
	function _custom_replace_callback ($text = '') {
		return _class('custom_meta_info')->_process($text);
	}

	/**
	* Replace method for 'IFRAME in center' mode
	*/
	function _replace_for_iframe_callback ($text = '') {
		return module('rewrite')->_replace_links_for_iframe($text);
	}

	/**
	* Rewrite links callback method
	*/
	function _rewrite_links_callback ($text = '') {
		return module('rewrite')->_rewrite_replace_links($text);
	}

	/**
	* Clenup HTML output with Tidy
	*/
	function _tidy_cleanup_callback ($text = '') {
		if (!class_exists('tidy') || !extension_loaded('tidy')) {
			return $text;
		}
		// Tidy
		$tidy = new tidy;
		$tidy->parseString($text, $this->_TIDY_CONFIG, conf('charset'));
		$tidy->cleanRepair();
		// Output
		return $tidy;
	}

	/**
	*/
	function _debug_mode_callback ($text = '') {
		if (!DEBUG_MODE) {
			return $text;
		}
		$p = "<span class='locale_tr' s_var='[^\']+?'>([^<]+?)<\/span>";
		$text = preg_replace("/(<title>)(.*?)(<\/title>)/imse", "'\\1'.strip_tags('\\2').'\\3'", $text);
		// Output
		return $text;
	}

	/**
	* Custom filter (Inherit this method and customize anything you want)
	*/
	function _custom_filter ($stpl_name = '', &$replace) {
		if ($stpl_name == 'home_page/main') {
			// example only:
			//print_r($replace);
			//$replace['recent_ads'] = '';
		}
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
	* Compile given template into pure PHP code
	*/
	function _compile($name, $replace = array(), $string = '') {
		return _class('tpl_compile', 'classes/tpl/')->_compile($name, $replace, $string);
	}

	/**
	* Wrapper function for t/translate/i18n calls inside templates
	*/
	function _i18n_wrapper ($input = '', $replace = array()) {
		if (!strlen($input)) {
			return '';
		}
		$input = stripslashes(trim($input, '"\''));
		$args = array();
		// Complex case with substitutions
		if (preg_match('/(?P<text>.+?)["\']{1},[\s\t]*%(?P<args>[a-z]+.+)$/ims', $input, $m)) {
			foreach (explode(';%', $m['args']) as $arg) {
				$attr_name = $attr_val = '';
				if (false !== strpos($arg, '=')) {
					list($attr_name, $attr_val) = explode('=', trim($arg));
				}
				$attr_name  = trim(str_replace(array("'",'"'), '', $attr_name));
				$attr_val   = trim(str_replace(array("'",'"'), '', $attr_val));
				$args['%'.$attr_name] = $attr_val;
			}
			$text_to_translate = $m['text'];
		} else {
			// Easy case that just needs to be translated
			$text_to_translate = $input;
		}
		$output = translate($text_to_translate, $args);
		// Do replacement of the template vars on the last stage
		// example: @replace1 will be got from $replace['replace1'] array item
		if (false !== strpos($output, '@') && !empty($replace)) {
			$r = array();
			foreach ((array)$replace as $k => $v) {
				$r['@'.$k] = $v;
			}
			$output = str_replace(array_keys($r), array_values($r), $output);
		}
		return $output;
	}

	/**
	* Wrapper around '_generate_url' function, called like this inside templates:
	* {url(object=home_page;action=test)}
	*/
	function _generate_url_wrapper ($params = array()){
		if(!function_exists('_force_get_url')) return '';
		// Try to process method params (string like attrib1=value1;attrib2=value2)
		if (is_string($params) && strlen($params)) {
			$tmp_params	 = explode(';', $params);
			$params  = array();
			// Convert params string into array
			foreach ((array)$tmp_params as $v) {
				$attrib_name = '';
				$attrib_value = '';
				if (false !== strpos($v, '=')) {
					list($attrib_name, $attrib_value) = explode('=', trim($v));
				}
				$params[trim($attrib_name)] = trim($attrib_value);
			}
		}
		return _force_get_url($params);
	}
}
