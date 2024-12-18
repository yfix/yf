<?php

/**
 * Locale handler.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_i18n
{
    /** @var array @conf_skip */
    private static $HTML_ENTS = [
        '_' => '&#95;', "'" => '&#39;', '"' => '&quot;', '/' => '&frasl;', '\\' => '&#92;', '[' => '&#91;', ']' => '&#93;',
        '(' => '&#40;', ')' => '&#41;', '{' => '&#123;', '}' => '&#125;', '?' => '&#63;', '!' => '&#33;', '|' => '&#124;',
    ];

    /** @var array Enabled methods for current lang detection */
    public $CURRENT_LANG_PRIORITIES = [
        'url',		// force override by url param
        'session',	// saved selection inside session
        'cookie',	// saved selection inside cookie, usually for came back user
        'user',		// user saved setting
        'http',		// from http accept
        'country',	// default lang by detected country
        'conf',		// set with $CONF
        'admin',	// set inside admin web panel
        'site',		// site/domain default
        'app',		// App default, usually set inside conf
    ];
    /** @var string Current locale code */
    public $CUR_LOCALE = 'en';
    /** @var string Current charset code */
    public $CUR_CHARSET = 'utf-8';
    /** @var array @conf_skip Active languages */
    public $LANGUAGES = [];
    /** @var bool Translation on/off */
    public $TRANSLATE_ENABLED = true;
    /** @var bool Try to find and insert not existed vars (only if DEBUG_MODE && TRANSLATE_ENABLED) */
    public $AUTO_FIND_VARS = false;
    /** @var bool Allow user to force change lange language */
    public $ALLOW_SESSION_LANG = true;
    /** @var bool Display translated vars (only if DEBUG_MODE && TRANSLATE_ENABLED) */
    public $TRACK_TRANSLATED = true;
    /** @var bool */
    public $VARS_IGNORE_CASE = true;
    /** @var bool */
    public $TRACK_FIRST_LETTER_CASE = true;
    /** @var bool Allow to find vars in shared place inside files */
    public $ALLOW_SHARED_LANG_FILES = true;
    /** @var bool Allow to find vars in modules sub-folders */
    public $ALLOW_MODULE_FILES = true;
    /** @var bool User-only translation for members */
    public $ALLOW_USER_TRANSLATE = false;
    /** @var bool In-Memory cachig */
    public $USE_TRANSLATE_CACHE = true;

    public $TR_VARS = [];
    public $TR_ALL_VARS = [];
    public $_LOCALE_CACHE = [];
    public $_NOT_TRANSLATED = [];
    public $_called = [];
    public $_loaded = [];
    public $_calls = [];
    public $CUR_COUNTRY = null;
    public $WRAP_VARS_FOR_INLINE_EDIT = null;

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }

    /**
     * Framework constructor.
     */
    public function _init()
    {
        $this->_get_langs();
        if (DEBUG_MODE && $this->AUTO_FIND_VARS && $this->TRANSLATE_ENABLED && main()->is_db()) {
            $this->TR_ALL_VARS = from('locale_vars')->get_2d('value,id');
            $this->TR_ALL_VARS && ksort($this->TR_ALL_VARS);
        }
    }


    public function init_locale()
    {
        $langs = $this->LANGUAGES ?: $this->_get_langs();
        $lang = strtolower($this->_get_current_lang());
        $charset = strtolower($this->_get_current_charset());
        $country = strtoupper($this->_get_current_country());
        $lc_all = array_unique(array_filter([
            $country ? $lang . '_' . $country . '.' . $charset : '',
            $country ? $lang . '_' . $country . '.' . str_replace('-', '', $charset) : '',
            $country ? $lang . '_' . $country : '',
            $lang,
            $langs[$lang]['name'] ?? '',
            'en_US.utf-8',
            'en_US.utf8',
            'en_US',
            'en_GB.utf-8',
            'en_GB.utf8',
            'en_GB',
            'en',
        ]));
        if (DEBUG_MODE) {
            debug('locale::default', $this->_get_locale_details());
            debug('locale::lc_variants', ['LC_ALL' => $lc_all]);
        }
        $success = setlocale(LC_ALL, $lc_all);
        if (DEBUG_MODE) {
            debug('locale::current', $this->_get_locale_details());
            $sys_locale = '';
            exec('locale -a', $sys_locale);
            debug('locale::system', $sys_locale);
        }
        $this->_load_lang($lang);
        $this->_init_inline_editor();
    }


    public function _get_default_lang()
    {
        $langs = $this->LANGUAGES ?: $this->_get_langs();
        foreach ((array) $langs as $lang_name => $lang_obj) {
            if ($lang_obj['is_default'] == 1) {
                return $lang_name;
            }
        }
        return false;
    }

    public function _set_current_lang($lang = null)
    {
        if ( ! $lang) {
            return  $lang;
        }
        $this->_called['_get_current_lang'] = $lang;
        $this->CUR_LOCALE = $lang;
        return  $lang;
    }

    /**
     * Get current language.
     * @param mixed $force
     */
    public function _get_current_lang($force = false)
    {
        $langs = $this->LANGUAGES ?: $this->_get_langs();

        $FORCE_LOCALE = conf('FORCE_LOCALE');
        if ($FORCE_LOCALE && isset($langs[$FORCE_LOCALE])) {
            return $FORCE_LOCALE;
        }
        if (($this->_called[__FUNCTION__] ?? false) && ! $force) {
            return $this->_called[__FUNCTION__];
        }
        $l = []; // contains all possible variants
        $l['url'] = $_GET['language'] ?? $_GET['lang'] ?? null;
        $l['session'] = $this->ALLOW_SESSION_LANG ? ($_SESSION[MAIN_TYPE . '_lang'] ?? null) : '';
        $l['cookie'] = $_COOKIE[MAIN_TYPE . '_lang'] ?? null;
        $l['user'] = function () {
            $uid = main()->USER_ID;
            $lang = '';
            if ($uid && MAIN_TYPE_USER && main()->is_db()) {
                $u = from('user')->whereid($uid)->limit(1)->get();
                $u && $lang = $u['lang'] ?: $u['language'] ?: $u['locale'];
            }
            return $lang;
        };
        $l['http'] = function () {
            if ( ! function_exists('locale_accept_from_http')) {
                return false;
            }
            $locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
            $lang = substr($locale, 0, 2);
            return $lang;
        };
        $l['country'] = function () {
            // TODO
            return null;
        };
        $l['conf'] = conf('language');
        $l['admin'] = function () use ($langs) {
            foreach ((array) $langs as $a) {
                if ($a['is_default']) {
                    return $a['locale'];
                }
            }
        };
        $l['site'] = function () {
            // TODO
            return null;
        };
        $l['app'] = (defined('DEFAULT_LANG') && DEFAULT_LANG != '') ? DEFAULT_LANG : null;

        $priorities = &$this->CURRENT_LANG_PRIORITIES;

        $array_del_by_val = function (&$a, $del_val) {
            if (($k = array_search($del_val, $a)) !== false) {
                unset($a[$k]);
            }
        };
        ! $this->ALLOW_SESSION_LANG && $array_del_by_val($priorities, 'session');

        foreach ((array) $l as $k => $v) {
            if ( ! in_array($k, $priorities)) {
                unset($l[$k]);
                continue;
            }
            if (is_callable($v)) {
                $l[$k] = $v();
            }
        }
        $lang = '';
        $selected = '';
        foreach ($priorities as $priority) {
            if (isset($l[$priority])) {
                $lang = $l[$priority];
            }
            if ($lang && isset($langs[$lang])) {
                $selected = $priority;
                break;
            }
        }
        ! $lang && $this->CUR_LOCALE && $lang = $this->CUR_LOCALE;
        if ( ! isset($langs[$lang])) {
            $lang = 'en';
        }
        $lang = strtolower($lang);

        $lang && $this->CUR_LOCALE = $lang;
        $this->CUR_LOCALE && conf('language', $this->CUR_LOCALE);

        $this->_called[__FUNCTION__] = $this->CUR_LOCALE;

        debug('locale::lang_variants', $l);
        debug('locale::lang_priorities', $priorities);
        debug('locale::lang_selected', $selected);

        return $this->CUR_LOCALE;
    }


    public function _get_langs()
    {
        if ($this->LANGUAGES) {
            return $this->LANGUAGES;
        }
        $langs = main()->get_data('locale_langs');
        conf('languages', $langs);
        return $this->LANGUAGES = $langs;
    }


    public function _get_current_country()
    {
        $country = strtoupper(
            conf('country')
            ?: ($_SERVER['GEOIP_COUNTRY_CODE'] ?? false)
            ?: (in_array(strtolower($this->CUR_LOCALE), ['ru', 'uk']) ? 'UA' : '')
        );
        $this->CUR_COUNTRY = $country;
        conf('country', $this->CUR_COUNTRY);
        return $country;
    }


    public function _get_current_charset()
    {
        $langs = $this->LANGUAGES ?: $this->_get_langs();
        $charset = $langs[$this->CUR_LOCALE]['charset'] ?? '';
        if (MAIN_TYPE_ADMIN && $this->CUR_LOCALE == 'en') {
            $charset = 'utf-8';
        }
        $charset = strtolower($charset ?: 'utf-8');
        $this->CUR_CHARSET = $charset;
        conf('charset', $charset ?: $this->CUR_CHARSET);
        return $charset;
    }

    /**
     * Load language.
     * @param mixed $lang
     */
    public function _load_lang($lang = '')
    {
        if ( ! $this->TRANSLATE_ENABLED) {
            return false;
        }
        ! $lang && $lang = $this->_get_current_lang();
        if ( ! $lang || isset($this->_loaded[$lang])) {
            return false;
        }
        $this->_loaded[$lang] = false;

        $this->_load_lang_get_vars_from_db($lang);
        $this->_load_lang_get_vars_from_files($lang);
        $this->_load_lang_get_user_translate($lang, main()->USER_ID);

        if ($this->VARS_IGNORE_CASE) {
            $tmp = [];
            foreach ((array) $this->TR_VARS[$lang] as $name => $val) {
                $name = _strtolower($name);
                $tmp[$name] = $val;
            }
            $this->TR_VARS[$lang] = $tmp;
            unset($tmp);
        }
        $this->_loaded[$lang] = true;
    }

    /**
     * Default storage of translations.
     * @param mixed $lang
     */
    public function _load_lang_get_vars_from_db($lang)
    {
        $data = getset('locale_translate_' . $lang, function () use ($lang) {
            if ( ! main()->is_db()) {
                return [];
            }
            $sql = 'SELECT v.value AS source, t.value AS translation
				FROM ' . db('locale_vars') . ' AS v
				INNER JOIN ' . db('locale_translate') . ' AS t ON t.var_id = v.id
				WHERE t.locale = "' . _es($lang) . '"
					AND t.value != ""
					AND t.value != v.value';
            return db()->get_2d($sql) ?: [];
        });
        foreach ((array) $data as $k => $v) {
            $this->TR_VARS[$lang][$k] = $v;
        }
    }

    /**
     * Member-only translations.
     * @param mixed $lang
     * @param mixed $user_id
     */
    public function _load_lang_get_user_translate($lang, $user_id)
    {
        $user_id = (int) $user_id;
        if ( ! $this->ALLOW_USER_TRANSLATE || ! $user_id) {
            return false;
        }
        $data = getset('locale_user_translate_' . $lang . '_' . $user_id, function () use ($lang, $user_id) {
            if ( ! main()->is_db()) {
                return [];
            }
            $sql = 'SELECT name, translation
				FROM ' . db('locale_user_tr') . '
				WHERE user_id = ' . (int) $user_id . '
					AND locale = "' . _es($lang) . '"
					AND translation != ""
					AND translation != name';
            return db()->get_2d($sql) ?: [];
        });
        foreach ((array) $data as $k => $v) {
            $this->TR_VARS[$lang][$k] = $v;
        }
    }

    /**
     * Load language varas from files.
     * @param mixed $lang
     */
    public function _load_lang_get_vars_from_files($lang)
    {
        $files = [];
        // Auto-find shared language vars. They will be connected in order of file system
        // Names can be any, but better to include lang name into file name. Examples:
        // share/langs/ru/001_other.php
        // share/langs/ru/002_other2.php
        // share/langs/ru/other.php
        // share/langs/ru/ru_shop.php
        // plugins/shop/share/langs/ru/ru_user_register.php
        if ($this->ALLOW_SHARED_LANG_FILES) {
            $patterns = [
                'framework' => [
                    YF_PATH . 'langs/' . $lang . '/*.php',
                    YF_PATH . 'plugins/*/langs/' . $lang . '/*.php',
                    YF_PATH . 'share/langs/' . $lang . '/*.php',
                    YF_PATH . 'plugins/*/share/langs/' . $lang . '/*.php',
                ],
                'project' => [
                    PROJECT_PATH . 'langs/' . $lang . '/*.php',
                    PROJECT_PATH . 'plugins/*/langs/' . $lang . '/*.php',
                    PROJECT_PATH . 'share/langs/' . $lang . '/*.php',
                    PROJECT_PATH . 'plugins/*/share/langs/' . $lang . '/*.php',
                ],
                'app' => [
                    APP_PATH . 'langs/' . $lang . '/*.php',
                    APP_PATH . 'plugins/*/langs/' . $lang . '/*.php',
                    APP_PATH . 'share/langs/' . $lang . '/*.php',
                    APP_PATH . 'plugins/*/share/langs/' . $lang . '/*.php',
                ],
            ];
            if (SITE_PATH != PROJECT_PATH) {
                $patterns['site'] = [
                    SITE_PATH . 'langs/' . $lang . '/*.php',
                    SITE_PATH . 'plugins/*/langs/' . $lang . '/*.php',
                    SITE_PATH . 'share/langs/' . $lang . '/*.php',
                    SITE_PATH . 'plugins/*/share/langs/' . $lang . '/*.php',
                ];
            }
            foreach ($patterns as $paths) {
                foreach ($paths as $path) {
                    foreach ((array) glob($path) as $f) {
                        $files[basename($f)] = $f;
                    }
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
            $modules = (MAIN_TYPE_USER ? 'modules' : 'admin_modules');
            $patterns = [
                'framework' => [
                    YF_PATH . $modules . '/*/__locale__' . $lang . '*.php',
                    YF_PATH . 'plugins/*/' . $modules . '/*/__locale__' . $lang . '*.php',
                ],
                'project' => [
                    PROJECT_PATH . $modules . '/*/__locale__' . $lang . '*.php',
                    PROJECT_PATH . 'plugins/*/' . $modules . '/*/__locale__' . $lang . '*.php',
                ],
                'app' => [
                    APP_PATH . $modules . '/*/__locale__' . $lang . '*.php',
                    APP_PATH . 'plugins/*/' . $modules . '/*/__locale__' . $lang . '*.php',
                ],
            ];
            if (MAIN_TYPE_USER && SITE_PATH != PROJECT_PATH) {
                $patterns['site'] = [
                    SITE_PATH . $modules . '/*/__locale__' . $lang . '*.php',
                    SITE_PATH . 'plugins/*/' . $modules . '/*/__locale__' . $lang . '*.php',
                ];
            }
            foreach ($patterns as $paths) {
                foreach ($paths as $path) {
                    foreach ((array) glob($path) as $f) {
                        $files[basename($f)] = $f;
                    }
                }
            }
        }
        foreach ((array) $files as $path) {
            $data = include $path;
            foreach ((array) $data as $_source => $_trans) {
                $_source = _strtolower($_source);
                $this->TR_VARS[$lang][$_source] = $_trans;
            }
        }
    }

    /**
     * Translation of the given string.
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
     *			array('%url' => url('/user/register')));
     * @endcode
     *
     * We have ability to use custom prefix for vars with same names in different places with different translations
     * ex. for var "welcome" we can have several vars with prefixes  "::forum::welcome"
     * Prefix syntax:	 "::[a-z_-]::text to tranlate here"
     *
     * @param	$string string	Text to translate
     * @param	$args	array	Optional array of items to replace after translation
     * @param mixed $in
     * @param mixed $lang
     * @return string Translation result
     */
    public function translate_string($in, $args = 0, $lang = '')
    {
        if ( ! $in) {
            return $in;
        }
        DEBUG_MODE && $_start_time = microtime(true);
        $lang = (string) $lang;
        ! $lang && $lang = $this->_get_current_lang();
        ! isset($this->_loaded[$lang]) && $this->_load_lang($lang);
        if ( ! $lang || ! $this->_loaded[$lang]) {
            return $in;
        }
        if (is_array($args) && isset($args[''])) {
            unset($args['']);
        }
        if (is_array($in)) {
            $func = __FUNCTION__;
            foreach ((array) $in as $k => $v) {
                $in[$k] = $this->$func($v, $args, $lang);
            }
            return $in;
        }
        $in = trim($in);

        DEBUG_MODE && @$this->_calls[$in]++;

        if ($this->USE_TRANSLATE_CACHE && empty($args)) {
            $CACHE_NAME = $lang . '#____#' . $in;
            if (isset($this->_LOCALE_CACHE[$CACHE_NAME])) {
                return $this->_LOCALE_CACHE[$CACHE_NAME];
            }
        }
        $is_translated = false;
        $_source = $in;
        $out = $in;

        $prefix = '';
        $plen = 0;
        if (strpos($in, '::') === 0) {
            $prefix = substr($in, 0, strpos($in, '::', 2) + 2);
            $plen = strlen($prefix);
            $in = substr($in, $plen);
        }
        if ($this->TRANSLATE_ENABLED) {
            $t = &$this->TR_VARS[$lang];
            if ($this->VARS_IGNORE_CASE) {
                $first = $in;
                $in = _strtolower($in);
            }
            // Search for namespaced variable with current module name inside, even when prefix was not explicitely passed
            $module = $_GET['object'];
            if ( ! strlen($prefix) && $module && isset($t['::' . $module . '::' . $in])) {
                $prefix = '::' . $module . '::';
            }
            if (strlen($prefix) && isset($t[$prefix . $in])) {
                $out = $t[$prefix . $in];
                $is_translated = true;
            } elseif (isset($t[$in])) {
                $out = $t[$in];
                $is_translated = true;
            } elseif (($var_un_html = $this->_un_html_entities($in)) && isset($t[$var_un_html])) {
                $out = $t[$var_un_html];
                $is_translated = true;
            } else {
                $out = $first;
                if (DEBUG_MODE) {
                    ! isset($this->_NOT_TRANSLATED[$lang][$in]) && $this->_NOT_TRANSLATED[$lang][$in] = 0;
                    @$this->_NOT_TRANSLATED[$lang][$in]++;
                    if ($this->AUTO_FIND_VARS && ! isset($this->TR_ALL_VARS[$in])) {
                        $this->insert_var($in);
                    }
                }
            }
        }
        if ( ! empty($args) && is_array($args)) {
            $tmp_out = $out;
            $out = $this->_process_sub_patterns($out, $args);
            if ($out != $tmp_out) {
                $is_translated = true;
            }
            $out = strtr($out, $args);
        }
        if ( ! $is_translated) {
            $out = $first;
            if ( ! empty($args) && is_array($args)) {
                $out = strtr($out, $args);
            }
        } elseif ($is_translated) {
            if ($this->TRACK_FIRST_LETTER_CASE) {
                $input = $first;
                $f_s = _substr($input, 0, 1);
                $f_t = _substr($out, 0, 1);
                $f_s_lower = _strtolower($f_s) == $f_s;
                $f_t_lower = _strtolower($f_t) == $f_t;
                if ( ! $f_s_lower && $f_t_lower) {
                    $out = _strtoupper($f_t) . _substr($out, 1);
                }
            }
        }
        if (DEBUG_MODE) {
            if ($this->WRAP_VARS_FOR_INLINE_EDIT && false === strpos($out, 'class=localetr')) {
                $r = [
                    ' ' => '%20',
                    '=' => '&equals;',
                    '<' => '&lt;',
                    '>' => '&gt;',
                ];
                $svar = _prepare_html(str_replace(array_keys($r), array_values($r), $_source));
                $out = '<span class=localetr svar=' . $svar . '>' . $out . '</span>';
            }
            debug('i18n[]', [
                'name_orig' => $_source,
                'name' => $in,
                'out' => $out,
                'lang' => $lang,
                'args' => $args ?: '',
                'translated' => (int) $is_translated,
                'time' => round(microtime(true) - $_start_time, 5),
                'trace' => main()->trace_string(),
            ]);
        }
        if ($this->USE_TRANSLATE_CACHE && empty($args)) {
            $this->_LOCALE_CACHE[$CACHE_NAME] = $out;
        }
        return $out;
    }

    /**
     * Process sub-patterns for translate depending on number value.
     *
     * @sample:
     * {t(While searching %num folders found,%num=1001)}
     * В процессе поиска {Найдено %num папок|0:Папок не найдено|1:Найдена %num папка|2,3,4:Найдено %num папки|11-14:Найдено %num папок|Найдено %num папок}
     * @param mixed $text
     * @param mixed $args
     */
    public function _process_sub_patterns($text = '', $args = [])
    {
        if (false === strpos($text, '{') || ! is_array($args)) {
            return $text;
        }
        $new_replace = [];

        $pattern = '/\{([^\}\|]+?)\|([^\}]+?)\}/ims';
        preg_match_all($pattern, $text, $m);
        foreach ((array) $m[0] as $_id => $_source) {
            preg_match('/%[a-z\_]+/ims', $m[1][$_id], $m2);
            if ( ! $m2[0]) {
                continue;
            }
            $number = (int) ($args[$m2[0]]);
            $variants = explode('|', $m[2][$_id]);
            $common_variant = array_pop($variants);
            $pairs = [
                'other' => $common_variant,
            ];
            $exacts = [];
            foreach ((array) $variants as $_variant) {
                list($_quantity, $_sub_replace) = explode(':', $_variant);
                if ( ! strlen($_quantity)) {
                    continue;
                }
                // Exact value?
                if (strpos($_quantity, '#') === 0) {
                    $i = (int) (substr($_quantity, 1));
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
                        $pairs[(int) $i] = $_sub_replace;
                    }
                } elseif (is_numeric($_quantity)) {
                    $pairs[(int) $_quantity] = $_sub_replace;
                }
                // Unknown quantity, do nothing
            }
            $_last_digit = $number % 10;
            $_last_digit_100 = $number % 100;

            $replace_into = '';
            if ( ! empty($exacts) && isset($exacts[$number])) {
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
        if ( ! empty($new_replace)) {
            $text = str_replace(array_keys($new_replace), array_values($new_replace), $text);
            $text = strtr($text, $args);
        }
        return $text;
    }

    /**
     * Convert HTML entities to their text sysmbols.
     * @param mixed $text
     */
    public function _un_html_entities($text = '')
    {
        return str_replace(array_values(self::$HTML_ENTS), array_keys(self::$HTML_ENTS), $text);
    }

    /**
     * Insert missed var.
     * @param mixed $var_name
     */
    public function insert_var($var_name)
    {
        if (empty($var_name)) {
            return false;
        }
        return db()->insert_safe('locale_vars', ['value' => $var_name, 'location' => ''], ['ignore' => true]);
    }


    public function _init_inline_editor()
    {
        if ( ! DEBUG_MODE || ! isset($_SESSION['locale_vars_edit'])) {
            return false;
        }
        $is_enabled = (int) ((bool) $_SESSION['locale_vars_edit']);
        $this->TRACK_TRANSLATED = $is_enabled;
        main()->INLINE_EDIT_LOCALE = $is_enabled;

        if ($is_enabled && main()->is_common_page()) {
            $this->WRAP_VARS_FOR_INLINE_EDIT = true;
            asset('yf_js_inline_editor');
        }
    }


    public function _get_locale_details()
    {
        return [
            'LC_ALL' => setlocale(LC_ALL, 0), // for all of the below
            'LC_COLLATE' => setlocale(LC_COLLATE, 0), // for string comparison, see strcoll()
            'LC_CTYPE' => setlocale(LC_CTYPE, 0), // for character classification and conversion, for example strtoupper()
            'LC_MONETARY' => setlocale(LC_MONETARY, 0), // for localeconv()
            'LC_NUMERIC' => setlocale(LC_NUMERIC, 0), // for decimal separator (See also localeconv())
            'LC_TIME' => setlocale(LC_TIME, 0), // for date and time formatting with strftime()
            'LC_MESSAGES' => setlocale(LC_MESSAGES, 0), // for system responses (available if PHP was compiled with libintl)
        ];
    }

    /**
     * Lists available system locales (on *NIX).
     */
    public function _list_system_locales()
    {
        ob_start();
        system('locale -a');
        $str = ob_get_clean();
        return preg_split('/\\n/D', trim($str));
    }
}
