<?php

/**
 * Redirects handler.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_redirect
{
    /** @var array @conf_skip Available redirect types */
    public $AVAIL_TYPES = [
        'html',
        'js',
        'http',
        'refresh',
        'hybrid',
    ];
    /** @var bool */
    public $USE_DESIGN = false;
    /** @var bool */
    public $JS_SHOW_TEXT = false;
    /** @var string Force using only this method (if text is empty), leave blank to disable */
    public $FORCE_TYPE = 'http';
    /** @var bool */
    public $LOG_REDIRECTS = true;
    /** @var bool */
    public $LOOP_DEFENCE = true;
    /** @var bool */
    public $LOOP_COUNT = 3;
    /** @var bool */
    public $LOOP_KEEP_LAST = 10;
    /** @var bool */
    public $LOOP_TTL = 5;
    /** @var array of url patterns to exclude from defence */
    public $LOOP_EXCLUDE_SOURCE = [
        '~^/[a-z0-9_]+/filter_save/~i',
        '~&action=filter_save&~i',
    ];
    /** @var array of url patterns to exclude from defence */
    public $LOOP_EXCLUDE_TARGET = [
    ];

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
     * @param mixed $cur_url
     */
    public function _loop_detected($cur_url)
    {
        if ( ! $this->LOOP_DEFENCE) {
            return false;
        }
        $cur_time = time();
        $loop_var = &$_SESSION['last_redirect_log'];
        if ( ! is_array($loop_var)) {
            $loop_var = [];
        }
        $loop_var[] = [
            'time' => $cur_time,
            'url' => $cur_url,
        ];
        $detected = false;
        $loop_count = $this->LOOP_COUNT;
        $detect_slice = array_reverse(array_slice((array) $loop_var, -$loop_count, $loop_count, true));
        if (count((array) $detect_slice) < $this->LOOP_COUNT) {
            return false;
        }
        $exclude_source = $this->LOOP_EXCLUDE_SOURCE;
        if ($exclude_source && ! is_array($exclude_source)) {
            $exclude_source = [$exclude_source];
        }
        // Check current/source page for exclude patterns
        $url_path_and_query = $_SERVER['REQUEST_URI'] . (strlen($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        foreach ((array) $exclude_source as $pattern) {
            if (preg_match($pattern, $url_path_and_query)) {
                return false;
            }
        }
        // Check redirect target page for exclude patterns
        $exclude_target = $this->LOOP_EXCLUDE_TARGET;
        if ($exclude_target && ! is_array($exclude_target)) {
            $exclude_target = [$exclude_target];
        }
        $counter = 0;
        foreach ((array) $detect_slice as $v) {
            $time = $v['time'];
            $url = $v['url'];
            if ($time < ($cur_time - $this->LOOP_TTL)) {
                break;
            }
            if ($v['url'] != $cur_url) {
                break;
            }
            if ($exclude_target) {
                $u = parse_url($v['url']);
                $url_path_and_query = $u['path'] . (strlen($u['query']) ? '?' . $u['query'] : '');
                foreach ((array) $exclude_target as $pattern) {
                    if (preg_match($pattern, $url_path_and_query)) {
                        break 2;
                    }
                }
            }
            $counter++;
        }
        if ($counter >= $this->LOOP_COUNT) {
            $detected = true;
        }
        // Keep only last 10 redirects log
        $keep_last = $this->LOOP_KEEP_LAST;
        $loop_var = array_slice($loop_var, -$keep_last, $keep_last, true);

        return $detected;
    }

    /**
     * Common redirect method.
     * @param mixed $location
     * @param mixed $rewrite
     * @param mixed $redirect_type
     * @param mixed $text
     * @param mixed $ttl
     * @param mixed $params
     */
    public function _go($location, $rewrite = true, $redirect_type = 'hybrid', $text = '', $ttl = 3, $params = [])
    {
        if (is_array($location)) {
            $params += $location;
            $rewrite = isset($params['rewrite']) ? $params['rewrite'] : $rewrite;
            $redirect_type = isset($params['type']) ? $params['type'] : (isset($params['redirect_type']) ? $params['redirect_type'] : $redirect_type);
            $text = isset($params['text']) ? $params['text'] : $text;
            $ttl = isset($params['ttl']) ? $params['ttl'] : $ttl;
            $location = isset($params['location']) ? $params['location'] : $params['url'];
        }
        // Avoid rewriting by mistake
        if (substr($location, 0, 1) === '/' && substr($location, 0, 2) !== '//' && $rewrite) {
            $location = url($location);
        }
        if (strpos($location, 'http://') === 0 || strpos($location, 'https://') === 0) {
            $rewrite = false;
        }
        $form_method = in_array(strtoupper($params['form_method'] ?? ''), ['GET', 'POST']) ? strtoupper($params['form_method']) : 'GET';
        if ($GLOBALS['no_redirect'] ?? false) {
            return $text;
        }
        if (main()->_IS_REDIRECTING) {
            return false;
        }
        main()->_IS_REDIRECTING = true;
        if (empty($location)) {
            $location = './?object=' . $_GET['object']
                . ($_GET['action'] != 'show' ? '&action=' . $_GET['action'] : '')
                . ($_GET['id'] ? '&id=' . $_GET['id'] : '')
                . ($_GET['page'] ? '&page=' . $_GET['page'] : '');
        }
        if ($rewrite && tpl()->REWRITE_MODE && MAIN_TYPE_USER) {
            $location = _class('rewrite')->_rewrite_replace_links($location, true);
        }
        $location = str_replace('??', '?', $location);
        if ($location == './?') {
            $location = './';
        }
        // Exec hook before redirecting
        $hook_name = '_on_before_redirect';
        $obj = module($_GET['object']);
        if (method_exists($obj, $hook_name)) {
            $obj->$hook_name($text);
        }
        if ($this->LOOP_DEFENCE) {
            $loop_detected = $this->_loop_detected($location);
            if ($loop_detected && ! DEBUG_MODE) {
                common()->message_error('Internal error: redirect loop detected. Please contact support');
                return false;
            }
        }
        $body = '';
        main()->NO_GRAPHICS = true;
        if (DEBUG_MODE) {
            $hidden_fields = '';
            if ($form_method == 'GET') {
                $query = parse_url($location, PHP_URL_QUERY);
                $fields = [];
                if (strlen($query)) {
                    parse_str($query, $fields);
                }
                if ($fields) {
                    $tmp = [];
                    // Fix for fields sub-arrays
                    foreach ($fields as $k => $v) {
                        if (is_array($v)) {
                            foreach ($v as $k1 => $v1) {
                                if (is_array($v1)) {
                                    foreach ($v1 as $k2 => $v2) {
                                        $tmp[$k . '[' . $k1 . '][' . $k2 . ']'] = $v2;
                                    }
                                } else {
                                    $tmp[$k . '[' . $k1 . ']'] = $v1;
                                }
                            }
                        } else {
                            $tmp[$k] = $v;
                        }
                    }
                    $fields = $tmp;
                    unset($tmp);
                    $form = form($fields, ['no_form' => 1]);
                    foreach ((array) $fields as $k => $v) {
                        $form->hidden($k);
                    }
                    $hidden_fields = $form;
                }
            }
            if (in_array($redirect_type, ['http', 'hybrid', 'refresh', '301', '302'])) {
                $ttl = 0;
            }
            $body .= tpl()->parse('system/redirect', [
                'mode' => 'debug',
                'normal_mode' => $redirect_type,
                'rewrite' => (int) ((bool) $rewrite),
                'location' => $location,
                'text' => $text,
                'ttl' => (int) $ttl,
                'form_method' => $form_method,
                'hidden_fields' => $hidden_fields,
            ]);
            if ($loop_detected) {
                $body .= '<b style="color:red">Redirect loop detected!</b>';
            }
            $body .= '<pre><small>' . htmlspecialchars(main()->trace_string()) . '</small></pre>';
            if ($this->FORCE_TYPE) {
                $redirect_type = $this->FORCE_TYPE;
            }
            $this->_save_log([
                'url_to' => $location,
                'reason' => $text,
                'rewrite' => $rewrite,
                'ttl' => $ttl,
                'type' => $redirect_type,
            ]);
            return print common()->show_empty_page($body, ['full_width' => 1]);
        }
        if (empty($redirect_type) || ! in_array($redirect_type, $this->AVAIL_TYPES)) {
            $redirect_type = 'hybrid';
        }
        if ($this->FORCE_TYPE) {
            $redirect_type = $this->FORCE_TYPE;
        }
        if ($redirect_type == 'js') {
            $body = $this->_redirect_js($location, $text, $ttl, $params);
        } elseif ($redirect_type == 'html') {
            $body = $this->_redirect_html($location, $text, $ttl, $params);
        } elseif ($redirect_type == 'http' || $redirect_type == '302') {
            $body = $this->_redirect_http_302($location, $text, $ttl, $params);
        } elseif ($redirect_type == '301') {
            $body = $this->_redirect_http_301($location, $text, $ttl, $params);
        } elseif ($redirect_type == 'refresh') {
            $body = $this->_redirect_http_refresh($location, $text, $ttl, $params);
        } elseif ($redirect_type == 'hybrid') {
            $body = $this->_redirect_hybrid($location, $text, $ttl, $params);
        }
        $this->_save_log([
            'url_to' => $location,
            'reason' => $text,
            'rewrite' => $rewrite,
            'ttl' => $ttl,
            'type' => $redirect_type,
        ]);
        echo $this->USE_DESIGN && ! empty($body) ? common()->show_empty_page($body, ['full_width' => 1]) : $body;
    }

    /**
     * Hybrid redirect method (HTTP refresh + JS).
     * @param mixed $location
     * @param mixed $text
     * @param mixed $ttl
     * @param mixed $params
     */
    public function _redirect_hybrid($location, $text = '', $ttl = 0, $params = [])
    {
        $this->_redirect_http_refresh($location, $text, $ttl, $params);
        return tpl()->parse('system/redirect', [
            'mode' => 'hybrid',
            'location' => $location,
            'text' => $text,
            'ttl' => (int) $ttl,
            'form_method' => $params['form_method'],
        ]);
    }

    /**
     * JavaScript redirect method (with 'degrade gracefully' feature).
     * @param mixed $location
     * @param mixed $text
     * @param mixed $ttl
     * @param mixed $params
     */
    public function _redirect_js($location, $text = '', $ttl = 0, $params = [])
    {
        return tpl()->parse('system/redirect', [
            'mode' => 'js',
            'location' => $location,
            'text' => $text,
            'ttl' => (int) $ttl,
            'html_redirect' => $this->_redirect_html($location, $text, $ttl),
            'js_show_text' => (int) ((bool) $this->JS_SHOW_TEXT),
            'form_method' => $params['form_method'],
        ]);
    }

    /**
     * HTML redirect method.
     * @param mixed $location
     * @param mixed $text
     * @param mixed $ttl
     * @param mixed $params
     */
    public function _redirect_html($location, $text = '', $ttl = 0, $params = [])
    {
        return tpl()->parse('system/redirect', [
            'mode' => 'html',
            'location' => $location,
            'text' => $text,
            'ttl' => (int) $ttl,
            'form_method' => $params['form_method'],
        ]);
    }

    /**
     * @param mixed $location
     * @param mixed $text
     * @param mixed $ttl
     * @param mixed $params
     */
    public function _redirect_http_302($location, $text = '', $ttl = 0, $params = [])
    {
        header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1') . ' 302 Found');
        header('Location: ' . $location);
        return '';
    }

    /**
     * @param mixed $location
     * @param mixed $text
     * @param mixed $ttl
     * @param mixed $params
     */
    public function _redirect_http_301($location, $text = '', $ttl = 0, $params = [])
    {
        header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1') . ' 301 Moved Permanently');
        header('Location: ' . $location);
        return '';
    }

    /**
     * @param mixed $location
     * @param mixed $text
     * @param mixed $ttl
     * @param mixed $params
     */
    public function _redirect_http_refresh($location, $text = '', $ttl = 0, $params = [])
    {
        header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1') . ' 302 Found');
        header('Refresh: ' . $ttl . '; url=' . $location);
        return '';
    }

    /**
     * @param mixed $params
     */
    public function _save_log($params = [])
    {
        if ( ! $this->LOG_REDIRECTS) {
            return false;
        }
        // slice 2 first elements (__FUNCTION__ and $this->_go) and leave only 5 more trace elements to save space
        $trace = implode(PHP_EOL, array_slice(explode(PHP_EOL, main()->trace_string()), 2, 7));

        $is_https = ($_SERVER['HTTPS'] || $_SERVER['SSL_PROTOCOL']);

        return db()->insert_safe('log_redirects', [
            'url_from' => ($is_https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'url_to' => $params['url_to'],
            'reason' => $params['reason'],
            'use_rewrite' => (int) $params['rewrite'],
            'redirect_type' => $params['type'],
            'date' => gmdate('Y-m-d H:i:s'),
            'ip' => common()->get_ip(),
            'query_string' => $_SERVER['QUERY_STRING'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'referer' => $_SERVER['HTTP_REFERER'],
            'object' => $_GET['object'],
            'action' => $_GET['action'],
            'user_id' => MAIN_TYPE_ADMIN ? main()->ADMIN_ID : main()->USER_ID,
            'user_group' => MAIN_TYPE_ADMIN ? main()->ADMIN_GROUP : main()->USER_GROUP,
            'site_id' => (int) main()->SITE_ID,
            'server_id' => (int) main()->SERVER_ID,
            'locale' => conf('language'),
            'is_admin' => MAIN_TYPE_ADMIN ? 1 : 0,
            'rewrite_mode' => (int) tpl()->REWRITE_MODE,
            'debug_mode' => DEBUG_MODE ? 1 : 0,
            'exec_time' => str_replace(',', '.', round(microtime(true) - main()->_time_start, 4)),
            'trace' => $trace,
        ]);
    }
}
