<?php

/**
 * Class where most common used functions are stored.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_common
{
    /** @var bool Store user-level errors */
    public $TRACK_USER_ERRORS = false;
    /** @var bool Display debug info for the empty page */
    public $EMPTY_PAGE_DEBUG_INFO = true;
    /** @var string Translit from encoding */
    public $TRANSLIT_FROM = 'cp1251';
    /** @var string Required for the compatibility with old main class */
    public $MEDIA_PATH = '';
    /** @var bool Used by propose url from name */
    public $URL_FORCE_DASHES = false;

    public $_current_theme = null;
    public $_get_vars_cache = null;
    public $USER_ERRORS = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        define('COMMON_LIB', 'classes/common/');
        $this->MEDIA_PATH = WEB_PATH;
        if (defined('MEDIA_PATH')) {
            $this->MEDIA_PATH = MEDIA_PATH;
        }
    }

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }


    public function show_ga()
    {
        if (DEBUG_MODE || MAIN_TYPE_ADMIN) {
            return false;
        }
        $body = [];
        // override this method and insert your google analytics tracking code here and all other analytics codes too
        return $body ? implode(PHP_EOL, $body) : '';
    }


    /**
     * Form2 chained wrapper.
     * @param mixed $replace
     * @param mixed $params
     */
    public function form2($replace = [], $params = [])
    {
        $form = clone _class('form2');
        return $form->chained_wrapper($replace, $params);
    }

    /**
     * Table2 chained wrapper.
     * @param mixed $data
     * @param mixed $params
     */
    public function table2($data = [], $params = [])
    {
        $table = clone _class('table2');
        return $table->chained_wrapper($data, $params);
    }

    /**
     * @param mixed $extra
     */
    public function css_class_body($extra = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        $main = main();
        $extra['css_framework'] = 'cssfw-' . strtolower(conf('css_framework'));
        $extra['bs_theme'] = 'bs-theme-' . strtolower($this->bs_current_theme());
        $extra['main_type'] = 'main-type-' . strtolower(MAIN_TYPE);
        $extra['is_common_page'] = $main->is_common_page() ? 'is-common-page' : '';
        $extra['is_ajax'] = $main->is_ajax() ? 'is-ajax' : '';
        $extra['is_post'] = $main->is_post() ? 'is-post' : '';
        $extra['is_dev'] = $main->is_dev() ? 'is-dev' : '';
        $extra['is_debug'] = $main->is_debug() ? 'is-debug' : '';
        $extra['is_https'] = $main->is_https() ? 'is-https' : '';
        $extra['is_spider'] = $main->is_spider() ? 'is-spider' : '';
        $extra['is_redirect'] = $main->is_redirect() ? 'is-redirect' : '';
        $extra['is_unit_test'] = $main->is_unit_test() ? 'is-unit-test' : '';
        $extra['is_logged_in'] = $main->is_logged_in() ? 'is-logged-in' : 'is-guest';
        $extra['is_banned'] = $main->is_banned() ? 'is-banned' : '';
        $extra['group_id'] = ($group = MAIN_TYPE_ADMIN ? $main->ADMIN_GROUP : $main->USER_GROUP) ? 'groupid-' . strtolower($group) : '';
        $extra['site_id'] = ($site_id = $main->SITE_ID) ? 'siteid-' . strtolower($site_id) : '';
        $extra['get_object'] = 'get-object-' . strtolower($_GET['object']);
        $extra['get_action'] = 'get-action-' . strtolower($_GET['action']);
        $extra['get_id'] = $_GET['id'] ? 'get-id-' . strtolower($_GET['id']) : '';
        $extra['language'] = 'lang-' . strtolower(conf('language') ?: 'en');
        $extra['country'] = $_SERVER['GEOIP_COUNTRY_CODE'] ? 'country-' . strtolower($_SERVER['GEOIP_COUNTRY_CODE']) : '';
        $extra['currency'] = ($currency = conf('currency')) ? 'currency-' . strtolower($currency) : '';
        return implode(' ', array_filter($extra));
    }

    /**
     * @param mixed $css_fw
     */
    public function bs_get_avail_themes($css_fw = '')
    {
        if ( ! $css_fw) {
            $css_fw = conf('css_framework');
        }
        if ( ! $css_fw) {
            $css_fw = 'bs3';
        }
        $cache_dir = YF_PATH . '.dev/assets_cache/bootswatch/';
        if (in_array($css_fw, ['bs2', 'bs3', 'bs4'])) {
            $themes = explode(PHP_EOL, trim(file_get_contents($cache_dir . '/themes_tw' . $css_fw . '.txt')));
        }
        $themes[] = 'flatui';
        $themes[] = 'material_design';
        $themes[] = 'todc_bootstrap';
        $themes[] = 'bootstrap_theme';
        $themes[] = 'bootstrap';

        $blacklist = [
            'flatui',
            'journal',
            'lumen',
            'material_design',
            'todc_bootstrap',
            'paper',
            'readable',
            'sandstone',
            'simplex',
            'superhero',
        ];
        foreach ($themes as $k => $name) {
            if (in_array($name, $blacklist)) {
                unset($themes[$k]);
            }
        }
        asort($themes);
        return $themes;
    }

    /**
     * @param mixed $main_type
     * @param mixed $force
     */
    public function bs_current_theme($main_type = '', $force = false)
    {
        if ( ! $main_type) {
            $main_type = MAIN_TYPE;
        }
        if ($main_type === 'user') {
            $theme = 'spacelab'; // Default
        } elseif ($main_type === 'admin') {
            $theme = 'slate'; // Default
        }
        if (isset($this->_current_theme) && ! $force) {
            return $this->_current_theme;
        }
        $conf_theme = conf('DEF_BOOTSTRAP_THEME_' . strtoupper($main_type)) ?: conf('DEF_BOOTSTRAP_THEME');
        if ($conf_theme) {
            $theme = $conf_theme;
        }
        $allow_override = conf('bs_theme_allow_override_for_' . $main_type);
        $avail_themes = $this->bs_get_avail_themes();
        if ($_GET['yf_theme'] && in_array($_GET['yf_theme'], $avail_themes) && $allow_override) {
            $theme = $_GET['yf_theme'];
            setcookie('yf_theme', $theme, 0, '/');
            unset($_GET['yf_theme']);
            js_redirect('/@object/@action/@id/@page');
        } elseif ($_COOKIE['yf_theme'] && in_array($_COOKIE['yf_theme'], $avail_themes)) {
            if ( ! $force || $allow_override) {
                $theme = $_COOKIE['yf_theme'];
            }
        }
        $this->_current_theme = $theme;
        return $theme;
    }


    public function bs_theme_html()
    {
        $theme = $this->bs_current_theme();
        return tpl()->parse('bs_theme_html', ['cur_theme' => $this->bs_current_theme()]);
    }


    public function bs_theme_changer()
    {
        return tpl()->parse('bs_theme_changer', [
            'cur_theme' => $this->bs_current_theme(),
            'themes' => $this->bs_get_avail_themes(),
        ]);
    }

    /**
     * Secondary database connection.
     */
    public function connect_db2()
    {
        if ( ! defined('DB_HOST2')) {
            return false;
        }
        global $db2;
        if ( ! is_object($db2)) {
            $db_class_name = main()->load_class_file('db', 'classes/');
            if ($db_class_name && class_exists($db_class_name)) {
                $db2 = new $db_class_name('mysql', 1, constant('DB_PREFIX2'));
                $db2->connect(
                    constant('DB_HOST2'),
                    constant('DB_USER2'),
                    constant('DB_PSWD2'),
                    constant('DB_NAME2'),
                    true,
                    defined('DB_SSL2') ? constant('DB_SSL2') : false,
                    defined('DB_PORT2') ? constant('DB_PORT2') : '',
                    defined('DB_SOCKET2') ? constant('DB_SOCKET2') : '',
                    defined('DB_CHARSET2') ? constant('DB_CHARSET2') : ''
                );
            }
        }
        return $db2;
    }

    /**
     * Secondary database connection.
     */
    public function db2_connect()
    {
        return $this->connect_db2();
    }

    /**
     * This function generate dividing table contents per pages.
     * @param mixed $sql
     * @param mixed $url_path
     * @param mixed $render_type
     * @param mixed $records_on_page
     * @param mixed $num_records
     * @param mixed $tpls_path
     * @param mixed $add_get_vars
     * @param mixed $extra
     */
    public function divide_pages($sql = '', $url_path = '', $render_type = '', $records_on_page = 0, $num_records = 0, $tpls_path = '', $add_get_vars = 1, $extra = [])
    {
        if (is_array($sql)) {
            $sql_is_array = true;
        } elseif (is_callable($sql)) {
            $sql_is_callable = true;
        } elseif (is_object($sql)) {
            if ($sql instanceof yf_db_query_builder_driver) {
                $sql_is_query_builder = true;
            } else {
                $sql_is_object = true;
            }
        }
        if ($sql_is_query_builder) {
            $sql = $sql->sql();
        } elseif ($sql_is_object) {
            $sql = obj2arr($sql);
        } elseif ($sql_is_callable) {
            $sql = (array) $sql(func_get_args());
        }
        // Override default method for input array
        $method = is_array($sql) ? 'go_with_array' : 'go';
        return _class('divide_pages', 'classes/common/')->$method($sql, $url_path, $render_type, $records_on_page, $num_records, $tpls_path, $add_get_vars, $extra);
    }

    /**
     * Send emails with attachments with DEBUG ability.
     * @param mixed $email_from
     * @param mixed $name_from
     * @param mixed $email_to
     * @param mixed $name_to
     * @param mixed $subject
     * @param mixed $text
     * @param mixed $html
     * @param mixed $attaches
     * @param mixed $charset
     * @param mixed $old_param1
     * @param mixed $force_mta_opts
     * @param null|mixed $priority
     * @param mixed $smtp
     */
    public function send_mail($email_from, $name_from = '', $email_to = '', $name_to = '', $subject = '', $text = '', $html = '', $attaches = [], $charset = '', $old_param1 = '', $force_mta_opts = [], $priority = null, $smtp = [])
    {
        if (is_array($email_from)) {
            $params = $email_from;
            $params['email_from'] = $params['from_mail'];
        }
        if ( ! is_array($params)) {
            $params = [];
        }
        $params['email_from'] = $params['from_mail'] ?: $params['email_from'] ?: $email_from;
        $params['name_from'] = $params['from_name'] ?: $params['name_from'] ?: $name_from;
        $params['email_to'] = $params['to_mail'] ?: $params['email_to'] ?: $email_to;
        $params['name_to'] = $params['to_name'] ?: $params['name_to'] ?: $name_to;
        $params['subject'] = $params['subj'] ?: $params['subject'] ?: $subject;
        $params['text'] = $params['text'] ?: $text;
        $params['html'] = $params['html'] ?: $html;
        $params['attaches'] = $params['attach'] ?: $params['attaches'] ?: $attaches;
        $params['charset'] = $params['charset'] ?: $charset;
        $params['mta_params'] = $params['force_mta_opts'] ?: $params['mta_params'] ?: $force_mta_opts;
        $params['priority'] = $params['priority'] ?: $priority ?: 3;
        $params['smtp'] = $params['smtp'] ?: $smtp;
        return _class('send_mail')->send($params);
    }

    /**
     * Quick send mail (From admin info).
     * @param mixed $email_to
     * @param mixed $subject
     * @param mixed $html
     */
    public function quick_send_mail($email_to, $subject, $html)
    {
        return $this->send_mail(SITE_ADMIN_EMAIL, defined('SITE_ADMIN_NAME') ? SITE_ADMIN_NAME : 'Site admin', $email_to, '', $subject, strip_tags($html), $html);
    }

    /**
     * Quick email notification to the admin (from the system).
     * @param mixed $subject
     * @param mixed $html
     */
    public function send_notify_mail_to_admin($subject, $html)
    {
        return $this->send_mail(SITE_ADMIN_EMAIL, constant('SITE_NAME') . ' system notification', SITE_ADMIN_EMAIL, '', $subject, strip_tags($html), $html);
    }

    /**
     * This function generate select box with tree hierarhy inside.
     * @param mixed $name
     * @param mixed $values
     * @param mixed $selected
     * @param mixed $show_text
     * @param mixed $type
     * @param mixed $add_str
     * @param mixed $translate
     * @param mixed $level
     */
    public function select_box($name, $values = [], $selected = '', $show_text = true, $type = 2, $add_str = '', $translate = 0, $level = 0)
    {
        return _class('html')->select_box($name, $values, $selected, $show_text, $type, $add_str, $translate, $level);
    }

    /**
     * Generate multi-select box.
     * @param mixed $name
     * @param mixed $values
     * @param mixed $selected
     * @param mixed $show_text
     * @param mixed $type
     * @param mixed $add_str
     * @param mixed $translate
     * @param mixed $level
     * @param mixed $disabled
     */
    public function multi_select($name, $values = [], $selected = '', $show_text = false, $type = 2, $add_str = '', $translate = 0, $level = 0, $disabled = false)
    {
        return _class('html')->multi_select($name, $values, $selected, $show_text, $type, $add_str, $translate, $level, $disabled);
    }

    /**
     * Alias for the multi_select.
     * @param mixed $name
     * @param mixed $values
     * @param mixed $selected
     * @param mixed $show_text
     * @param mixed $type
     * @param mixed $add_str
     * @param mixed $translate
     * @param mixed $level
     * @param mixed $disabled
     */
    public function multi_select_box($name, $values = [], $selected = '', $show_text = false, $type = 2, $add_str = '', $translate = 0, $level = 0, $disabled = false)
    {
        return $this->multi_select($name, $values, $selected, $show_text, $type, $add_str, $translate, $level, $disabled);
    }

    /**
     * Processing radio buttons.
     * @param mixed $box_name
     * @param mixed $values
     * @param mixed $selected
     * @param mixed $horizontal
     * @param mixed $type
     * @param mixed $add_str
     * @param mixed $translate
     */
    public function radio_box($box_name, $values = [], $selected = '', $horizontal = true, $type = 2, $add_str = '', $translate = 0)
    {
        return _class('html')->radio_box($box_name, $values, $selected, $horizontal, $type, $add_str, $translate);
    }

    /**
     * Simple check box.
     * @param mixed $box_name
     * @param mixed $values
     * @param mixed $selected
     * @param mixed $add_str
     */
    public function check_box($box_name, $values = [], $selected = '', $add_str = '')
    {
        return _class('html')->check_box($box_name, $values, $selected, $add_str);
    }

    /**
     * Processing many checkboxes at one time.
     * @param mixed $box_name
     * @param mixed $values
     * @param mixed $selected
     * @param mixed $horizontal
     * @param mixed $type
     * @param mixed $add_str
     * @param mixed $translate
     * @param mixed $name_as_array
     */
    public function multi_check_box($box_name, $values = [], $selected = [], $horizontal = true, $type = 2, $add_str = '', $translate = 0, $name_as_array = false)
    {
        return _class('html')->multi_check_box($box_name, $values, $selected, $horizontal, $type, $add_str, $translate, $name_as_array);
    }

    /**
     * @param mixed $selected_date
     * @param mixed $years
     * @param mixed $name_postfix
     * @param mixed $add_str
     * @param mixed $order
     * @param mixed $show_text
     * @param mixed $translate
     */
    public function date_box($selected_date = '', $years = '', $name_postfix = '', $add_str = '', $order = 'ymd', $show_text = 1, $translate = 1)
    {
        return _class('html')->date_box($selected_date, $years, $name_postfix, $add_str, $order, $show_text, $translate);
    }

    /**
     * @param mixed $selected_time
     * @param mixed $name_postfix
     * @param mixed $add_str
     * @param mixed $show_text
     * @param mixed $translate
     */
    public function time_box($selected_time = '', $name_postfix = '', $add_str = '', $show_text = 1, $translate = 1)
    {
        return _class('html')->time_box($selected_time, $name_postfix, $add_str, $show_text, $translate);
    }

    /**
     * @param mixed $name
     * @param mixed $selected
     * @param mixed $range
     * @param mixed $add_str
     * @param mixed $show_what
     * @param mixed $show_text
     * @param mixed $translate
     */
    public function date_box2($name = '', $selected = '', $range = '', $add_str = '', $show_what = 'ymd', $show_text = 1, $translate = 1)
    {
        return _class('html')->date_box2($name, $selected, $range, $add_str, $show_what, $show_text, $translate);
    }

    /**
     * @param mixed $name
     * @param mixed $selected
     * @param mixed $range
     * @param mixed $add_str
     * @param mixed $show_what
     * @param mixed $show_text
     * @param mixed $translate
     */
    public function datetime_box2($name = '', $selected = '', $range = '', $add_str = '', $show_what = 'ymdhis', $show_text = 1, $translate = 1)
    {
        return _class('html')->datetime_box2($name, $selected, $range, $add_str, $show_what, $show_text, $translate);
    }

    /**
     * @param mixed $name
     * @param mixed $selected
     * @param mixed $add_str
     * @param mixed $show_text
     * @param mixed $translate
     */
    public function time_box2($name = '', $selected = '', $add_str = '', $show_text = 1, $translate = 1)
    {
        return _class('html')->time_box2($name, $selected, $add_str, $show_text, $translate);
    }

    /**
     * Format file size.
     * @param mixed $fs
     * @param mixed $digits_count
     */
    public function format_file_size($fs = 0, $digits_count = 0)
    {
        if ($digits_count == 0) {
            return ($fs < 1024 * 1024) ? round($fs / 1024, 3) . ' Kb' : (($fs < 1024 * 1024 * 1024) ? round($fs / (1024 * 1024), 3) . ' Mb' : round($fs / (1024 * 1024 * 1024), 3) . ' Gb');
        }
        return ($fs < 1048576) ? round($fs / 1024, $digits_count - strlen(round($fs / 1024, 0))) . ' Kb' : (($fs < 1073741824) ? round($fs / 1048576, $digits_count - strlen(round($fs / 1048576, 0))) . ' Mb' : round($fs / 1073741824, $digits_count - strlen(round($fs / 1073741824, 0))) . ' Gb');
    }

    /**
     * Format time.
     * @param mixed $timestamp
     * @param mixed $accuracy
     */
    public function format_time($timestamp, $accuracy = 'second')
    {
        $timestamp = (int) $timestamp;
        if ($timestamp == 0) {
            return 0;
        }
        $out = '';
        $periods = ['year', 'month', 'day', 'hour', 'minute', 'second'];
        list($length_accur) = array_keys($periods, $accuracy);
        $lengths = [31536000, 2592000, 86400, 3600, 60, 1];
        for ($i = 0; $timestamp > $lengths[$length_accur]; $i++) {
            $value = (int) ($timestamp / $lengths[$i]);
            if ($value != 0) {
                $out .= $value . ' ' . $periods[$i] . ($value > 1 ? 's ' : ' ');
            }
            $r = fmod($timestamp, $lengths[$i]);
            if ($r == 0) {
                break;
            }
            $timestamp = $r;
        }
        return $out;
    }

    /**
     * @param mixed $seconds
     * @param mixed $delimiter
     * @param mixed $need_return
     * @param mixed $only_text
     * @param mixed $need_closing_tag
     */
    public function _get_time_diff_human($seconds, $delimiter = ' ', $need_return = false, $only_text = false, $need_closing_tag = false)
    {
        $d = [];
        $tr = [
            'years' => ['лет', 'год', 'года'],
            'months' => ['месяцев', 'месяц', 'месяца'],
            'days' => ['дней', 'день', 'дня'],
            'hours' => ['часов', 'час', 'часа'],
            'minutes' => ['минут', 'минута', 'минуты'],
            'seconds' => ['секунд', 'секунда', 'секунды'],
        ];
        if ($need_return && is_array($need_return)) {
            $check_format = true;
        }
        if ($check_format && in_array('years', $need_return)) {
            $d['years'] = floor($seconds / (3600 * 24 * 365));
            $seconds -= $d['years'] * (3600 * 24 * 365);
        }
        if ($check_format && in_array('months', $need_return)) {
            $d['months'] = floor($seconds / (3600 * 24 * 365 / 12));
            $seconds -= $d['months'] * (3600 * 24 * 365 / 12);
        }
        if ($check_format && in_array('days', $need_return)) {
            $d['days'] = floor($seconds / (3600 * 24));
            $seconds -= $d['days'] * (3600 * 24);
        }
        if ($check_format && in_array('hours', $need_return)) {
            $d['hours'] = floor($seconds / 3600);
            $seconds -= $d['hours'] * 3600;
        }
        if ($check_format && in_array('minutes', $need_return)) {
            $d['minutes'] = floor($seconds / 60);
            $seconds -= $d['minutes'] * 60;
        }
        if ($check_format && in_array('seconds', $need_return)) {
            $d['seconds'] = $seconds;
        }
        $out = [];
        foreach ($d as $name => $val) {
            if ( ! $val) {
                continue;
            }
            $last1 = substr($val, -1);
            $last2 = substr($val, -2);
            if ($last1 == 0 || ($last1 >= 5 && $last1 <= 9) || ($last2 >= 10 && $last2 <= 20)) {
                $str = $tr[$name][0];
            } elseif ($last1 === '1') {
                $str = $tr[$name][1];
            } else {
                $str = $tr[$name][2];
            }
            $out[] = $only_text ? $str : $val . ' ' . $str;
        }
        $open_tag = '';
        $close_tag = '';
        if ($need_closing_tag) {
            $open_tag = '<' . $delimiter . '>';
            $close_tag = '</' . $delimiter . '>';
            $delimiter = $close_tag . $open_tag;
        }
        return $open_tag . implode($delimiter, $out) . $close_tag;
    }

    /**
     * Return file size formatted.
     * @param mixed $file_name
     * @param mixed $digits_count
     */
    public function get_formatted_file_size($file_name = '', $digits_count = 0)
    {
        return $this->format_file_size(file_exists($file_name) ? filesize($file_name) : 0, $digits_count);
    }

    /**
     * Return file extension.
     * @param mixed $file_path
     */
    public function get_file_ext($file_path = '')
    {
        return pathinfo($file_path, PATHINFO_EXTENSION);
    }

    /**
     * Simple random password creator with specified length (max 32 symbols) //.
     * @param mixed $Length
     */
    public function rand_name($Length = 8)
    {
        return substr(md5(microtime(true) . rand()), 0, $Length);
    }

    /**
     * Email verifying function.
     * @param mixed $email
     * @param mixed $check_mx
     * @param mixed $check_by_smtp
     * @param mixed $check_blacklists
     */
    public function email_verify($email = '', $check_mx = false, $check_by_smtp = false, $check_blacklists = false)
    {
        return _class('validate')->_email_verify($email, $check_mx, $check_by_smtp, $check_blacklists);
    }

    /**
     * Verify url.
     * @param mixed $url
     * @param mixed $absolute
     */
    public function url_verify($url = '', $absolute = false)
    {
        return _class('validate')->_url_verify($url, $absolute);
    }

    /**
     * Verify url using remote call.
     * @param mixed $url
     */
    public function _validate_url_by_http($url)
    {
        return _class('validate')->_validate_url_by_http($url);
    }

    /**
     * Add variables that came from $_GET array.
     * @param mixed $add_skip
     */
    public function add_get_vars($add_skip = [])
    {
        // Cache it
        if (isset($this->_get_vars_cache)) {
            return $this->_get_vars_cache;
        }
        $string = '';
        $skip = array_merge(
            ['task', 'object', 'action', 'section', 'id', 'post_id', 'language'],
            (array) $add_skip
        );
        foreach ((array) $_GET as $name => $value) {
            // Skip some vars
            if (in_array($name, $skip)) {
                continue;
            }
            // Process array
            if (is_array($value)) {
                foreach ((array) $value as $k2 => $v2) {
                    if (is_array($v2)) {
                        continue;
                    }
                    $string .= '&' . urlencode($name) . '[' . urlencode($k2) . ']=' . urlencode($v2);
                }
            } else {
                $string .= '&' . urlencode($name) . '=' . urlencode($value);
            }
        }
        $this->_get_vars_cache = $string;
        return $string;
    }

    /**
     * Make thumbnail using best available method.
     * @param mixed $source_file_path
     * @param mixed $dest_file_path
     * @param mixed $LIMIT_X
     * @param mixed $LIMIT_Y
     * @param mixed $watermark_path
     * @param mixed $ext
     */
    public function make_thumb($source_file_path = '', $dest_file_path = '', $LIMIT_X = -1, $LIMIT_Y = -1, $watermark_path = '', $ext = '')
    {
        return _class('make_thumb', 'classes/common/')->go($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y, $watermark_path, $ext);
    }

    /**
     * Do upload image.
     * @param mixed $new_file_path
     * @param mixed $name_in_form
     * @param mixed $max_image_size
     * @param mixed $is_local
     */
    public function upload_image($new_file_path, $name_in_form = 'image', $max_image_size = 0, $is_local = false)
    {
        return _class('upload_image', 'classes/common/')->go($new_file_path, $name_in_form, $max_image_size, $is_local);
    }

    /**
     * Do multi upload image.
     * @param mixed $new_file_path
     * @param mixed $k
     * @param mixed $name_in_form
     * @param mixed $max_image_size
     * @param mixed $is_local
     */
    public function multi_upload_image($new_file_path, $k, $name_in_form = 'image', $max_image_size = 0, $is_local = false)
    {
        return _class('multi_upload_image', 'classes/common/')->go($new_file_path, $k, $name_in_form, $max_image_size, $is_local);
    }

    /**
     * Do crop image.
     * @param mixed $source_file_path
     * @param mixed $dest_file_path
     * @param mixed $LIMIT_X
     * @param mixed $LIMIT_Y
     * @param mixed $pos_left
     * @param mixed $pos_top
     */
    public function crop_image($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y, $pos_left, $pos_top)
    {
        return _class('image_manip', 'classes/common/')->crop($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y, $pos_left, $pos_top);
    }

    /**
     * Do upload archive file (zip, rar, tar accepted).
     * @param mixed $new_file_path
     * @param mixed $name_in_form
     */
    public function upload_archive($new_file_path, $name_in_form = 'archive')
    {
        return _class('upload_archive', 'classes/common/')->go($new_file_path, $name_in_form);
    }

    /**
     * Create simple table with debug info.
     */
    public function show_debug_info()
    {
        return _class('debug')->go();
    }

    /**
     * Show execution time info.
     */
    public function _show_execution_time()
    {
        main()->_time_end = $this->_format_time_value(microtime(true) - main()->_time_start);
        return '<div align="center" id="debug_exec_time">' . _ucfirst(t('page_generated_in')) . ' ' . main()->_time_end . ' ' . t('seconds') . ', &nbsp;&nbsp;' . t('number_of_queries') . ' = ' . (int) (db()->NUM_QUERIES) . '</div>' . PHP_EOL;
    }

    /**
     * Get user IP address.
     * @param mixed $check_type
     */
    public function get_ip($check_type = 'force')
    {
        return _class('client_utils', 'classes/common/')->_get_ip($check_type);
    }

    /**
     * Show print version of the given page.
     * @param mixed $text
     */
    public function print_page($text = '')
    {
        main()->no_graphics(true);
        return print tpl()->parse('system/common/print_page', [
            'text' => $text,
            'path_to_tpls' => WEB_PATH . tpl()->TPL_PATH,
        ]);
    }

    /**
     * Send given text to a desired email address.
     * @param mixed $text
     */
    public function email_page($text = '')
    {
        return _class('email_page', 'classes/common/')->go($text);
    }

    /**
     * Create PDF 'on the fly' from the given content.
     * @param mixed $html
     * @param mixed $file
     * @param mixed $type
     */
    public function pdf_page($html = '', $file = '', $type = 'I')
    {
        return _class('pdf_page', 'classes/common/')->go($html, $file, $type);
    }

    /**
     * Create Alphabet search criteria.Make alphabet html and query limit for selected chars.
     * @param mixed $url
     * @param mixed $get_var_name
     * @param mixed $q_var
     */
    public function make_alphabet($url, &$chars, $get_var_name = 'id', $q_var = 'id')
    {
        return _class('make_alphabet', 'classes/common/')->go($url, $chars, $get_var_name, $q_var);
    }

    /**
     * Alias.
     */
    public function log_exec()
    {
        return _class('logs')->log_exec();
    }

    /**
     * Create RSS 'on the fly' from the given content.
     * @param mixed $data
     * @param mixed $params
     */
    public function rss_page($data = '', $params = [])
    {
        return _class('rss_data', 'classes/common/')->show_rss_page($data, $params);
    }

    /**
     * Get data from RSS feeds and return it as array.
     * @param mixed $params
     */
    public function fetch_rss($params = [])
    {
        return _class('rss_data', 'classes/common/')->fetch_data($params);
    }

    /**
     * Show empty page (useful for popup windows, etc).
     * @param mixed $text
     * @param mixed $params
     */
    public function show_empty_page($text = '', $params = [])
    {
        main()->no_graphics(true);
        $output = tpl()->parse('empty_page', [
            'text' => $text,
            'title' => $params['title'],
            'close_button' => (int) ((bool) $params['close_button']),
            'full_width' => (int) ((bool) $params['full_width']),
        ]);
        $output .= tpl()->_get_quick_page_info();
        $output = tpl()->_apply_output_filters($output);
        main()->_send_main_headers(strlen($output));
        echo $output;
    }

    /**
     * Try to add activity points.
     * @param mixed $user_id
     * @param mixed $task_name
     * @param mixed $action_value
     * @param mixed $record_id
     */
    public function _add_activity_points($user_id = 0, $task_name = '', $action_value = '', $record_id = 0)
    {
        return module_safe('activity')->_auto_add_points($user_id, $task_name, $action_value, $record_id);
    }

    /**
     * Try to remove activity points.
     * @param mixed $user_id
     * @param mixed $task_name
     * @param mixed $record_id
     */
    public function _remove_activity_points($user_id = 0, $task_name = '', $record_id = 0)
    {
        return module_safe('activity')->_auto_remove_points($user_id, $task_name, $record_id);
    }

    /**
     * Upload given file to remote server from this server.
     * @param mixed $path_tmp
     * @param mixed $new_dir
     * @param mixed $new_file
     */
    public function upload_file($path_tmp = '', $new_dir = '', $new_file = '')
    {
        return _class('remote_files', 'classes/common/')->do_upload($path_tmp, $new_dir, $new_file);
    }

    /**
     * Delete uploaded file.
     * @param mixed $path_to
     */
    public function delete_uploaded_file($path_to = '')
    {
        return _class('remote_files', 'classes/common/')->do_delete($path_to);
    }

    /**
     * Remote file last-modification time.
     * @param mixed $path_to
     */
    public function filemtime_remote($path_to = '')
    {
        return _class('remote_files', 'classes/common/')->filemtime_remote($path_to);
    }

    /**
     * Check if file exists.
     * @param mixed $path_to
     */
    public function file_is_exists($path_to = '')
    {
        return _class('remote_files', 'classes/common/')->file_is_exists($path_to);
    }

    /**
     * Get remote file using CURL extension.
     * @param mixed $page_url
     */
    public function remote_file_size($page_url = '')
    {
        return _class('remote_files', 'classes/common/')->remote_file_size($page_url);
    }

    /**
     * Get remote file using CURL extension.
     * @param mixed $page_url
     * @param mixed $cache_ttl
     * @param mixed $options
     */
    public function get_remote_page($page_url = '', $cache_ttl = -1, $options = [], &$requests_info = [])
    {
        return _class('remote_files', 'classes/common/')->get_remote_page($page_url, $cache_ttl, $options, $requests_info);
    }

    /**
     * Get several remote files at one time.
     * @param mixed $page_urls
     * @param mixed $options
     */
    public function multi_request($page_urls = [], $options = [], &$requests_info = [])
    {
        return _class('remote_files', 'classes/common/')->_multi_request($page_urls, $options, $requests_info);
    }

    /**
     * 'Safe' multi_request, which splits inpu array into smaller chunks to prevent server breaking.
     * @param mixed $page_urls
     * @param mixed $options
     * @param mixed $chunk_size
     */
    public function multi_request_safe($page_urls = [], $options = [], $chunk_size = 50)
    {
        return _class('remote_files', 'classes/common/')->multi_request_safe($page_urls, $options, $chunk_size);
    }

    /**
     * Get several remote files sizes.
     * @param mixed $page_urls
     * @param mixed $options
     * @param mixed $max_threads
     */
    public function multi_file_size($page_urls, $options = [], $max_threads = 50)
    {
        return _class('remote_files', 'classes/common/')->multi_file_size($page_urls, $options, $max_threads);
    }

    /**
     * Check if user is banned.
     * @param mixed $info
     * @param mixed $user_info
     */
    public function check_user_ban($info = [], $user_info = [])
    {
        return _class('user_ban', 'classes/common/')->_check($info, $user_info);
    }

    /**
     * Check if user is banned.
     */
    public function get_browser_info()
    {
        return _class('client_utils', 'classes/common/')->_get_browser_info();
    }

    /**
     * Format execution time.
     * @param mixed $value
     * @param mixed $round_to
     */
    public function _format_time_value($value = '', $round_to = 4)
    {
        if (empty($value)) {
            $value = 0.0001;
        }
        return substr(round((float) $value, $round_to), 0, $round_to + 2);
    }

    /**
     * Revert html special chars.
     * @param mixed $text
     */
    public function unhtmlspecialchars($text = '')
    {
        $trans_tbl = [
            '"' => '&quot;',
            '&' => '&amp;',
            '\'' => '&#039;',
            '<' => '&lt;',
            '>' => '&gt;',
        ];
        $trans_tbl = array_flip($trans_tbl);
        return strtr($text, $trans_tbl);
    }

    /**
     * Do redirect user to the specified location.
     * @param mixed $location
     * @param mixed $rewrite
     * @param mixed $redirect_type
     * @param mixed $text
     * @param mixed $ttl
     */
    public function redirect($location, $rewrite = true, $redirect_type = 'js', $text = '', $ttl = 3)
    {
        return _class('redirect')->_go($location, $rewrite, $redirect_type, $text, $ttl);
    }

    /**
     * Encode given address to prevent spam-bots harvesting.
     * @param mixed $addr
     * @param mixed $as_html_link
     */
    public function encode_email($addr = '', $as_html_link = false)
    {
        return _class('utils')->encode_email($addr, $as_html_link);
    }

    /**
     * Display message if server is overloaded.
     *
     * @param mixed $msg
     */
    public function server_is_busy($msg = '')
    {
        $replace = [
            'msg' => $msg,
        ];
        return tpl()->parse('system/server_is_busy', $replace);
    }

    /**
     * Get file using HTTP request (grabbed from drupal 5.1).
     * @param mixed $url
     * @param mixed $headers
     * @param mixed $method
     * @param null|mixed $data
     * @param mixed $retry
     */
    public function http_request($url, $headers = [], $method = 'GET', $data = null, $retry = 3)
    {
        return _class('remote_files', 'classes/common/')->http_request($url, $headers, $method, $data, $retry);
    }

    /**
     * Get file using HTTP request (grabbed from drupal 5.1).
     * @param mixed $url
     * @param mixed $server
     */
    public function get_whois_info($url, $server = '')
    {
        return _class('other_common', 'classes/common/')->get_whois_info($url, $server);
    }

    /**
     * Get geo info by IP from db.
     * @param mixed $cur_ip
     */
    public function _get_geo_data_from_db($cur_ip = '')
    {
        return _class('other_common', 'classes/common/')->_get_geo_data_from_db($cur_ip);
    }

    /**
     * Get geo info by IP from db.
     * @param mixed $cur_ip
     */
    public function _is_ip_to_skip($cur_ip = '')
    {
        return _class('other_common', 'classes/common/')->_is_ip_to_skip($cur_ip);
    }

    /**
     * Check if given IP matches given CIDR.
     * @param mixed $iptocheck
     * @param mixed $CIDR
     */
    public function _is_ip_in_cidr($iptocheck, $CIDR)
    {
        return _class('other_common', 'classes/common/')->_is_ip_in_cidr($iptocheck, $CIDR);
    }

    /**
     * Check if given IP is banned.
     * @param mixed $CUR_IP
     */
    public function _ip_is_banned($CUR_IP = '')
    {
        if ( ! $CUR_IP) {
            $CUR_IP = common()->get_ip();
        }
        if ( ! $CUR_IP) {
            return false;
        }
        // We allow wildcards here (example: banned_ip: 192.168.*)
        $_banned_ips_array = main()->get_data('banned_ips');
        foreach ((array) $_banned_ips_array as $_ip => $_info) {
            $IP_MATCHED = false;
            $_ip = preg_replace('/[^0-9\.\*]/', '', $_ip);
            // Check as subnetwork with wildcard
            if (false != strpos($_ip, '*')) {
                $IP_MATCHED = preg_match('#' . str_replace(['.', '*'], ['\\.', '.*'], $_ip) . '#', $CUR_IP);
            // Check as subnetwork in CIDR format
            } elseif (false != strpos($_ip, '/')) {
                $IP_MATCHED = common()->_is_ip_in_cidr($CUR_IP, $_ip);
            } else {
                $IP_MATCHED = ($CUR_IP == $_ip);
            }
            if ($IP_MATCHED) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if given IP matches given CIDR.
     * @param mixed $text
     * @param mixed $charset_from
     * @param mixed $charset_into
     */
    public function _convert_charset($text = '', $charset_from = ''/*ISO-8859-1*/, $charset_into = 'utf-8')
    {
        if ( ! strlen($text)) {
            return false;
        }
        return _class('convert_charset')->go($text, $charset_from, $charset_into);
    }

    /**
     * Check multi-accounts.
     * @param mixed $target_user_id
     * @param mixed $source_user_id
     */
    public function _check_multi_accounts($target_user_id = 0, $source_user_id = 0)
    {
        return _class('check_multi_accounts', 'classes/common/')->_check($target_user_id, $source_user_id);
    }

    /**
     * Adaptively split large text into smaller parts by token with part size limit.
     * @param mixed $text
     * @param mixed $split_token
     * @param mixed $split_length
     */
    public function _my_split($text = '', $split_token = '', $split_length = 0)
    {
        return _class('other_common', 'classes/common/')->_my_split($text, $split_token, $split_length);
    }

    /**
     * Get user info(s) by id(s).
     * @param mixed $user_id
     * @param mixed $fields
     * @param mixed $params
     * @param mixed $return_sql
     */
    public function user($user_id, $fields = 'full', $params = '', $return_sql = false)
    {
        $db = db()->from('user')->where('id', $user_id);
        return $return_sql ? $db->sql() : $db->get();
    }

    /**
     * Check if user is ignored by second one.
     * @param mixed $target_user_id
     * @param mixed $owner_id
     */
    public function _is_ignored($target_user_id, $owner_id)
    {
        if (empty($target_user_id) || empty($owner_id) || $target_user_id == $owner_id) {
            return false;
        }
        return (bool) db()->query_num_rows(
            'SELECT * FROM ' . db('ignore_list') . ' WHERE user_id=' . (int) $owner_id . ' AND target_user_id=' . (int) $target_user_id
        );
    }

    /**
     * Remove accents from symbols.
     * @param mixed $text
     * @param mixed $case
     */
    public function _unaccent($text = '', $case = 0)
    {
        if (is_array($text)) {
            foreach ((array) $text as $k => $v) {
                $text[$k] = $this->_unaccent($v);
            }
            return $text;
        }
        if ( ! strlen($text)) {
            return $text;
        }
        return _class('utf8_clean', 'classes/common/')->_unaccent($text, $case);
    }

    /**
     * Create translit from Russian or Ukrainian text.
     * @param mixed $string
     */
    public function make_translit($string)
    {
        return _class('translit', 'classes/common/')->make($string);
    }

    /**
     * Cut BB Codes from the given text.
     * @param mixed $body
     */
    public function _cut_bb_codes($body = '')
    {
        return preg_replace('/\[[^\]]+\]/ims', '', $body);
    }

    /**
     * Log user actions for stats.
     * @param mixed $action_name
     * @param mixed $member_id
     * @param mixed $object_name
     * @param mixed $object_id
     */
    public function _log_user_action($action_name, $member_id, $object_name = '', $object_id = 0)
    {
        return _class('logs')->_log_user_action($action_name, $member_id, $object_name, $object_id);
    }

    /**
     * Creates tags cloud.
     * @param mixed $cloud_data
     * @param mixed $params
     */
    public function _create_cloud($cloud_data = [], $params = [])
    {
        return _class('common_tags_cloud', 'classes/common/')->create($cloud_data, $params);
    }

    /**
     * Makes thumb of remote web page
     * parameters: page url, filename(without extension).
     * @param mixed $url
     * @param mixed $filename
     */
    public function _make_thumb_remote($url, $filename)
    {
        $command = '/usr/src/webthumb-1.01/webthumb "' . $url . '" | pnmcrop -black | pamcut -top 95 -right -16 -bottom -40 | pnmtojpeg > ' . $filename;
        exec($command);
    }

    /**
     * Create account name of fixed length with given prefix.
     * @param mixed $id
     * @param mixed $prefix
     * @param mixed $length
     * @param mixed $padding_char
     */
    public function gen_account_name($id = 0, $prefix = 'u', $length = 5, $padding_char = '0')
    {
        if ( ! $id) {
            return false;
        }
        if ($length < strlen($id)) {
            $length = strlen($id);
        }
        return $prefix . str_pad($id, $length, $padding_char, STR_PAD_LEFT);
    }

    /**
     * Try to detect intrusions (XSS and other hack stuff).
     */
    public function intrusion_detection()
    {
        return _class('intrusion_detection', 'classes/common/')->check();
    }

    /**
     * Parse text using jevix.
     * @param mixed $text
     * @param mixed $params
     */
    public function jevix_parse($text = '', $params = [])
    {
        return _class('other_common', 'classes/common/')->jevix_parse($text, $params);
    }

    /**
     * Parse text using jevix.
     * @param mixed $text
     * @param mixed $params
     */
    public function text_typos($text = '', $params = [])
    {
        return _class('text_typos', 'classes/')->process($text, $params);
    }

    /**
     * Search related content based on params.
     * @param mixed $params
     */
    public function related_content($params = [])
    {
        return _class('related_content', 'classes/common/')->_process($params);
    }

    /**
     * Convert name into URL-friendly string.
     * @param mixed $name
     * @param null|mixed $force_dashes
     */
    public function _propose_url_from_name($name = '', $force_dashes = null)
    {
        if (empty($name)) {
            return '';
        }
        $url = str_replace([';', ',', '.', ':', ' ', '/'], '_', $name);
        $url = preg_replace('/[_-]{2,}/', '_', $url);
        $url = trim(trim(trim($url), '_-'));

        $url = common()->make_translit($url);

        $url = strtolower(preg_replace('/[^a-z0-9_-]+/i', '', $url));

        if ( ! isset($force_dashes)) {
            $force_dashes = $this->URL_FORCE_DASHES;
        }
        if ($force_dashes) {
            $url = str_replace('_', '-', $url);
            $url = preg_replace('/[-]{2,}/', '-', $url);
            $url = trim(trim(trim($url), '-'));
        } else {
            $url = str_replace('-', '_', $url);
            $url = preg_replace('/[_]{2,}/', '_', $url);
            $url = trim(trim(trim($url), '_'));
        }
        return $url;
    }

    /**
     * Simple trace without dumping whole objects.
     */
    public function trace()
    {
        $trace = [];
        foreach (debug_backtrace() as $k => $v) {
            if ( ! $k) {
                continue;
            }
            $v['object'] = is_object($v['object']) ? get_class($v['object']) : null;
            $trace[$k - 1] = $v;
        }
        return $trace;
    }

    /**
     * Print nice.
     */
    public function trace_string()
    {
        $e = new Exception();
        return implode(PHP_EOL, array_slice(explode(PHP_EOL, $e->getTraceAsString()), 1, -1));
    }

    /**
     * Convert URL to absolute form.
     * @param mixed $base_url
     * @param mixed $relative_url
     */
    public function url_to_absolute($base_url, $relative_url)
    {
        return _class('url_to_absolute', 'classes/common/')->url_to_absolute($base_url, $relative_url);
    }

    /**
     * is_utf8.
     * @param mixed $content
     */
    public function is_utf8($content)
    {
        require_php_lib('yf_utf8_funcs');
        include_once 'is_utf8.php';
        return is_utf8($content);
    }

    /**
     * utf8_html_entity_decode.
     * @param mixed $content
     */
    public function utf8_html_entity_decode($content)
    {
        require_php_lib('yf_utf8_funcs');
        include_once 'utf8_html_entity_decode.php';
        return utf8_html_entity_decode($content, true);
    }

    /**
     * strip_tags_smart.
     * @param mixed $content
     */
    public function strip_tags_smart($content)
    {
        require_php_lib('yf_utf8_funcs');
        include_once 'strip_tags_smart.php';
        return strip_tags_smart($content);
    }

    /**
     * strip_tags_smart.
     * @param mixed $text
     * @param mixed $params
     */
    public function utf8_clean($text = '', $params = [])
    {
        return _class('utf8_clean', 'classes/common/')->_do($text, $params);
    }

    /**
     * current GMT time.
     */
    public function gmtime()
    {
        return strtotime('now GMT');
    }

    /**
     * Localize current piece of data.
     * @param mixed $name
     * @param mixed $data
     * @param mixed $lang
     */
    public function l($name = '', $data = '', $lang = '')
    {
        return _class('l10n')->$name($data, $lang);
    }

    /**
     * new method checking for spider by ip address (database from http://www.iplists.com/).
     * @param mixed $ip
     * @param mixed $ua
     */
    public function _is_spider($ip = '', $ua = '')
    {
        return _class('spider_detection', 'classes/common/')->_is_spider($ip, $ua);
    }

    /**
     * Searches given URL for known search engines hosts.
     * @return string name of the found search engine
     * @param mixed $url
     */
    public function is_search_engine_url($url = '')
    {
        return _class('spider_detection', 'classes/common/')->is_search_engine_url($url);
    }

    /**
     * Return SQL part for detecting search engine ips.
     * @param mixed $field_name
     */
    public function get_spiders_ips_sql($field_name = 'ip')
    {
        return _class('spider_detection', 'classes/common/')->get_spiders_ips_sql($field_name);
    }

    /**
     * Get country by IP address using maxmind API (http://geolite.maxmind.com/download/geoip/api/php/).
     * @return 2-byte $country_code (uppercased) or false if something wrong
     * @param mixed $ip
     */
    public function _get_country_by_ip($ip = '')
    {
        return _class('other_common', 'classes/common/')->_get_country_by_ip($ip);
    }

    /**
     * Converter between well-known currencies.
     * @param mixed $number
     * @param mixed $c_from
     * @param mixed $c_to
     */
    public function _currency_convert($number = 0, $c_from = '', $c_to = '')
    {
        return _class('other_common', 'classes/common/')->_currency_convert($number, $c_from, $c_to);
    }

    /**
     * Threaded execution of the given object/action.
     * @param mixed $object
     * @param mixed $action
     * @param mixed $threads_params
     * @param mixed $max_threads
     */
    public function threaded_exec($object, $action = 'show', $threads_params = [], $max_threads = 10)
    {
        return _class('threads')->threaded_exec($object, $action, $threads_params, $max_threads);
    }

    /**
     * Helper to get params from command line.
     */
    public function get_console_params()
    {
        foreach ((array) $_SERVER['argv'] as $key => $argv) {
            if ($argv == '--params' && isset($_SERVER['argv'][$key + 1])) {
                return unserialize($_SERVER['argv'][$key + 1]);
            }
        }
        return false;
    }

    /**
     * Sphinx QL query wrapper.
     * @param mixed $sql
     * @param mixed $need_meta
     */
    public function sphinx_query($sql, $need_meta = false)
    {
        return _class('sphinxsearch')->query($sql, $need_meta);
    }

    /**
     * @param mixed $string
     */
    public function sphinx_escape_string($string)
    {
        return _class('sphinxsearch')->escape_string($string);
    }

    /**
     * @param mixed $str1
     * @param mixed $str2
     * @param mixed $type
     */
    public function get_diff($str1, $str2, $type = 'side_by_side')
    {
        return _class('diff', 'classes/common/')->get_diff($str1, $str2, $type);
    }


    public function show_left_filter()
    {
        $obj = module_safe($_GET['object']);
        $method = '_show_filter';
        if (method_exists($obj, $method) && ! (isset($obj->USE_FILTER) && ! $obj->USE_FILTER)) {
            return $obj->$method();
        }
    }


    public function show_side_column_hooked()
    {
        $obj = module_safe($_GET['object']);
        $method = '_hook_side_column';
        if (method_exists($obj, $method)) {
            return $obj->$method();
        }
    }

    /**
     * @param mixed $data
     */
    public function admin_wall_add($data = [])
    {
        return _class('admin_methods')->admin_wall_add($data);
    }

    /**
     * @param mixed $data
     */
    public function user_wall_add($data = [])
    {
        return db()->insert('user_walls', db()->es([
            'message' => isset($data['message']) ? $data['message'] : (isset($data[0]) ? $data[0] : ''),
            'user_id' => isset($data['user_id']) ? $data['user_id'] : (isset($data[1]) ? $data[1] : ''),
            'object_id' => isset($data['object_id']) ? $data['object_id'] : (isset($data[2]) ? $data[2] : (isset($_GET['id']) ? $_GET['id'] : '')),
            'object' => isset($data['object']) ? $data['object'] : (isset($data[3]) ? $data[3] : $_GET['object']),
            'action' => isset($data['action']) ? $data['action'] : (isset($data[4]) ? $data[4] : $_GET['action']),
            'important' => isset($data['important']) ? $data['important'] : (isset($data[5]) ? $data[5] : 0),
            'old_data' => json_encode(isset($data['old_data']) ? $data['old_data'] : (isset($data[6]) ? $data[6] : '')),
            'new_data' => json_encode(isset($data['new_data']) ? $data['new_data'] : (isset($data[7]) ? $data[7] : '')),
            'add_date' => date('Y-m-d H:i:s'),
            'server_id' => (int) main()->SERVER_ID,
            'site_id' => (int) main()->SITE_ID,
            'read' => isset($data['read']) ? $data['read'] : 0,
            'type' => isset($data['type']) ? $data['type'] : '',
        ]));
    }

    /**
     * @param mixed $name
     * @param mixed $cur_date
     */
    public function date_picker($name, $cur_date = '')
    {
        return _class('html')->date_picker($name, $cur_date);
    }

    /**
     * @param mixed $product_id
     */
    public function shop_get_images($product_id)
    {
        return module('shop')->_get_images($product_id);
    }

    /**
     * @param mixed $product_id
     * @param mixed $image_id
     * @param mixed $media
     */
    public function shop_generate_image_name($product_id, $image_id, $media = false)
    {
        return module('shop')->_generate_image_name($product_id, $image_id, $media);
    }

    /**
     * @param mixed $archive_name
     * @param mixed $EXTRACT_PATH
     */
    public function rar_extract($archive_name, $EXTRACT_PATH)
    {
        if (function_exists('rar_open')) {
            $rar = rar_open($archive_name);
            $list = rar_list($rar);
            foreach ($list as $file) {
                $file = explode('"', $file);
                $entry = rar_entry_get($rar, $file[1]);
                $entry->extract($EXTRACT_PATH);
            }
            rar_close($rar);
        }
    }

    /**
     * @param mixed $archive_name
     * @param mixed $EXTRACT_PATH
     */
    public function zip_extract($archive_name, $EXTRACT_PATH)
    {
        $zip = new ZipArchive();
        $res = $zip->open($archive_name);
        if ($res === true) {
            $zip->extractTo($EXTRACT_PATH);
            $zip->close();
        }
    }

    /**
     * Returns the sum in words (for money).
     * @param mixed $number
     * @param null|mixed $currency_id
     * @param null|mixed $lang_id
     */
    public function num2str($number, $currency_id = null, $lang_id = null)
    {
        return _class('common_num2string', 'classes/common/')->num2str($number, $currency_id, $lang_id);
    }

    /**
     * @param mixed $name
     */
    public function dashboard_display($name)
    {
        return _class('dashboards', 'classes/common/')->display($name);
    }

    /**
     * @param mixed $name
     */
    public function dashboard2_display($name)
    {
        return _class('dashboards2')->display($name);
    }

    /*
     * Returns all types with empty param 'type'
     * Works in both ways:
     * - get status name by id
     * - get status id by name
     * */
    public function get_static_conf($type = false, $value = false, $translate = true)
    {
        return _class('common_static_conf', 'classes/common/')->get_static_conf($type, $value, $translate);
    }

    /**
     * @param mixed $text
     * @param null|mixed $options
     */
    public function message_success($text = '', $options = null)
    {
        return $this->add_message($text, 'success', '');
    }

    /**
     * @param mixed $text
     * @param null|mixed $options
     */
    public function message_info($text = '', $options = null)
    {
        return $this->add_message($text, 'info', '', $options);
    }

    /**
     * @param mixed $text
     * @param null|mixed $options
     */
    public function message_warning($text = '', $options = null)
    {
        return $this->add_message($text, 'warning', '', $options);
    }

    /**
     * @param mixed $text
     * @param null|mixed $options
     */
    public function message_error($text = '', $options = null)
    {
        return $this->add_message($text, 'error', '', $options);
    }

    /**
     * @param mixed $text
     * @param mixed $level
     * @param mixed $key
     * @param null|mixed $options
     */
    public function add_message($text = '', $level = 'info', $key = '', $options = null)
    {
        if ( ! strlen($text)) {
            return false;
        }
        $is_translate = true;
        isset($options['translate']) && $is_translate = (bool) $options['translate'];
        if ($is_translate) {
            $text = t($text);
        }
        if ($key) {
            $_SESSION['permanent'][$level][$key] = $text;
        } else {
            $_SESSION['permanent'][$level][$text] = $text;
        }
        return true;
    }

    public function is_messages()
    {
        $result = isset($_SESSION['permanent']) && count((array) $_SESSION['permanent']) > 0;
        return $result;
    }


    public function show_messages()
    {
        if ( ! $this->is_messages()) {
            return false;
        }
        $body = [];
        $level_to_style = [
            'info' => 'alert alert-info',
            'success' => 'alert alert-success',
            'warning' => 'alert alert-warning',
            'error' => 'alert alert-error alert-danger',
        ];
        foreach ((array) $level_to_style as $level => $css_style) {
            $messages = $_SESSION['permanent'][$level];
            if ( ! is_array($messages) || ! count((array) $messages)) {
                continue;
            }
            $body[] = '<div class="' . $css_style . '"><button type="button" class="close" data-dismiss="alert">×</button>' . implode('<br />' . PHP_EOL, $messages) . '</div>';
        }
        unset($_SESSION['permanent']);
        return implode(PHP_EOL, $body);
    }

    /**
     * Raise user error.
     * @param mixed $text
     * @param mixed $error_key
     */
    public function _raise_error($text = '', $error_key = '')
    {
        $text = t((string) $text);
        $error_key = (string) $error_key;
        if ( ! $text) {
            return false;
        }
        if ( ! isset($this->USER_ERRORS)) {
            $this->USER_ERRORS = [];
        }
        if ($error_key) {
            $this->USER_ERRORS[$error_key] = $text;
        } else {
            $this->USER_ERRORS[] = $text;
        }
        return true;
    }

    /**
     * Chec if user error exists.
     * @param mixed $error_key
     */
    public function _error_exists($error_key = '')
    {
        if ( ! empty($error_key)) {
            return (bool) $this->USER_ERRORS[$error_key];
        }
        return isset($this->USER_ERRORS) && count((array) $this->USER_ERRORS) ? true : false;
    }

    /**
     * Show formatted contents of user errors.
     * @param mixed $key
     */
    public function _get_error_messages($key = '')
    {
        if ( ! $this->USER_ERRORS) {
            return false;
        }
        return $key ? $this->USER_ERRORS[$key] : $this->USER_ERRORS;
    }

    /**
     * @param mixed $key
     */
    public function _remove_error_messages($key = '')
    {
        if ($key) {
            unset($this->USER_ERRORS[$key]);
        } else {
            $this->USER_ERRORS = [];
        }
    }

    /**
     * Show formatted contents of user errors.
     * @param mixed $cur_error_msg
     * @param mixed $clear_error
     */
    public function _show_error_message($cur_error_msg = '', $clear_error = true)
    {
        // Prevent recursive display
        if (strlen($cur_error_msg) && false !== strpos($cur_error_msg, '<!--YF_ERROR_MESSAGE_START-->')) {
            return t($cur_error_msg);
        }
        if ( ! isset($this->USER_ERRORS)) {
            $this->USER_ERRORS = [];
        }
        if (strlen($cur_error_msg)) {
            $this->USER_ERRORS[] = $cur_error_msg;
        }
        foreach ((array) $this->USER_ERRORS as $error_key => $value) {
            if (empty($value)) {
                continue;
            }
            $items[$error_key] = $value;
        }
        if ($this->TRACK_USER_ERRORS && ! empty($this->USER_ERRORS)) {
            _class('logs')->save_user_error($this->USER_ERRORS);
        }
        if ($clear_error) {
            $this->_remove_error_messages();
        }
        if (conf('IS_SPIDER')) {
            return false;
        }
        if (empty($items)) {
            return '';
        }
        $replace = [
            'items' => $items,
            'error' => 'error', // DO NOT REMOVE THIS!!! NEEDED TO AVOID INFINITE LOOP INSIDE TPL CLASS
        ];
        return tpl()->parse('system/error_message', $replace);
    }

    /**
     * Show formatted single error message.
     * @param mixed $error_key
     */
    public function _show_error_inline($error_key = '')
    {
        if (empty($error_key)) {
            return false;
        }
        // Try to get error message
        $error_msg = '';
        if (isset($this->USER_ERRORS[$error_key])) {
            $error_msg = $this->USER_ERRORS[$error_key];
        }
        // Last check
        if (empty($error_msg)) {
            return false;
        }
        // Prepare template
        $replace = [
            'text' => $error_msg,
            'key' => $error_key,
        ];
        return tpl()->parse('system/error_inline', $replace);
    }

    /**
     * Show error and set response header to "404 Not Found".
     * @param mixed $msg
     */
    public function error_404($msg = '')
    {
        if ((MAIN_TYPE_ADMIN && is_logged_in()) || DEBUG_MODE) {
            // Do not override status header for logged in admin, just display error inlined
            ! $msg && $msg = t('404 Not Found');
        } else {
            // All other cases
            header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1') . ' 404 Not Found');
            main()->IS_404 = true;
        }
        if (DEBUG_MODE) {
            no_graphics(true);
            $body = '<b>404 Not found</b><br />' . PHP_EOL . '<i>' . $msg . '</i>';
            $body .= '<pre><small>' . htmlspecialchars(main()->trace_string()) . '</small></pre>';
            return print common()->show_empty_page($body, ['full_width' => 1]);
        }
        return $this->_show_error_message($msg);
    }

    /**
     * Show error and set response header to "403 Forbidden".
     * @param mixed $msg
     */
    public function error_403($msg = '')
    {
        if ((MAIN_TYPE_ADMIN && is_logged_in()) || DEBUG_MODE) {
            // Do not override status header for logged in admin, just display error inlined
            ! $msg && $msg = t('403 Forbidden');
        } else {
            // All other cases
            header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1') . ' 403 Forbidden');
            main()->IS_403 = true;
        }
        if (DEBUG_MODE) {
            no_graphics(true);
            $body = '<b>404 Not found</b><br />' . PHP_EOL . '<i>' . $msg . '</i>';
            $body .= '<pre><small>' . htmlspecialchars(main()->trace_string()) . '</small></pre>';
            return print common()->show_empty_page($body, ['full_width' => 1]);
        }
        return $this->_show_error_message($msg);
    }

    /**
     * Show formatted contents of notices for user.
     * @param mixed $keep
     * @param mixed $force_text
     */
    public function show_notices($keep = false, $force_text = '')
    {
        if ($force_text) {
            $this->set_notice($force_text);
        }
        $name_in_session = '_user_notices';
        $items = $_SESSION[$name_in_session];
        if ( ! $keep) {
            $_SESSION[$name_in_session] = [];
            unset($_SESSION[$name_in_session]);
        }
        return $items ? tpl()->parse('system/user_notices', ['items' => $items]) : '';
    }

    /**
     * Set notice to display on next page (usually after redirect).
     * @param mixed $text
     */
    public function set_notice($text = '')
    {
        $_SESSION['_user_notices'][crc32($text)] = $text;
    }
}
