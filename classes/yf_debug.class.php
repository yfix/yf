<?php

/**
 * Debug console.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_debug
{
    public $SHOW_DB_QUERY_LOG = true;
    public $SHOW_DB_STATS = false;
    public $SHOW_DB_EXPLAIN_QUERY = true;
    public $SHOW_SPHINX = true;
    public $SHOW_SSH = true;
    public $SHOW_STPLS = true;
    public $SHOW_REWRITE_INFO = true;
    public $SHOW_OUTPUT_CACHE_INFO = true;
    public $SHOW_RESIZED_IMAGES_LOG = true;
    public $SHOW_INCLUDED_FILES = true;
    public $SHOW_LOADED_MODULES = true;
    public $SHOW_REDIS_INFO = true;
    public $SHOW_MEMCACHED_INFO = true;
    public $SHOW_DASHBOARD_INFO = true;
    public $SHOW_XCACHE_INFO = true;
    public $SHOW_APC_INFO = true;
    public $SHOW_MAIN_GET_DATA = true;
    public $SHOW_CORE_CACHE = true;
    public $SHOW_MAIN_EXECUTE = true;
    public $SHOW_GLOBALS = true;
    public $SHOW_NOT_TRANSLATED = true;
    public $SHOW_I18N_VARS = true;
    public $SHOW_INPUT_DATA = true;
    public $SHOW_GET_DATA = true;
    public $SHOW_POST_DATA = true;
    public $SHOW_COOKIE_DATA = true;
    public $SHOW_SESSION_DATA = true;
    public $SHOW_FILES_DATA = true;
    public $SHOW_SERVER_DATA = true;
    public $SHOW_ENV_DATA = true;
    public $SHOW_SETTINGS = true;
    public $SHOW_CURL_REQUESTS = true;
    public $SHOW_FORM2 = true;
    public $SHOW_TABLE2 = true;
    public $SHOW_DD_TABLE = true;
    public $SORT_TEMPLATES_BY_NAME = true;
    public $ADD_ADMIN_LINKS = true;
    public $ADMIN_PATHS = [
        'edit_stpl' => '?object=template_editor&action=edit_stpl&location={LOCATION}&theme={THEME}&name={ID}',
        'edit_i18n' => '?object=locale_editor&action=var_edit&id={ID}',
        'edit_file' => '?object=file_manager&action=edit&id={ID}',
        'show_db_table' => '?object=db_manager&action=table_show&id={ID}',
        'sql_query' => '?object=db_manager&action=import&id={ID}',
        'link' => '{ID}',
    ];

    public $_NOT_TRANSLATED_FILE = null;
    public $DEBUG_CONSOLE_LIGHT = null;
    public $DEBUG_CONSOLE_HIDDEN = null;
    public $_used_debug_datas = [];
    public $backup_debug_data = [];

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
     * Constructor.
     */
    public function _init()
    {
        $this->_NOT_TRANSLATED_FILE = PROJECT_PATH . 'logs/not_translated_' . conf('language') . '.php';
        $name = 'debug_console_light';
        $this->DEBUG_CONSOLE_LIGHT = (int) ((bool) (isset($_SESSION[$name]) ? $_SESSION[$name] : $_GET[$name]));
        $name = 'debug_console_hidden';
        $this->DEBUG_CONSOLE_HIDDEN = (int) ((bool) (isset($_SESSION[$name]) ? $_SESSION[$name] : $_GET[$name]));
        if ($this->DEBUG_CONSOLE_HIDDEN) {
            $this->DEBUG_CONSOLE_LIGHT = true;
        }
    }

    /**
     * Create simple table with debug info.
     */
    public function go()
    {
        if ($this->DEBUG_CONSOLE_HIDDEN) {
            return false;
        }
        $ts = microtime(true);
        // Do hide console if needed
        if ($_SESSION['hide_debug_console'] || $_GET['hide_debug_console']) {
            return '';
        }
        $exec_time = round(microtime(true) - main()->_time_start, 4);
        $main_exec_time = common()->_show_execution_time();
        $num_db_queries = db()->NUM_QUERIES;

        $debug_timings = [];
        $methods = [];
        $class_name = get_class($this);
        foreach ((array) get_class_methods($class_name) as $method) {
            if (substr($method, 0, strlen('_debug_')) != '_debug_' || $method == $class_name || $method == __FUNCTION__) {
                continue;
            }
            $name = substr($method, strlen('_debug_'));
            if ($this->DEBUG_CONSOLE_LIGHT && $name !== 'DEBUG_YF') {
                continue;
            }
            $ts2 = microtime(true);
            $method_params = null;
            $content = $this->$method($method_params);
            if ($method_params) {
                $debug_params[$method] = $method_params;
            }
            $debug_timings[$method] = round(microtime(true) - $ts2, 4) . ' sec';
            $debug_contents[$name] = $content;
        }
        $debug_add = [];
        _class('core_events')->fire('debug.render', $debug_add);
        if ($debug_add) {
            foreach ((array) $debug_add as $k => $v) {
                $debug_contents[$k] = $v;
            }
        }
        $debug_time = round(microtime(true) - $ts, 4);

        $debug_timings_html = '<div class="span4 col-md-4">Debug panel timing: ' . $debug_time . '<br />' . $this->_show_key_val_table($debug_timings, ['no_total' => 1, 'no_sort' => 1, 'no_escape' => 1]) . '</div>';
        $debug_contents['DEBUG_YF'] .= $debug_timings_html;

        $data['debug_info'] = [
            'class_head' => 'tab_info_compact',
            'disabled' => 1,
            'desc_raw' => '
				<span title="' . t('Page generation time in seconds') . '"><i class="icon icon-time fa fa-clock-o"></i>&nbsp;' . $exec_time . '</span>
				<span title="' . t('Database queries') . '">&nbsp;<i class="icon icon-table fa fa-table"></i>&nbsp;' . (int) $num_db_queries . '</span><br />
				<span title="' . t('Debug console generation time in seconds') . '"><small>D&nbsp;' . $debug_time . '</small></span>',
        ];
        foreach ((array) $debug_contents as $name => $content) {
            if (empty($content)) {
                continue;
            }
            $data[$name] = $content;
        }
        $links_prefix = 'debug_item_';
        $cookie_active_tab = substr($_COOKIE['debug_tabs_active'] ?? '', strlen($links_prefix));
        // Show default tab if saved tab not existing now for any reason
        if (! isset($data[$cookie_active_tab])) {
            $cookie_active_tab = '';
        }
        $body[] = _class('html')->tabs($data, [
            'selected' => $cookie_active_tab ?: 'DEBUG_YF',
            'no_auto_desc' => 1,
            'links_prefix' => $links_prefix,
        ]);
        return '<div id="debug_console">' . implode(PHP_EOL, $body) . '</div>';
    }


    public function _get_request_headers()
    {
        $arh = [];
        $rx_http = '/(\AHTTP_)/';
        foreach ((array) $_SERVER as $key => $val) {
            if (! preg_match($rx_http, $key, $m)) {
                continue;
            }
            $arh_key = str_replace($m[1], '', $key);
            $rx_matches = explode('_', $arh_key);
            if (count((array) $rx_matches) > 0 && strlen($arh_key) > 2) {
                foreach ($rx_matches as $ak_key => $ak_val) {
                    $rx_matches[$ak_key] = ucwords(strtolower($ak_val));
                }
                $arh_key = implode('-', $rx_matches);
            }
            $arh[$arh_key] = $val;
        }
        return $arh;
    }

    /**
     * @param mixed $FS_PATH
     * @param mixed $as_submodule
     */
    public function _get_git_details($FS_PATH, $as_submodule = false)
    {
        $git_base_path = $FS_PATH . '.git';
        if (! file_exists($git_base_path)) {
            return [];
        }
        $git_head_path = $git_base_path . '/HEAD';
        $git_branch = '';
        $git_hash = '';
        $git_date = 0;
        if (! file_exists($git_head_path) && file_exists($git_base_path) && is_file($git_base_path)) {
            // gitdir: ../.git/modules/yf
            list(, $git_base_path) = explode('gitdir:', file_get_contents($git_base_path));
            $git_base_path = realpath($FS_PATH . trim($git_base_path ?? ''));
            $git_head_path = $git_base_path && file_exists($git_base_path) ? $git_base_path . '/HEAD' : '';
        }
        if ($git_head_path && file_exists($git_head_path)) {
            // ref: refs/heads/master
            list(, $git_subhead_path) = explode('ref:', file_get_contents($git_head_path));
            $git_subhead_path = trim($git_subhead_path ?? '');
            $git_branch = basename($git_subhead_path);
            $git_hash_file = $git_subhead_path ? $git_base_path . '/' . trim($git_subhead_path) : '';
            $git_hash = $git_hash_file && file_exists($git_hash_file) ? trim(file_get_contents($git_hash_file)) : '';
            if (! $git_hash) {
                $git_hash_file = $git_subhead_path ? $git_base_path . '/refs/remotes/origin/' . $git_branch : '';
                $git_hash = $git_hash_file && file_exists($git_hash_file) ? trim(file_get_contents($git_hash_file)) : '';
            }
        }
        $git_log_path = $git_base_path . '/logs/HEAD';
        if ($git_hash && file_exists($git_log_path)) {
            // ad814593f2844620e37ae220aea8a746d6a53efa 54b490f737cb00c701b3d7439be6d8032d398e96 Some author <some.author@email.dev> 1412937971 +0300    commit: db real mysql tests up
            foreach ((array) file($git_log_path) as $line) {
                if (strpos($line, $git_hash) === false) {
                    continue;
                }
                $line = trim($line);
                $hash = trim(substr($line, 41, 40));
                if ($hash === $git_hash) {
                    list($tmp, $msg) = explode("\t", $line);
                    $git_date = substr($tmp, -strlen('1412937971 +0300'), -strlen(' +0300'));
                    break;
                }
            }
        }
        $git_config_path = $git_base_path . '/config';
        $url_part = '';
        if (file_exists($git_config_path)) {
            $config = file_get_contents($git_config_path);
            $pattern1 = '/origin"\].+?url\s+=\s+(git@|https:\/\/)(?P<url_part>.+?).git/ims';
            $pattern2 = '/submodule\s+"yf"\].+?url\s+=\s+(git@|https:\/\/)(?P<url_part>.+?).git/ims';
            if (preg_match($pattern1, $config, $m) || preg_match($pattern2, $config, $m)) {
                $url_part = str_replace(':', '/', $m['url_part']);
            }
        }
        return [
            'hash' => $git_hash,
            'date' => $git_date,
            'url' => 'https://' . $url_part . '/tree/',
            'branch' => $git_branch,
        ];
    }


    public function _get_yf_version()
    {
        $out = [];
        $as_submodule = false;
        if (strlen(YF_PATH) > strlen(APP_PATH) && substr(YF_PATH, 0, strlen(APP_PATH)) === APP_PATH) {
            $as_submodule = true;
        }
        $git = $this->_get_git_details(YF_PATH, $as_submodule);
        $yf_version_file = YF_PATH . '.yf_version';
        $yf_version = file_exists($yf_version_file) ? file_get_contents($yf_version_file) : '';
        if ($yf_version) {
            $yf_version = _prepare_html($yf_version);
            $out[] = '<a href="' . $git['url'] . $yf_version . '" class="btn btn-mini btn-xs btn-default">' . $yf_version . '</a>';
        }
        if ($git['hash']) {
            $git['hash'] = _prepare_html($git['hash']);
            $out[] = '<a href="' . $git['url'] . $git['hash'] . '" class="btn btn-mini btn-xs btn-default">' . substr($git['hash'], 0, 8) . '</a>';
        }
        if ($git['branch']) {
            $out[] = '<a href="' . $git['url'] . $git['branch'] . '" class="btn btn-mini btn-xs btn-default">' . substr($git['branch'], 0, 8) . '</a>';
        }
        if ($git['date']) {
            $out[] = date('Y-m-d H:i', $git['date']);
        }
        return implode(' | ', $out);
    }


    public function _get_app_version()
    {
        $out = [];
        $git = $this->_get_git_details(APP_PATH);
        $app_version_file = APP_PATH . '.app_version';
        $app_version = file_exists($app_version_file) ? file_get_contents($app_version_file) : '';
        if ($app_version) {
            $app_version = _prepare_html($app_version);
            $out[] = '<a href="' . $git['url'] . $app_version . '" class="btn btn-mini btn-xs btn-default">' . $app_version . '</a>';
        }
        if ($git['hash']) {
            $git['hash'] = _prepare_html($git['hash']);
            $out[] = '<a href="' . $git['url'] . $git['hash'] . '" class="btn btn-mini btn-xs btn-default">' . substr($git['hash'], 0, 8) . '</a>';
        }
        if ($git['branch']) {
            $out[] = '<a href="' . $git['url'] . $git['branch'] . '" class="btn btn-mini btn-xs btn-default">' . substr($git['branch'], 0, 8) . '</a>';
        }
        if ($git['date']) {
            $out[] = date('Y-m-d H:i', $git['date']);
        }
        return implode(' | ', $out);
    }

    /**
     * @param mixed $db
     * @param mixed $connect_trace
     */
    public function _do_debug_db_connection_queries($db, $connect_trace = [])
    {
        if (! $this->SHOW_DB_QUERY_LOG) {
            return '';
        }
        if (! is_object($db) || ! is_array($db->_LOG) || ! $db->_tried_to_connect) {
            return false;
        }
        $body = '';
        $items = [];
        $db_queries_list = $db->_LOG;
        if ($this->SHOW_DB_EXPLAIN_QUERY && ! empty($db_queries_list) && substr($db->DB_TYPE, 0, 5) == 'mysql') {
            foreach ((array) $db_queries_list as $id => $log) {
                if ($log['error']) {
                    continue;
                }
                $sql = trim($log['sql']);
                // Cut comment
                if (substr($sql, 0, 2) == '--') {
                    $sql = substr($sql, strpos($sql, "\n"));
                }
                $sql = preg_replace('/[\s]{2,}/ims', ' ', str_replace("\t", ' ', trim($sql)));
                if (preg_match('/^[\(]*select/ims', $sql)) {
                    $db_explain_results[$id] = $db->get_all('EXPLAIN ' . $sql, -1);
                }
            }
        }
        $total_queries_exec_time = 0;

        $body .= '<b>' . t('QUERY_LOG') . '  ('
            . ($db->DB_SSL ? 'SSL ' : '')
            . $db->DB_TYPE
            . '://' . $db->DB_USER
            . '@' . $db->DB_HOST
            . ($db->DB_PORT ? ':' . $db->DB_PORT : '')
            . '/' . $db->DB_NAME
            . ($db->DB_CHARSET ? '?charset=' . $db->DB_CHARSET : '')
            . ($db->DB_SOCKET ? '?socket=' . $db->DB_SOCKET : '')
            . ')</b>';

        $trace_html = ' <a href="javascript:void(0)" class="btn btn-default btn-mini btn-xs btn-toggle" data-hidden-toggle="debug-db-connect-trace">' . t('Trace') . '</a>'
            . '<pre style="display:none;" id="debug-db-connect-trace"><small>' . _prepare_html($connect_trace) . '</small></pre>';

        $body .= $connect_trace ? $trace_html : '';

        $_this = $this;
        foreach ((array) $db_queries_list as $id => $log) {
            $sql = trim($log['sql']);
            // Cut comment
            if (substr($sql, 0, 2) == '--') {
                $sql = substr($sql, strpos($sql, "\n"));
                $sql = trim($sql);
                if (! strlen($sql)) {
                    continue;
                }
            }
            $total_queries_exec_time += $log['time'];
            $_cur_trace = $log['trace'];
            $_cur_explain = isset($db_explain_results[$id]) ? $this->_format_db_explain_result($db_explain_results[$id]) : '';
            $_sql_type = strtoupper(rtrim(substr(ltrim($sql), 0, 7)));

            $admin_link = $this->_admin_link('sql_query', $sql, true);
            $sql = htmlspecialchars($sql);
            $replace = [
                ',' => ', ',
            ];
            $sql = str_replace(array_keys($replace), array_values($replace), $sql);
            if ($db->DB_PREFIX) {
                $sql = preg_replace_callback('/([\s\t]+`?)(' . preg_quote($db->DB_PREFIX, '/') . '[a-z0-9_]+)(`?)/ims', function ($m) use ($_this) {
                    return $m[1] . $_this->_admin_link('show_db_table', $m[2]) . $m[3];
                }, $sql);
            }
            $exec_time = round($log['time'], 4);
            if ($admin_link && $this->ADD_ADMIN_LINKS) {
                $exec_time = '<a href="' . $admin_link . '" class="btn btn-default btn-mini btn-xs" rel="nofollow,noindex" target="_blank">' . $exec_time . '</a>';
            }
            $num = $id + 1;
            $items[] = [
                'id' => $num,
                'sql' => $sql,
                'rows' => (string) ($log['rows']),
                'insert_id' => (string) ($log['insert_id']),
                'error' => $log['error'] ? '<pre>' . _prepare_html($this->_var_export($log['error'])) . '</pre>' : '',
                'warning' => $log['warning'] ? '<pre>' . _prepare_html($this->_var_export($log['warning'])) . '</pre>' : '',
                'info' => $log['info'] ? '<pre>' . _prepare_html($this->_var_export($log['info'])) . '</pre>' : '',
                'exec_time' => (string) $exec_time,
                'time' => round($log['time'], 4),
                'trace' => $_cur_trace,
                'explain' => $_cur_explain,
            ];
        }
        $items = $this->_time_count_changes($items);
        foreach ((array) $items as $k => $v) {
            unset($items[$k]['time']);
        }
        return $this->_show_auto_table($items, [
            'first_col_width' => '1%',
            'tr' => function ($row, $id) {
                return $row['error'] ? ' class="error"' : '';
            },
            'caption' => [
                'total_exec_time' => round($total_queries_exec_time, 4),
                'connect_time' => round($db->_connection_time, 4),
            ],
            'hidden_map' => [
                'explain' => 'sql',
                'trace' => 'sql',
                'error' => 'sql',
                'warning' => 'sql',
                'info' => 'sql',
            ],
        ]);
    }

    /**
     * @param mixed $db
     */
    public function _show_db_shutdown_queries($db)
    {
        if (! $this->SHOW_DB_QUERY_LOG) {
            return '';
        }
        return $this->_show_key_val_table($db->_SHUTDOWN_QUERIES);
    }

    /**
     * @param mixed $db
     */
    public function _show_db_stats($db)
    {
        if (! $this->SHOW_DB_STATS) {
            return '';
        }
        $data['stats'] = $db->get_2d('SHOW SESSION STATUS');
        $data['vars'] = $db->get_2d('SHOW VARIABLES');
        //		$data['global_vars'] = $db->get_2d('SHOW GLOBAL VARIABLES');
        $body = '';
        foreach ($data as $name => $_data) {
            $body .= '<div class="span10 col-md-10">' . $name . '<br>' . $this->_show_key_val_table($_data, ['no_total' => 1, 'skip_empty_values' => 1]) . '</div>';
        }
        return $body;
    }

    /**
     * @param mixed $explain_result
     */
    public function _format_db_explain_result($explain_result = [])
    {
        if (empty($explain_result)) {
            return false;
        }
        $body = '<table class="table table-bordered table-striped table-hover table-condensed">';
        // Header
        foreach ((array) $explain_result[0] as $k => $v) {
            $body .= '<td>' . $k . '</td>';
        }
        // Data
        foreach ((array) $explain_result as $_num => $_data) {
            $body .= '<tr>';
            foreach ((array) $_data as $k => $v) {
                $body .= '<td>' . (strlen($v ?? '') ? $v : '') . '</td>';
            }
            $body .= '</tr>';
        }
        $body .= '</table>';
        return $body;
    }

    /**
     * @param mixed $a
     * @param mixed $params
     * @param mixed $name
     */
    public function _show_key_val_table($a, $params = [], $name = '')
    {
        if (! $a) {
            return false;
        }
        if (! isset($params['first_col_width'])) {
            $params['first_col_width'] = '1%';
        }
        if (is_array($a) && ! $params['no_sort']) {
            ksort($a);
        }
        // Escape by default
        if (! $params['no_escape']) {
            $params['escape'] = 1;
        }
        $items = [];
        foreach ((array) $a as $k => $v) {
            if ($params['skip_empty_values'] && ! $v) {
                continue;
            }
            $v = is_array($v) ? nl2br($this->_var_export($v)) : $v;
            $items[] = [
                'key' => $params['escape'] ? _prepare_html($k) : $k,
                'value' => $params['escape'] && strlen($v) ? '<pre>' . _prepare_html($v) . '</pre>' : $v,
            ];
        }
        if (! $items) {
            return false;
        }
        if ($params['escape']) {
            $params['no_escape'] = 1; // Means we already escaped here
        }
        return $this->_show_auto_table($items, $params);
    }

    /**
     * @param mixed $items
     * @param mixed $params
     * @param mixed $name
     */
    public function _show_auto_table($items = [], $params = [], $name = '')
    {
        if (! is_array($items)) {
            $items = [];
        }
        $items = $this->_format_trace_in_items($items);
        $total_time = 0.0;
        foreach ($items as $k1 => $item) {
            foreach ($item as $k => $v) {
                if (is_array($v)) {
                    $v = ! empty($v) ? $this->_var_export($v) : '';
                    if (! $params['no_escape']) {
                        $v = ! empty($v) ? _prepare_html($v) : '';
                    }
                    if (is_array($v)) {
                        $v = $this->_var_export($v);
                    }
                    $items[$k1][$k] = $v;
                }
                if ($k == 'time') {
                    $total_time += $v;
                }
            }
        }
        if (! $items) {
            return false;
        }
        $caption = $params['header'] ? '<b class="btn btn-default disabled">' . $params['header'] . '</b>' : '';
        if (! $params['no_total']) {
            if (! is_array($params['caption'])) {
                $params['caption'] = [];
            }
            count((array) $items) && $params['caption']['items'] = count((array) $items);
            $total_time && $params['caption']['total_time'] = round($total_time, 4);
            foreach ((array) $params['caption'] as $k => $v) {
                $caption .= ' <span class="label label-info">' . $k . ': ' . $v . '</span>' . PHP_EOL;
            }
        }
        $table = table((array) $items, [
            'table_class' => 'debug_item table-condensed',
            'auto_no_buttons' => 1,
            'pager_records_on_page' => 10000,
            'hidden_map' => $params['hidden_map'],
            'tr' => $params['tr'],
            'td' => $params['td'],
            'no_total' => true,
            'caption' => $caption ? '<div class="pull-left">' . $caption . '</div>' : '',
        ])->auto();
        foreach ((array) $params['hidden_map'] as $name => $to) {
            $table->btn($name, 'javascript:void();', ['hidden_toggle' => $name, 'display_func' => function ($row, $info, $params) use ($name) {
                return (bool) strlen($row[$name] ?? '');
            }]);
        }
        return (string) $table;
    }

    /**
     * Process through admin link or just return text if links disabled.
     * @param mixed $type
     * @param mixed $text
     * @param mixed $just_link
     * @param mixed $replace
     */
    public function _admin_link($type, $text = '', $just_link = false, $replace = [])
    {
        if (! $this->ADD_ADMIN_LINKS || ! isset($this->ADMIN_PATHS[$type])) {
            return $text;
        }
        if ($type == 'link') {
            return '<a href="' . $text . '" class="btn btn-default btn-mini btn-xs" rel="nofollow,noindex" target="_blank">' . $text . '</a>';
        }
        $id = $text;
        $replace += [
            '{ID}' => urlencode(str_replace('\\', '/', $id)),
            '{THEME}' => conf('theme'),
        ];
        $url_tpl = $this->ADMIN_PATHS[$type];
        if ($type == 'show_db_table') {
            $url_tpl = 'adminer.php?server=' . DB_HOST . '&username=' . DB_USER . '&db=' . DB_NAME . '&table={ID}';
        } elseif ($type == 'sql_query') {
            $url_tpl = 'adminer.php?server=' . DB_HOST . '&username=' . DB_USER . '&db=' . DB_NAME . '&sql={ID}';
        }
        $url = str_replace(array_keys($replace), array_values($replace), $url_tpl);
        $link = ADMIN_WEB_PATH . $url;
        if ($just_link) {
            return $link;
        }
        return '<a href="' . $link . '" class="btn btn-default btn-mini btn-xs" rel="nofollow,noindex" target="_blank">' . $text . '</a>';
    }

    /**
     * @param mixed $trace
     */
    public function _format_trace($trace)
    {
        return '<pre><small>' . _prepare_html($trace) . '</small></pre>';
    }

    /**
     * @param mixed $items
     */
    public function _format_trace_in_items($items)
    {
        foreach ((array) $items as $k => $v) {
            if (isset($v['trace'])) {
                $items[$k]['trace'] = $this->_format_trace($v['trace']);
            }
        }
        return $items;
    }

    /**
     * @param mixed $name
     */
    public function _get_debug_data($name)
    {
        @$this->_used_debug_datas[$name]++;
        $data = debug($name);
        $this->backup_debug_data[$name] = $data;
        debug($name, false);
        return $data;
    }

    /**
     * @param mixed $items
     * @param mixed $field
     */
    public function _time_count_changes($items = [], $field = 'time')
    {
        $time_all = 0;
        $time_max = 0;
        foreach ((array) $items as $i => $v) {
            $time = $v[$field];
            $time_all += $time;
            if ($time > $time_max) {
                $time_max = $time;
            }
        }
        if (! $time_all) {
            return $items;
        }
        $warn_limit = $time_max / $time_all * 100 / 2;
        if ($warn_limit < 20) {
            $warn_limit = 20;
        }
        foreach ((array) $items as $i => $v) {
            $time = $v[$field];
            $timep = round($time / $time_all * 100, 1);
            $items[$i]['timep'] = $timep ? '<span class="' . ($timep > $warn_limit ? 'label label-warning' : '') . '">' . $timep . '%</span>' : '';
        }
        return $items;
    }

    /**
     * @param mixed $var
     */
    public function _var_export($var)
    {
        if (defined('HHVM_VERSION')) {
            return is_array($var) ? print_r($var, 1) : $var;
        }
        return _var_export($var);
    }

    //------------ debug methods tabs below -----------//


    public function _debug_DEBUG_YF(&$params = [])
    {
        if (! $this->SHOW_SETTINGS) {
            return '';
        }
        $body = '';
        $cache_use = ((main()->USE_SYSTEM_CACHE || conf('USE_CACHE')) && ! cache()->NO_CACHE);
        $locale_debug = $this->_get_debug_data('locale');
        $data['yf'] = [
            'MAIN_TYPE' => MAIN_TYPE,
            'LANG' => conf('language'),
            'IP' => common()->get_ip(),
            'COUNTRY' => conf('country') ?: $_SERVER['GEOIP_COUNTRY_CODE'],
            'TIMEZONE' => date_default_timezone_get() . (conf('timezone') ? ', conf: ' . conf('timezone') : ''),
            'DEBUG_MODE' => (int) DEBUG_MODE,
            'DEV_MODE' => (int) conf('DEV_MODE'),
            'REWRITE_MODE' => (int) tpl()->REWRITE_MODE,
            'DEBUG_CONSOLE_POPUP' => (int) conf('DEBUG_CONSOLE_POPUP'),
            'CACHE_USE' => (int) $cache_use,
            'CACHE_NO_CACHE' => (int) cache()->NO_CACHE,
            'CACHE_NO_WHY' => cache()->_NO_CACHE_WHY,
            'CACHE_DRIVER' => cache()->DRIVER,
            'CACHE_NS' => cache()->CACHE_NS,
            'CACHE_TTL' => (int) cache()->TTL,
            'YF_PATH' => YF_PATH,
            'YF_VERSION' => $this->_get_yf_version(),
            'APP_PATH' => APP_PATH,
            'APP_VERSION' => $this->_get_app_version(),
            'PROJECT_PATH' => PROJECT_PATH,
            'SITE_PATH' => SITE_PATH,
            'ADMIN_SITE_PATH' => ADMIN_SITE_PATH,
            'CONFIG_PATH' => CONFIG_PATH,
            'STORAGE_PATH' => STORAGE_PATH,
            'LOGS_PATH' => LOGS_PATH,
            'UPLOADS_PATH' => UPLOADS_PATH,
            'WEB_PATH' => WEB_PATH,
            'MEDIA_PATH' => MEDIA_PATH,
            'ADMIN_WEB_PATH' => ADMIN_WEB_PATH,
            'CSS_FRAMEWORK' => conf('css_framework') ?: 'bs3',
            'BOOTSTRAP_THEME' => common()->bs_current_theme() ?: $_COOKIE['yf_theme'] ?: (conf('DEF_BOOTSTRAP_THEME_' . strtoupper(MAIN_TYPE)) ?: conf('DEF_BOOTSTRAP_THEME')),
            'TPL_DRIVER' => tpl()->DRIVER_NAME,
            'TPL_COMPILE' => (int) tpl()->COMPILE_TEMPLATES,
            'TPL_THEMES_PATH' => tpl()->_THEMES_PATH,
            'TPL_PATH' => tpl()->TPL_PATH,
            'TPL_SKIN' => conf('theme'),
            'TPL_INHERIT_SKIN' => (string) tpl()->INHERIT_SKIN,
            'TPL_INHERIT_SKIN2' => (string) tpl()->INHERIT_SKIN2,
            'MAIN_HOSTNAME' => main()->HOSTNAME,
            'SITE_ID' => (int) conf('SITE_ID'),
            'SERVER_ID' => (int) conf('SERVER_ID'),
            'SERVER_ROLE' => _prepare_html(conf('SERVER_ROLE')),
            'SERVER_ADDR' => $_SERVER['SERVER_ADDR'],
            'SERVER_SELF_IPS' => implode(', ', (array) main()->_server_self_ips),
            'USER_ID' => (int) main()->USER_ID,
            'USER_GROUP' => (int) main()->USER_GROUP,
            'USER_ROLE' => main()->USER_ROLE,
            'IS_POST' => (int) main()->is_post(),
            'IS_AJAX' => (int) main()->is_ajax(),
            'IS_HTTPS' => (int) main()->is_https(),
            'IS_CONSOLE' => (int) main()->is_console(),
            'IS_REDIRECT' => (int) main()->is_redirect(),
            'IS_COMMON_PAGE' => (int) main()->is_common_page(),
            'IS_UNIT_TEST' => (int) main()->is_unit_test(),
            'IS_SPIDER' => (int) conf('IS_SPIDER'),
            'NO_GRAPHICS' => (int) main()->NO_GRAPHICS,
            'HTTP_HOST' => $_SERVER['HTTP_HOST'],
            'SERVER_PORT' => $_SERVER['SERVER_PORT'],
            'REWRITE_DEF_HOST' => _class('rewrite')->DEFAULT_HOST,
            'REWRITE_DEF_PORT' => _class('rewrite')->DEFAULT_PORT,
            'WEB_DOMAIN' => WEB_DOMAIN,
            'REQUEST_URI' => $_SERVER['REQUEST_URI'],
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
            'OUTPUT_CACHING' => (int) main()->OUTPUT_CACHING,
            'NO_CACHE_HEADERS' => (int) main()->NO_CACHE_HEADERS,
            'LANG_SELECTED_FROM' => $locale_debug['lang_selected'] . ' (' . $locale_debug['lang_variants'][$locale_debug['lang_selected']] . ')',
            'LANG_PRIORITIES' => implode(', ', (array) $locale_debug['lang_priorities']),
            'LANG_VARIANTS' => $locale_debug['lang_variants'],
            'LOCALE_CURRENT' => $locale_debug['current'],
            'LOCALE_DEFAULT' => $locale_debug['default'],
            'LOCALE_LC_VARIANTS' => implode(', ', (array) $locale_debug['lc_variants']['LC_ALL']),
            'LOCALE_SYSTEM' => implode(', ', (array) $locale_debug['system']),
            'HTTP_IN_HEADERS' => $this->_get_request_headers(),
            'HTTP_OUT_HEADERS' => headers_list(),
        ];
        foreach ((array) $this->_get_debug_data('_DEBUG_META') as $k => $v) {
            $data['yf']['META_' . strtoupper($k)] = $v;
        }

        $ini_all = ini_get_all();
        $ini = [
            'memory_limit',
            'max_execution_time',
            'default_socket_timeout',
            'max_input_time',
            'memory_limit',
            'post_max_size',
            'upload_max_filesize',
            'file_uploads',
            'allow_url_fopen',
            'error_reporting',
            'display_errors',
        ];
        foreach ($ini as $name) {
            $data['ini']['php_ini&nbsp;:&nbsp;' . $name] = $ini_all[$name]['local_value'];
        }
        if (tpl()->COMPRESS_OUTPUT && ! main()->NO_GRAPHICS) {
            $c_info = $this->_get_debug_data('compress_output');

            $data['ini'] += [
                'compress: size original' => $c_info['size_original'] . ' bytes',
                'compress: size compressed' => $c_info['size_compressed'] . ' bytes',
                'compress: ratio' => ($c_info['size_compressed'] ? round($c_info['size_original'] / $c_info['size_compressed'] * 100, 0) : 0) . '%',
            ];
        }
        $loaded_exts = [];
        foreach (get_loaded_extensions() as $v) {
            $loaded_exts[] = strtolower($v);
        }
        asort($loaded_exts);

        $data['ini'] += [
            'memory_usage' => function_exists('memory_get_usage') ? memory_get_usage() : 'n/a',
            'memory_peak_usage' => function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : 'n/a',
            'sys_loadavg' => implode(' | ', sys_getloadavg()),
            'db_server_version' => db()->get_server_version(),
            'db_host_info' => db()->get_host_info(),
            'php_version' => phpversion(),
            'php_sapi_name' => php_sapi_name(),
            'php_current_user' => get_current_user(),
            'php_uname' => php_uname(),
            'php_include_path' => get_include_path(),
            'php_loaded_extensions' => implode(', ', $loaded_exts),
            'php_ini_scanned_files' => function_exists('php_ini_scanned_files') ? php_ini_scanned_files() : '',
        ];
        $data['session']['session_id'] = session_id();
        foreach ((array) ini_get_all('session') as $k => $v) {
            $data['session'][$k] = $v['local_value'];
        }
        $a = $_POST + $_SESSION;
        $body .= form($a, ['action' => url('/test/change_debug'), 'class' => 'form-inline', 'style' => 'padding-left:20px;'])
            ->row_start()
            ->container('Locale edit')
            ->active_box('locale_edit', ['selected' => $_SESSION['locale_vars_edit']])
            ->container('<span style="padding-left:20px;">Debug console light</span>')
            ->active_box('debug_console_light', ['selected' => $_SESSION['debug_console_light']])
            ->row_end()
            ->save(['class' => 'btn btn-default btn-mini btn-xs']);
        foreach ($data as $name => $_data) {
            foreach ($_data as $k => $v) {
                if ($name == 'yf' && ($k == 'YF_VERSION' || $k == 'APP_VERSION')) {
                    continue;
                }
                $_data[$k] = _prepare_html($v);
            }
            $body .= '<div class="span6 col-md-6">' . $this->_show_key_val_table($_data, ['no_total' => 1, 'no_sort' => 1, 'no_escape' => 1]) . '</div>';
        }
        return $body;
    }


    public function _debug_db(&$params = [])
    {
        if (! $this->SHOW_DB_QUERY_LOG) {
            return false;
        }
        $items = [];
        $instances_trace = $this->_get_debug_data('db_instances_trace');
        foreach ((array) $this->_get_debug_data('db_instances') as $k => $db) {
            $connect_trace = [];
            if (isset($instances_trace[$k])) {
                $connect_trace = $instances_trace[$k];
            }
            $name = $db->DB_TYPE . ' | ' . $db->DB_USER . ' | ' . $db->DB_HOST . ($db->DB_PORT ? ':' . $db->DB_PORT : '') . ' | ' . $db->DB_NAME;
            $items[$name] = $this->_do_debug_db_connection_queries($db, $connect_trace);
            $items['db_shutdown_queries_' . $name] = $this->_show_db_shutdown_queries($db);
            $items['db_stats_' . $name] = $this->_show_db_stats($db);
        }
        return $items ? _class('html')->tabs($items, ['hide_empty' => 1]) : null;
    }


    public function _debug_redis(&$params = [])
    {
        if (! $this->SHOW_REDIS_INFO) {
            return '';
        }
        $instances = [
            'redis_default' => redis(),
            'redis_cache' => strpos(strtolower(cache()->DRIVER), 'redis') !== false ? (cache()->_driver->_connection ?? null) : null,
        ];
        $counter = 0;
        $tabs = [];
        foreach ((array) $instances as $iname => $instance) {
            if (! $instance || ! $instance->log || ($iname != 'redis_default' && $instance === $instances['redis_default'])) {
                continue;
            }
            $items = [];
            $totals = [];
            foreach ((array) $instance->log as $k => $v) {
                $items[$counter] = [
                    'id' => ++$counter,
                    'func' => a('https://redis.io/commands/' . $v['func'], $v['func']),
                    'args' => $v['args'] ? '<pre><small>' . _prepare_html(substr(implode(PHP_EOL, $v['args'] ?? []), 0, 1000)) . '</small></pre>' : '',
                    'result' => $v['result'] ? '<pre><small>' . _prepare_html($this->_var_export($v['result'])) . '</small></pre>' : null,
                    'time' => round($v['exec_time'], 5),
                    'trace' => _prepare_html($v['trace']),
                ];
                $totals['time'] += $v['exec_time'];
            }
            $items = $this->_time_count_changes($items);
            $items[-1] = ['id' => 'TOTAL', 'time' => round($totals['time'], 5)] + array_map(function () {}, last($items));

            $logs = $this->_show_auto_table($items, [
                'hidden_map' => ['trace' => 'args', 'result' => 'time'],
                'no_total' => 1,
                'skip_empty_values' => 1,
                'caption' => $iname,
                'tr' => function ($row, $id, $rid) {
                    return $id == -1 ? ['class' => 'active'] : [];
                },
            ]);
            $tabs[$iname] = '<div class="col-md-8">' . $logs . '</div>'
                . '<div class="col-md-4">' . $this->_show_key_val_table($instance->info(), ['no_total' => 1, 'skip_empty_values' => 1]) . '</div>';
        }
        return $tabs ? _class('html')->tabs($tabs, ['hide_empty' => 1]) : null;
    }


    public function _debug_memcached(&$params = [])
    {
        if (! $this->SHOW_MEMCACHED_INFO) {
            return '';
        }
        if (strpos(strtolower(cache()->DRIVER), 'memcache') === false) {
            return '';
        }
        $mc_obj = cache_memcached_connect();
        if (! is_object($mc_obj)) {
            return '';
        }
        $body = '';
        $data = [];
        $ext = '';
        if (method_exists($mc_obj, 'getExtendedStats')) {
            $ext = 'memcache (old)';
            $data = $mc_obj->getExtendedStats();
        } elseif (method_exists($mc_obj, 'getStats')) {
            $ext = 'memcached (new)';
            $data = $mc_obj->getStats();
        }
        if (! $data) {
            return 'n/a';
        }
        $body .= 'PHP Extension used: ' . $ext . '<br>' . PHP_EOL;
        foreach ($data as $name => $_data) {
            $body .= '<div class="span6 col-md-6">' . $name . '<br>' . $this->_show_key_val_table($_data, ['no_total' => 1, 'skip_empty_values' => 1]) . '</div>';
        }
        return $body;
    }


    public function _debug_stpls(&$params = [])
    {
        if (! $this->SHOW_STPLS) {
            return '';
        }
        $data = _class('tpl')->driver->CACHE;
        $debug = _class('tpl')->driver->debug;
        if ($this->SORT_TEMPLATES_BY_NAME && ! empty($data)) {
            ksort($data);
        }
        $stpl_vars = $this->_get_debug_data('STPL_REPLACE_VARS');
        $stpl_traces = $this->_get_debug_data('STPL_TRACES');

        $total_size = 0;
        $total_stpls_exec_time = 0;
        $counter = 0;

        $items = [];
        foreach ((array) $data as $k => $v) {
            if (empty($v['calls'])) {
                continue;
            }
            $cur_size = strlen($v['string']);
            $total_size += $cur_size;
            $total_stpls_exec_time += (float) $v['exec_time'];

            $items[$counter] = [
                'id' => ++$counter,
                'name' => $this->_admin_link('edit_stpl', $k, false, ['{LOCATION}' => $debug[$k]['storage'] ?? '']),
                'calls' => (string) ($v['calls']),
                'driver' => (string) ($v['driver']),
                'compiled' => (int) $v['is_compiled'],
                'storage' => (string) ($debug[$k]['storage'] ?? ''),
                'storages' => '<pre>' . _prepare_html($this->_var_export($debug[$k]['storages'] ?? '')) . '</pre>',
                'size' => (string) $cur_size,
                'time' => round($v['exec_time'], 4),
                'trace' => _prepare_html($stpl_traces[$k]),
            ];
            if (isset($stpl_vars[$counter])) {
                $items[$counter]['vars'] = '<pre><small>' . _prepare_html($this->_var_export($stpl_vars[$counter])) . '</small></pre>';
            }
        }
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, [
            'first_col_width' => '1%',
            'caption' => [
                'tpl_driver' => tpl()->DRIVER_NAME,
                'compile_mode' => (int) tpl()->COMPILE_TEMPLATES,
                'templates_size' => $total_size . ' bytes',
            ],
            'hidden_map' => [
                'trace' => 'name',
                'vars' => 'name',
                'storages' => 'name',
            ],
        ]);
    }


    public function _debug_rewrite(&$params = [])
    {
        if (! $this->SHOW_REWRITE_INFO) {
            return '';
        }
        $data = $this->_get_debug_data('rewrite');
        if (empty($data)) {
            return '';
        }
        $items = [];
        foreach ((array) $data as $k => $v) {
            $items[] = [
                'id' => $k + 1,
                'source' => (string) ($v['source']),
                'rewrited' => (string) ($this->_admin_link('link', $v['rewrited'])),
                'time' => round($v['exec_time'], 4),
                'trace' => $v['trace'],
            ];
        }
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, [
            'first_col_width' => '1%',
            'caption' => [
                'Rewrite processing time' => round($this->_get_debug_data('rewrite_exec_time'), 4),
            ],
            'hidden_map' => [
                'trace' => 'source',
            ],
        ]);
    }


    public function _debug_url(&$params = [])
    {
        if (! $this->SHOW_REWRITE_INFO) {
            return '';
        }
        $items = $this->_get_debug_data('_url');
        foreach ((array) $items as $k => $v) {
            $items[$k]['time'] = round($v['time'], 4);
            $items[$k]['rewrited_link'] = (string) ($this->_admin_link('link', $v['rewrited_link']));
        }
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'params']]);
    }


    public function _debug_modules(&$params = [])
    {
        if (! $this->SHOW_LOADED_MODULES) {
            return '';
        }
        $counter = 0;
        $items = [];
        foreach ((array) $this->_get_debug_data('main_load_class') as $data) {
            $items[] = [
                'id' => ++$counter,
                'module' => $data['class_name'],
                'loaded_class' => $data['loaded_class_name'],
                'path' => $this->_admin_link('edit_file', $data['loaded_path']),
                'size' => file_exists($data['loaded_path']) ? filesize($data['loaded_path']) : '',
                'storage' => $data['storage'],
                'time' => round($data['time'], 4),
                'trace' => $data['trace'],
                'storages' => '<pre>' . _prepare_html($this->_var_export($data['storages'])) . '</pre>',
            ];
        }
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'path', 'storages' => 'path']]);
    }


    public function _debug_execute(&$params = [])
    {
        if (! $this->SHOW_MAIN_EXECUTE) {
            return '';
        }
        $items = $this->_get_debug_data('main_execute_block_time');
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, ['first_col_width' => '1%', 'hidden_map' => ['trace' => 'params']]);
    }


    public function _debug_get_data(&$params = [])
    {
        if (! $this->SHOW_MAIN_GET_DATA) {
            return '';
        }
        $items = (array) $this->_get_debug_data('main_get_data');
        foreach ($items as $k => $v) {
            $data = $this->_var_export($v['data']);
            $size = $v['data'] === null ? 'NULL' : strlen($data);
            $items[$k]['data'] = '<pre><small>' . _prepare_html(substr($data, 0, 1000)) . '</small></pre>';
            $items[$k]['data_size'] = $size;
        }
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'params', 'data' => 'name']]);
    }


    public function _debug_cache_get(&$params = [])
    {
        if (! $this->SHOW_CORE_CACHE) {
            return '';
        }
        // TODO + add admin link to purge cache
        // TODO + add link to inspect current cache contents if driver supports this
        $items = (array) $this->_get_debug_data('cache_get');
        foreach ($items as $k => $v) {
            $data = $this->_var_export($v['data']);
            $size = $v['data'] === null ? 'NULL' : strlen($data);
            $items[$k]['data'] = '<pre><small>' . _prepare_html(substr($data, 0, 1000)) . '</small></pre>';
            $items[$k]['data_size'] = $size;
        }
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'params', 'data' => 'name']]);
    }


    public function _debug_cache_set(&$params = [])
    {
        if (! $this->SHOW_CORE_CACHE) {
            return '';
        }
        $items = (array) $this->_get_debug_data('cache_set');
        foreach ($items as $k => $v) {
            $data = $this->_var_export($v['data']);
            $size = $v['data'] === null ? 'NULL' : strlen($data);
            $items[$k]['data'] = '<pre><small>' . _prepare_html(substr($data, 0, 1000)) . '</small></pre>';
            $items[$k]['data_size'] = $size;
        }
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'name', 'data' => 'name']]);
    }


    public function _debug_cache_del(&$params = [])
    {
        if (! $this->SHOW_CORE_CACHE) {
            return '';
        }
        $items = $this->_get_debug_data('cache_del');
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'name']]);
    }


    public function _debug_input(&$params = [])
    {
        if (! $this->SHOW_INPUT_DATA) {
            return '';
        }
        $body = [];
        foreach (['GET', 'POST', 'FILES', 'COOKIE', 'SERVER', 'ENV'] as $name) {
            $enabled_var = 'SHOW_' . $name . '_DATA';
            $enabled = $this->$enabled_var;
            if ($enabled) {
                $val = '_' . $name;
                $out = $this->_show_key_val_table($GLOBALS[$val]);
                $items = $this->_get_debug_data('input_' . strtolower($name));
                $items && $out .= $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'name']]);
                $body[] = '<div class="col-md-12"><h3>_' . $name . '</h3>' . $out . '</div>';
            }
        }
        return implode(PHP_EOL, $body);
    }


    public function _debug__session(&$params = [])
    {
        if (! $this->SHOW_SESSION_DATA) {
            return '';
        }
        $items = $_SESSION;
        foreach ((array) $items as $k => $v) {
            $items[$k] = [
                'key' => $k,
                'value' => '<pre>' . _prepare_html($this->_var_export($v)) . '</pre>',
            ];
        }
        $out = '<div class="col-md-6">' . $this->_show_auto_table($items, ['first_col_width' => '1%']) . '</div>';

        $data['session_id'] = session_id();
        foreach ((array) ini_get_all('session') as $k => $v) {
            $data[$k] = $v['local_value'];
        }
        foreach ($data as $k => $v) {
            $data[$k] = _prepare_html($v);
        }
        $out .= '<div class="col-md-6">' . $this->_show_key_val_table($data, ['no_total' => 1, 'no_sort' => 1, 'no_escape' => 1]) . '</div>';

        $items = $this->_get_debug_data('input_session');
        $items && $out .= '<div class="col-md-6">' . $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'name']]) . '</div>';

        return $out;
    }


    public function _debug_i18n(&$params = [])
    {
        if (! $this->SHOW_I18N_VARS) {
            return '';
        }
        $i = 0;
        $calls = _class('i18n')->_calls;
        $items = (array) $this->_get_debug_data('i18n');
        foreach ($items as $k => $v) {
            $v['name'] = $this->_admin_link('edit_i18n', $v['name']);
            $v['calls'] = (int) $calls[$v['name_orig']];
            $items[$k] = ['id' => ++$i] + $v;
        }
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'name', 'data' => 'name']]);
    }


    public function _debug_sphinxsearch(&$params = [])
    {
        if (! $this->SHOW_SPHINX) {
            return '';
        }
        $items = $this->_get_debug_data('sphinxsearch');
        if (! $items) {
            return '';
        }
        $body = '';
        $body .= 'host: ' . _class('sphinxsearch')->_get_host();
        $body .= ', version: ' . _class('sphinxsearch')->_get_server_version();

        $sphinx_connect_debug = [];
        foreach ((array) $items as $id => $item) {
            if ($item['query'] == 'sphinx connect') {
                $sphinx_connect_debug = $item;
                unset($items[$id]);
                continue;
            }
            $item['time'] = round($item['time'], 4);
            $item['results'] = '<pre>' . _prepare_html($this->_var_export($item['results'])) . '</pre>';
            $item['meta'] = '<pre>' . _prepare_html($this->_var_export($item['meta'])) . '</pre>';
            $item['describe'] = '<pre>' . _prepare_html($this->_var_export($item['describe'])) . '</pre>';
            $items[$id] = ['id' => $id] + $item;
        }
        $items = $this->_time_count_changes($items);

        $body .= $this->_show_auto_table($items, ['first_col_width' => '1%', 'hidden_map' => ['trace' => 'query', 'meta' => 'count', 'describe' => 'count', 'results' => 'count']]);
        $body .= $sphinx_connect_debug ? '<pre>' . _prepare_html($this->_var_export($sphinx_connect_debug)) . '</pre>' : '';
        $body .= $this->_show_key_val_table(_class('sphinxsearch')->_get_server_status());
        return $body;
    }


    public function _debug_ssh(&$params = [])
    {
        if (! $this->SHOW_SSH) {
            return '';
        }
        // Need to enable only when ssh was used
        if (! isset(main()->modules['ssh'])) {
            return '';
        }
        return $this->_show_key_val_table(_class('ssh')->_debug);
    }


    public function _debug_apc(&$params = [])
    {
        if (! $this->SHOW_APC_INFO || ! function_exists('apc_cache_info')) {
            return '';
        }
        $data = apc_cache_info();
        foreach ((array) ini_get_all('apc') as $_k => $_v) {
            $data[$_k] = $_v['local_value'];
        }
        return $this->_show_key_val_table($data);
    }


    public function _debug_xcache(&$params = [])
    {
        if (! $this->SHOW_XCACHE_INFO || ! function_exists('xcache_get')) {
            return '';
        }
        $ini_names = 'cacher optimizer coverager admin.enable_auth size count slots ttl gc_interval stat var_size var_count var_slots var_ttl var_maxttl var_gc_interval coverager_autostart';
        foreach (explode(' ', $ini_names) as $name) {
            $name = 'xcache.' . $name;
            $data[$name] = ini_get($name);
        }
        return $this->_show_key_val_table($data);
    }


    public function _debug_resize_images(&$params = [])
    {
        if (! $this->SHOW_RESIZED_IMAGES_LOG || empty($GLOBALS['_RESIZED_IMAGES_LOG'])) {
            return '';
        }
        return $this->_show_auto_table($GLOBALS['_RESIZED_IMAGES_LOG']);
    }


    public function _debug_globals(&$params = [])
    {
        if (! $this->SHOW_GLOBALS) {
            return '';
        }
        $classes_builtin = [];
        $classes_custom = [];
        foreach (get_declared_classes() as $k => $name) {
            $r = new ReflectionClass($name);
            $file_name = $r->getFileName();
            if (! $file_name) {
                $classes_builtin[$name] = '<built-in class>';
            } else {
                $classes_custom[$name] = $file_name . ':' . $r->getStartLine();
            }
        }
        ksort($classes_builtin);
        $data['classes'] = $classes_custom + $classes_builtin;

        $funcs = get_defined_functions();
        foreach ((array) $funcs['user'] as $k => $name) {
            $r = new ReflectionFunction($name);
            $data['functions'][$name] = $r->getFileName() . ':' . $r->getStartLine();
        }
        ksort($data['functions']);

        $data['constants'] = get_defined_constants(true);
        $data['constants'] = array_keys($data['constants']['user']); // Compatibility with PHP 5.3
        sort($data['constants']);

        $data['globals'] = array_filter(array_keys($GLOBALS), function ($v) {
            return $v[0] != '_';
        });
        sort($data['globals']);

        $body = '';
        $i = 0;
        $grid = [5, 4, 1, 2];
        foreach ($data as $name => $_data) {
            $grid_num = $grid[++$i - 1];
            $body .= '<div class="span' . $grid_num . ' col-md-' . $grid_num . '">' . $name . '<br>' . $this->_show_key_val_table($_data, ['no_total' => 1, 'no_sort' => 1]) . '</div>';
        }
        return $body;
    }


    public function _debug_included(&$params = [])
    {
        if (! $this->SHOW_INCLUDED_FILES) {
            return '';
        }
        $body = '';
        $total_size = 0;
        $i = 0;
        $items = (array) $this->_get_debug_data('included_files');
        foreach ($items as $k => $v) {
            if (! $v['exists']) {
                unset($items[$k]);
                continue;
            }
            $v['path'] = $this->_admin_link('edit_file', $v['path']);
            $items[$k] = ['id' => ++$i] + $v;
            $total_size += $v['size'];
        }
        $items = $this->_time_count_changes($items);
        return $body . $this->_show_auto_table($items, ['caption' => ['total_size' => $total_size], 'hidden_map' => ['trace' => 'path']]);
    }


    public function _debug_curl_requests(&$params = [])
    {
        if (! $this->SHOW_CURL_REQUESTS) {
            return '';
        }
        $items = $this->_get_debug_data('curl_get_remote_page');
        foreach ((array) $items as $k => $v) {
            $items[$k] = [
                'id' => $k + 1,
                'info' => '<pre>' . _prepare_html($this->_var_export($v['info'])) . '</pre>',
                'trace' => $v['trace'],
            ];
        }
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'id']]);
    }


    public function _debug_form2(&$params = [])
    {
        if (! $this->SHOW_FORM2) {
            return '';
        }
        $i = 0;
        $items = $this->_get_debug_data('form2');
        foreach ((array) $items as $k => $v) {
            $v['params'] = '<pre>' . _prepare_html($this->_var_export($v['params'])) . '</pre>';
            $v['fields'] = '<pre>' . _prepare_html($this->_var_export($v['fields'])) . '</pre>';
            $items[$k] = ['id' => ++$i] + $v;
        }
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'params', 'fields' => 'params']]);
    }


    public function _debug_table2(&$params = [])
    {
        if (! $this->SHOW_TABLE2) {
            return '';
        }
        $i = 0;
        $items = $this->_get_debug_data('table2');
        foreach ((array) $items as $k => $v) {
            $v['params'] = '<pre>' . _prepare_html($this->_var_export($v['params'])) . '</pre>';
            $v['fields'] = '<pre>' . _prepare_html($this->_var_export($v['fields'])) . '</pre>';
            $v['buttons'] = '<pre>' . _prepare_html($this->_var_export($v['buttons'])) . '</pre>';
            if ($v['header_links']) {
                $v['header_links'] = '<pre>' . _prepare_html($this->_var_export($v['header_links'])) . '</pre>';
            }
            if ($v['footer_links']) {
                $v['footer_links'] = '<pre>' . _prepare_html($this->_var_export($v['footer_links'])) . '</pre>';
            }
            $items[$k] = ['id' => ++$i] + $v;
        }
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'header_links', 'fields' => 'header_links', 'buttons' => 'header_links']]);
    }


    public function _debug_dd_table(&$params = [])
    {
        if (! $this->SHOW_DD_TABLE) {
            return '';
        }
        $i = 0;
        $items = $this->_get_debug_data('dd_table');
        foreach ((array) $items as $k => $v) {
            $v['fields'] = '<pre>' . _prepare_html($this->_var_export($v['fields'])) . '</pre>';
            $v['extra'] = '<pre>' . _prepare_html($this->_var_export($v['extra'])) . '</pre>';
            if ($v['field_types']) {
                $v['field_types'] = '<pre>' . _prepare_html($this->_var_export($v['field_types'])) . '</pre>';
            }
            $items[$k] = ['id' => ++$i] + $v;
        }
        $items = $this->_time_count_changes($items);
        return $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'fields']]);
    }


    public function _debug_profiling(&$params = [])
    {
        $all_timings = main()->_timing;
        if (! $all_timings) {
            return false;
        }
        $ts = main()->_time_start;
        $_last_item = end($all_timings);
        $time_all = $_last_item[0] - $ts;
        $items = [];
        foreach ((array) $all_timings as $i => $v) {
            $time_offset = $v[0] - $ts;
            $time_change = '';
            $time_change_p = '';
            if (isset($all_timings[$i + 1])) {
                $time_change = $all_timings[$i + 1][0] - $v[0];
            }
            $time_warning = false;
            if ($time_change > 0.001) {
                $time_change_p = round(100 - (($time_all - $time_change) / $time_all * 100), 1);
                if ($time_change_p >= 5) {
                    $time_warning = true;
                }
            }
            $items[] = [
                'i' => $i,
                'time_offset' => round($time_offset, 4),
                'time_change' => $time_change && $time_change > 0.0001 ? round($time_change, 4) : '',
                'time_change_p' => $time_change_p ? '<span class="' . ($time_warning ? 'label label-warning' : '') . '">' . $time_change_p . '%</span>' : '',
                'class' => $v[1],
                'method' => $v[2],
                'trace' => $v[3],
                'args' => $v[4] ? _prepare_html($this->_var_export($v[4])) : '',
            ];
        }
        return $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'args']]);
    }


    public function _debug_events(&$params = [])
    {
        $main_ts = main()->_time_start;
        $items = [];
        foreach (['listen', 'fire', 'queue'] as $name) {
            $data = $this->_get_debug_data('events_' . $name);
            if (! $data) {
                continue;
            }
            foreach ((array) $data as $k => $v) {
                $data[$k]['time_offset'] = round($data[$k]['time_offset'] - $main_ts, 5);
            }
            $items[$name] = $this->_show_auto_table($data, ['header' => $name, 'no_total_time' => 1, 'hidden_map' => ['trace' => 'name']]);
        }
        return _class('html')->tabs($items, ['hide_empty' => 1, 'show_all' => 1, 'no_headers' => 1]);
    }


    public function _debug_hooks(&$params = [])
    {
        $items = [];
        $hook_name = '_hook_debug';
        foreach (main()->modules as $module_name => $module_obj) {
            if (! method_exists($module_obj, $hook_name)) {
                continue;
            }
            $items[$module_name] = $module_obj->$hook_name($this);
        }
        return $this->_show_key_val_table($items);
    }


    public function _debug_dashboard()
    {
        if (! $this->SHOW_DB_STATS) {
            return '';
        }
        $items = $this->_get_debug_data('dashboard');
        if (! isset($items) || ! isset($items['widgets'])) {
            return false;
        }
        $loaded_modules = $this->backup_debug_data['main_load_class'];
        foreach ((array) $items['widgets'] as $key => $value) {
            $_items[$key]['class_name'] = $value['class_name'];
            $_items[$key]['action'] = $value['action'];
            foreach ($loaded_modules as $k => $v) {
                if (($value['class_name'] == $v['class_name'])) {
                    $_items[$key]['loaded_class_name'] = $v['loaded_class_name'];
                    $_items[$key]['storage'] = $v['storage'];
                    $_items[$key]['loaded_path'] = $v['loaded_path'];
                    break;
                }
            }
            $_items[$key]['time'] = $value['time'];
        }
        $_items = $this->_time_count_changes($_items);

        $data = '<div class="span4 col-md-4">dashboard name: <b>' . $items['name'] . '</b><br /><br />';
        $data .= 'Total time: <b>' . $items['total_time'] . '</b><br /><br />';
        $data .= $this->_show_auto_table($_items, ['hidden_map' => []]);
        $data .= '</div>';

        return $data;
    }


    public function _debug_assets(&$params = [])
    {
        $body = [];

        $items = $this->_get_debug_data('assets_out');
        $i = 0;
        foreach ((array) $items as $k => $v) {
            $v['preview'] = '<pre>' . _prepare_html(substr($v['content'], 0, 100)) . '</pre>';
            $v['content'] = '<pre>' . _prepare_html($this->_var_export($v['content'])) . '</pre>';
            $v['params'] = $v['params'] ? '<pre>' . _prepare_html($this->_var_export($v['params'])) . '</pre>' : '';
            $items[$k] = ['id' => ++$i] + $v;
            $items[$k]['strlen'] = strlen($v['content']);
        }
        $body['assets_out'] = $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'md5', 'content' => 'preview']]);

        $items = $this->_get_debug_data('assets_add');
        $i = 0;
        foreach ((array) $items as $k => $v) {
            $v['preview'] = '<pre>' . _prepare_html(substr($v['content'], 0, 100)) . '</pre>';
            $v['content'] = '<pre>' . _prepare_html($this->_var_export($v['content'])) . '</pre>';
            $v['params'] = $v['params'] ? '<pre>' . _prepare_html($this->_var_export($v['params'])) . '</pre>' : '';
            unset($v['is_added']);
            $items[$k] = ['id' => ++$i] + $v;
            $items[$k]['strlen'] = strlen($v['content']);
        }
        $body['assets_add_log'] = $this->_show_auto_table($items, ['hidden_map' => ['trace' => 'md5', 'content' => 'preview']]);

        $items = $this->_get_debug_data('assets_names');
        $i = 0;
        foreach ((array) $items as $k => $v) {
            $v['content'] = '<pre>' . _prepare_html($this->_var_export($v['content'])) . '</pre>';
            $items[$k] = ['id' => ++$i] + $v;
            $items[$k]['strlen'] = strlen($v['content']);
        }
        $body['assets_names'] = $this->_show_auto_table($items, ['hidden_map' => ['content' => 'path']]);

        return _class('html')->tabs($body, ['hide_empty' => 1]);
    }


    public function _debug_other(&$params = [])
    {
        $items = [];
        foreach (debug() as $k => $v) {
            if (isset($this->_used_debug_datas[$k])) {
                continue;
            }
            $items[$k] = $v;
        }
        return $this->_show_key_val_table($items);
    }
}
