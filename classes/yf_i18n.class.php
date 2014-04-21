<?php

/**
* Locale handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_i18n {

	/** @var bool Replace underscore '_' into space ' ' in translate_string */
	public $REPLACE_UNDERSCORE	= true;
	/** @var bool Translation on/off */
	public $TRANSLATE_ENABLED	= true;
	/** @var bool Try to find and insert not existed vars (only if DEBUG_MODE && TRANSLATE_ENABLED) */
	public $AUTO_FIND_VARS		= false;
	/** @var bool Allow user to force change lange language */
	public $ALLOW_SESSION_LANG	= true;
	/** @var bool Display translated vars (only if DEBUG_MODE && TRANSLATE_ENABLED) */
	public $TRACK_TRANSLATED	= true;
	/** @var bool */
	public $VARS_IGNORE_CASE	= true;
	/** @var bool */
	public $TRACK_FIRST_LETTER_CASE	= true;
	/** @var string Current locale code */
	public $CUR_LOCALE			= 'en';
	/** @var string Current charset code */
	public $CUR_CHARSET			= 'utf-8';
	/** @var array @conf_skip Active languages */
	public $LANGUAGES			= array();
	/** @var array */
	public $_HTML_ENTITIES		= array(
		'_' => '&#95;', "'" => '&#39;', '"' => '&quot;', '/' => '&frasl;', "\\"=> '&#92;', '[' => '&#91;', ']' => '&#93;', 
		'(' => '&#40;', ')' => '&#41;', '{' => '&#123;', '}' => '&#125;', '?' => '&#63;', '!' => '&#33;', '|' => '&#124;',
	);
	/** @var bool Allow to find vars in shared place inside files */
	public $ALLOW_SHARED_LANG_FILES	= true;
	/** @var bool Allow to find vars in modules sub-folders */
	public $ALLOW_MODULE_FILES		= true;
	/** @var bool User-only translation for members */
	public $ALLOW_USER_TRANSLATE	= false;
	/** @var bool In-Memory cachig */
	public $USE_TRANSLATE_CACHE		= true;

	/**
	* Framework constructor
	*/
	function _init() {
		// Inline locale editor
		if (DEBUG_MODE && isset($_SESSION['locale_vars_edit'])) {
			$this->TRACK_TRANSLATED = intval((bool)$_SESSION['locale_vars_edit']);
			main()->INLINE_EDIT_LOCALE = intval((bool)$_SESSION['locale_vars_edit']);
		}
		conf('languages', main()->get_data('locale_langs'));
		// Force default language as it set in locale editor
		foreach ((array)conf('languages') as $lang_info) {
			if ($lang_info['is_default'] == 1) {
				$this->CUR_LOCALE = $lang_info['locale'];
				break;
			}
		}
		// Default language (could be set for site)
		if (defined('DEFAULT_LANG') && DEFAULT_LANG != '') {
			$this->CUR_LOCALE = DEFAULT_LANG;
		}
		if ($this->ALLOW_SESSION_LANG && MAIN_TYPE_USER) {
			// Catch language if it comes from $_GET
			if (!empty($_GET['language']) && conf('languages::'.$_GET['language'])) {
				if ($_SESSION['user_lang'] != $_GET['language']) {
					$_SESSION['user_lang'] == $_GET['language'];
				}
			}
			if (MAIN_TYPE_USER && !empty($_SESSION['user_lang']) && conf('languages::'.$_SESSION['user_lang'])) {
				$this->CUR_LOCALE = $_SESSION['user_lang'];
			}
		}
		// Force to get all available vars (try to find and insert new ones)
		if (DEBUG_MODE && $this->AUTO_FIND_VARS && $this->TRANSLATE_ENABLED) {
			$q = db()->query('SELECT id,value FROM '.db('locale_vars').'');
			while ($a = db()->fetch_assoc($q)) {
				$this->TR_ALL_VARS[$a['value']] = $a['id'];
			}
			if (!empty($this->TR_ALL_VARS)) {
				ksort($this->TR_ALL_VARS);
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
	* Constructor
	*/
	function init_locale () {
		$this->_load_lang($this->CUR_LOCALE);
		// Get charset from the current language
		$charset = conf('languages::'.$this->CUR_LOCALE.'::charset');
		// Force UTF-8 for the admin section
		if (MAIN_TYPE_ADMIN && $this->CUR_LOCALE == 'en') {
			$charset = 'utf-8';
		}
		conf('charset', !empty($charset) ? $charset : $this->CUR_CHARSET);
		conf('language', $this->CUR_LOCALE);
		$this->CUR_CHARSET = conf('charset');
		// Try to set PHP's locale (provide several possible values)
		setlocale(LC_ALL, array(
			strtolower($this->CUR_LOCALE),
			strtolower($this->CUR_LOCALE).'_'.strtoupper($this->CUR_LOCALE),
			strtolower($this->CUR_LOCALE).'_'.strtoupper($this->CUR_LOCALE).'.'.$this->CUR_CHARSET,
			strtolower(conf('languages::'.$this->CUR_LOCALE.'::name')),
		));
	}

	/**
	* Get current language
	*/
	function _get_current_lang() {
		$FORCE_LOCALE = conf('FORCE_LOCALE');
		if ($FORCE_LOCALE && conf('languages::'.$FORCE_LOCALE)) {
			return $FORCE_LOCALE;
		}
		return $this->CUR_LOCALE;
	}

	/**
	* Load language
	*/
	function _load_lang($lang = '') {
		// Get all translation for the current language (if needed)
		if (!$this->TRANSLATE_ENABLED) {
			return false;
		}
		if (!$lang) {
			$lang = $this->_get_current_lang();
		}
		if (!$lang || isset($this->_loaded[$lang])) {
			return false;
		}
		$this->_loaded[$lang] = false;

		$this->_load_lang_get_vars_from_db($lang);
		$this->_load_lang_get_vars_from_files($lang);
		$this->_load_lang_get_user_translate($lang, main()->USER_ID);

		// Pre-format vars if case sensetivity
		if ($this->VARS_IGNORE_CASE) {
			$tmp_vars = array();
			foreach ((array)$this->TR_VARS[$lang] as $_var_name => $_value) {
				$_var_name = strtolower($_var_name);
				if ($this->REPLACE_UNDERSCORE) {
					$_var_name = str_replace(' ', '_', $_var_name);
					$_var_name = str_replace("'", '&#39;', $_var_name);
				}
				$tmp_vars[$_var_name] = $_value;
			}
			$this->TR_VARS[$lang] = $tmp_vars;
			unset($tmp_vars);
		}
//		conf('language', $lang);
		$this->_loaded[$lang] = true;
	}

	/**
	* Default storage of translations
	*/
	function _load_lang_get_vars_from_db($lang) {
		$CACHE_NAME = 'locale_translate_'.$lang;
		$data = cache_get($CACHE_NAME);
		if (!$data && !is_array($data)) {
			$data = array();
			$q = db()->query(
				'SELECT v.value AS source, t.value AS translation 
				FROM '.db('locale_vars').' AS v, '.db('locale_translate').' AS t 
				WHERE t.var_id=v.id 
					AND t.locale="'._es($lang).'" 
					AND t.value != "" 
					AND t.value != v.value'
			);
			while ($a = db()->fetch_assoc($Q)) {
				$data[$a['source']] = $a['translation'];
			}
			cache_set($CACHE_NAME, $data);
		}
		foreach ((array)$data as $k => $v) {
			$this->TR_VARS[$lang][$k] = $v;
		}
	}

	/**
	* Member-only translations
	*/
	function _load_lang_get_user_translate($lang, $user_id) {
		$user_id = intval($user_id);
		if ($this->ALLOW_USER_TRANSLATE && $user_id) {
			$q = db()->query(
				'SELECT name, translation 
				FROM '.db('locale_user_tr').' 
				WHERE user_id='.intval($user_id).' 
					AND locale="'._es($lang).'"
					AND translation != ""
					AND translation != name'
			);
			while ($a = db()->fetch_assoc($q)) {
				$this->TR_VARS[$lang][$a['name']] = $a['translation'];
			}
		}
	}

	/**
	* Load language varas from files
	*/
	function _load_lang_get_vars_from_files($lang) {
		$lang_files = array();
		// Auto-find shared language vars. They will be connected in order of file system
		// Names can be any, but better to include lang name into file name. Examples: 
		// share/langs/ru/001_other.php
		// share/langs/ru/002_other2.php
		// share/langs/ru/other.php
		// share/langs/ru/ru_shop.php
		// share/langs/ru/ru_user_register.php
		// plugins/shop/share/langs/ru/ru_user_register.php
		if ($this->ALLOW_SHARED_LANG_FILES) {
			$dirs = array(
				'yf_main'			=> YF_PATH.'share/langs/'.$lang.'/',
				'yf_plugins'		=> YF_PATH.'plugins/*/share/langs/'.$lang.'/',
				'project_main'		=> PROJECT_PATH.'share/langs/'.$lang.'/',
				'project_plugins'	=> PROJECT_PATH.'plugins/*/share/langs/'.$lang.'/',
			);
			if (SITE_PATH != PROJECT_PATH) {
				$dirs['site'] = SITE_PATH.'share/langs/'.$lang.'/';
			}
			// Order matters! Project vars will have ability to override vars from franework
			foreach ($dirs as $dir) {
				foreach ((array)glob($dir.'*.php') as $f) {
					$lang_files[basename($f)] = $f;
				}
			}
		}
		// Auto-find vars for user modules. They will be connected in order of file system
		// Names must begin with __locale__{lang} and then any name. Examples: 
		// modules/shop/__locale__ru.php
		// modules/shop/__locale__ru_orders.php
		// modules/shop/__locale__ru_products.php
		// plugins/shop/modules/shop/__locale__ru_products.php
		if ($this->ALLOW_MODULE_FILES) {
			$m_dir = (MAIN_TYPE_USER ? 'modules/' : 'admin_modules/');
			$dirs = array(
				'yf_main'			=> YF_PATH. $m_dir,
				'yf_plugins'		=> YF_PATH. 'plugins/*/'. $m_dir,
				'project_main'		=> PROJECT_PATH. $m_dir,
				'project_plugins'	=> PROJECT_PATH. 'plugins/*/'. $m_dir,
			);
			if (MAIN_TYPE_USER && SITE_PATH != PROJECT_PATH) {
				$dirs['site'] = SITE_PATH. $m_dir;
			}
			// Order matters! Project vars will have ability to override vars from franework
			foreach ($dirs as $dir) {
				foreach ((array)glob($dir.'/*/__locale__'.$lang.'*.php') as $f) {
					$lang_files[basename($f)] = $f;
				}
			}
		}
		//
		// Inside each file $data array will be searched for
		//
		foreach ((array)$lang_files as $path) {
			include $path;
			foreach ((array)$data as $_source => $_trans) {
				$_source = str_replace(' ', '_', strtolower($_source));
				$this->TR_VARS[$lang][$_source] = $_trans;
			}
		}
	}

	/**
	* Translation of the given string
	*
	* Some common symbol codes in HTML:
	*	_ => '&#95;'
	*	' => '&prime;' or '&#39;'
	*	' => '&quot;'
	*	/ => '&frasl;'
	*	\ => '&#92;'
	*	[ => '&#91;'
	*	] => '&#93;'
	*	( => '&#40;'
	*	) => '&#41;'
	*	{ => '&#123;'
	*	} => '&#125;'
	*	? => '&#63;'
	*	! => '&#33;'
	*	| => '&#124;'
	*
	* @code
	*	$msg = t('You must log in below or <a href="%url">create a new account</a> before viewing the next page.', 
	*			array('%url' => process_url('./?object=user&action=register')));
	* @endcode
	*
	* We have ability to use custom prefix for vars with same names in different places with different translations
	* ex. for var "welcome" we can have several vars with prefixes  "::forum::welcome"
	* Prefix syntax:	 "::[a-z_-]::text to tranlate here"
	*
	* @access	public
	* @param	$string string	Text to translate
	* @param	$args	array	Optional array of items to replace after translation
	* @return string Translation result
	*/
	function translate_string ($input_string, $args = 0, $lang = '') {
		if (DEBUG_MODE) {
			$_start_time = microtime(true);
		}
		$lang = strval($lang);
		if (!$lang) {
			$lang = $this->_get_current_lang();
		}
		if (!isset($this->_loaded[$lang])) {
			$this->_load_lang($lang);
		}
		if (!$lang || !$this->_loaded[$lang]) {
			return $input_string;
		}
		if (is_array($args) && isset($args[''])) {
			unset($args['']);
		}
		if (is_array($input_string)) {
			foreach ((array)$input_string as $k => $v) {
				$input_string[$k] = $this->translate_string($v, $args, $lang);
			}
			return $input_string;
		}
		if (!$input_string) {
			return $input_string;
		}
		$input_string = trim($input_string);
		if ($this->USE_TRANSLATE_CACHE && empty($args)) {
			$CACHE_NAME = $lang.'#____#'.$input_string;
			if (isset($this->_LOCALE_CACHE[$CACHE_NAME])) {
				return $this->_LOCALE_CACHE[$CACHE_NAME];
			}
		}
		$is_translated = false;
		$_source = $input_string;
		$output_string = $input_string;
		// Try to find prefix (starts with '::')
		$_prefix = '';
		$_prefix_length = 0;
		if ($input_string{0} == ':' && $input_string{1} == ':') {
			$_prefix = substr($input_string, 0, strpos($input_string, '::', 2) + 2);
			$_prefix_length = strlen($_prefix);
			$input_string = substr($input_string, $_prefix_length);
		}
		if ($this->TRANSLATE_ENABLED) {
			// Prepare for case ignore
			if ($this->VARS_IGNORE_CASE) {
				$first_input_string = $input_string;
				$input_string = strtolower($input_string);
				if ($this->REPLACE_UNDERSCORE) {
					$input_string = str_replace('&nbsp;', ' ', $input_string);
					$input_string = str_replace(' ', '_', $input_string);
				}
			}
			// Try to find prefix (starts with '::') again
			if (!strlen($_prefix) && isset($this->TR_VARS[$lang]['::'.$_GET['object'].'::'. $input_string])) {
				$_prefix = '::'.$_GET['object'].'::';
			}
			// First try to translate var with prefix
			if (strlen($_prefix) && isset($this->TR_VARS[$lang][$_prefix. $input_string])) {
				$output_string = $this->TR_VARS[$lang][$_prefix. $input_string];
				$is_translated = true;
			// Then common var
			} elseif (isset($this->TR_VARS[$lang][$input_string])) {
				$output_string = $this->TR_VARS[$lang][$input_string];
				$is_translated = true;
			// Then try _un_html_entities
			} elseif (($var_un_html = $this->_un_html_entities($input_string)) && isset($this->TR_VARS[$lang][$var_un_html])) {
				$output_string = $this->TR_VARS[$lang][$var_un_html];
				$is_translated = true;
			// Last - is untranslated
			} else {
if (strtolower(substr($input_string, 0, 4)) == 'jpeg') {
	echo $input_string;
	foreach ($this->TR_VARS[$lang] as $k => $v) {
		if (strtolower(substr($k, 0, 4)) == 'jpeg') {
			echo '<br>'.$k.' | '.$v. '<br>'. PHP_EOL;
		}
	}
}
				$output_string = $input_string;
				if (DEBUG_MODE) {
					if (!isset($this->_NOT_TRANSLATED)) {
						$this->_NOT_TRANSLATED = array();
					}
					if (!isset($this->_NOT_TRANSLATED[$lang])) {
						$this->_NOT_TRANSLATED[$lang] = array();
					}
					if (!isset($this->_NOT_TRANSLATED[$lang][$input_string])) {
						$this->_NOT_TRANSLATED[$lang][$input_string] = 0;
					}
					$this->_NOT_TRANSLATED[$lang][$input_string]++;
					// Check if such variable exists
					if ($this->AUTO_FIND_VARS && !isset($this->TR_ALL_VARS[$input_string])) {
						$this->insert_var($input_string);
					}
				}
			}
		}
		// Force replace underscore '_' chars into spaces ' ' (only if string not translated)
		if ($this->REPLACE_UNDERSCORE && !$is_translated) {
			$output_string = str_replace('_', ' ', $_source);
			if ($_prefix_length) {
				$output_string = substr($output_string, $_prefix_length);
			}
		}
		// Replace with arguments
		if (!empty($args) && is_array($args)) {
			$output_string = $this->_process_sub_patterns($output_string, $args);
			$output_string = strtr($output_string, $args);
		}
		// Try to change translation case according to original
		if ($this->TRACK_FIRST_LETTER_CASE && $is_translated) {
			$input = $this->VARS_IGNORE_CASE ? $first_input_string : $input_string;

			$first_s = substr($input, 0, 1);
			$first_t = _substr($output_string, 0, 1);
			$first_s_lower = strtolower($first_s) == $first_s;
			$first_t_lower = _strtolower($first_t) == $first_t;
			if (!$first_s_lower && $first_t_lower) {
				$output_string = _strtoupper($first_t). _substr($output_string, 1);
			}
		}
		if (DEBUG_MODE) {
			if ($this->TRACK_TRANSLATED) {
				$this->_I18N_VARS[$lang][$_source] = $output_string;
				if (main()->INLINE_EDIT_LOCALE && !main()->_IS_REDIRECTING) {
					$r = array(
						' ' => '%20',
						'='	=> '&equals;',
						'<' => '&lt;',
						'>' => '&gt;',
					);
					$s_var = _prepare_html(str_replace(array_keys($r), array_values($r), $_source));
					$output_string = '<span class=locale_tr s_var='.$s_var.'>'.$output_string.'</span>';
				}
			}
			$this->_tr_total_time += (microtime(true) - $_start_time);
			if (!isset($this->_tr_time[$lang])) {
				$this->_tr_time[$lang] = array();
			}
			$this->_tr_time[$lang][$input_string] += (microtime(true) - $_start_time);
			$this->_tr_calls[$lang][$input_string]++;

		}
		// Put to cache
		if ($this->USE_TRANSLATE_CACHE && empty($args)) {
			$this->_LOCALE_CACHE[$CACHE_NAME] = $output_string;
		}
		return $output_string;
	}

	/**
	* Process sub-patterns for translate depending on number value
	*
	* @sample:
	* {t(While searching %num folders found,%num=1001)}
	* В процессе поиска {Найдено %num папок|0:Папок не найдено|1:Найдена %num папка|2,3,4:Найдено %num папки|11-14:Найдено %num папок|Найдено %num папок}
	*/
	function _process_sub_patterns($text = '', $args = array()) {
		// Quick check for sub-patterns
		if (false === strpos($text, '{') || !is_array($args)) {
			return $text;
		}
		$new_replace = array();

		$pattern = '/\{([^\}\|]+?)\|([^\}]+?)\}/ims';
		preg_match_all($pattern, $text, $m);
		foreach ((array)$m[0] as $_id => $_source) {
			preg_match('/%[a-z\_]+/ims', $m[1][$_id], $m2);
			if (!$m2[0]) {
				continue;
			}
			$number = intval($args[$m2[0]]);
			// Parse translate variants
			$variants = explode('|', $m[2][$_id]);
			$common_variant = array_pop($variants);
			$pairs = array(
				'other'	=> $common_variant,
			);
			$exacts = array();
			foreach ((array)$variants as $_variant) {
				list($_quantity, $_sub_replace) = explode(':', $_variant);
				if (!strlen($_quantity)) {
					continue;
				}
				// Exact value?
				if ($_quantity{0} == '#') {
					$i = intval(substr($_quantity, 1));
					$exacts[$i] = $_sub_replace;
				// Check if we have range here
				} elseif (false !== strpos($_quantity, '-')) {
					list($_start, $_stop) = explode('-', $_quantity);
					for ($i = $_start; $i <= $_stop; $i++) {
						$pairs[$i] = $_sub_replace;
					}
				// Check if we have several values
				} elseif (false !== strpos($_quantity, ',')) {
					foreach (explode(',', $_quantity) as $i) {
						$pairs[intval($i)] = $_sub_replace;
					}
				} elseif (is_numeric($_quantity)) {
					$pairs[intval($_quantity)] = $_sub_replace;
				} else {
					// Unknown quantity, do nothing
				}
			}
			$_last_digit = $number % 10;
			$_last_digit_100 = $number % 100;

			$replace_into = '';
			if (!empty($exacts) && isset($exacts[$number])) {
				$replace_into = $exacts[$number];
			} elseif ($number == 0) {
				$replace_into = isset($pairs[0]) ? $pairs[0] : $pairs['other'];
			} elseif ($_last_digit_100 > 0 && isset($pairs[$_last_digit_100])) {
				$replace_into = $pairs[$_last_digit_100];
			} elseif ($_last_digit > 0 && isset($pairs[$_last_digit])) {
				$replace_into = $pairs[$_last_digit];
			} elseif (isset($pairs[$number])) {
				$replace_into = $pairs[$number];
			} else {
				$replace_into = $pairs['other'];
			}
			$new_replace[$_source] = $replace_into;
		}
		if (!empty($new_replace)) {
			$text = str_replace(array_keys($new_replace), array_values($new_replace), $text);
			$text = strtr($text, $args);
		}
		return $text;
	}

	/**
	* Convert HTML entities to their text sysmbols
	*/
	function _un_html_entities($text = '') {
		return str_replace(array_values($this->_HTML_ENTITIES), array_keys($this->_HTML_ENTITIES), $text);
	}

	/**
	* Insert missed var
	*/
	function insert_var ($var_name) {
		if (empty($var_name)) {
			return false;
		}
		db()->insert('locale_vars', array(
			'value'		=> _es($var_name),
			'location'	=> '',
		));
	}

	/**
	* Lists available system locales (on *NIX)
	*/
	function _list_system_locales() {
		ob_start();
		system('locale -a'); 
		$str = ob_get_contents();
		ob_end_clean();
		return split("\\n", trim($str));
	}
}
