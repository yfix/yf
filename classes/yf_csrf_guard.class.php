<?php

/**
 * CSRF protection.
 */
class yf_csrf_guard
{
    public $ENABLED = true;
    public $ENABLED_FOR = [
        'admin' => false,
        'user' => true,
    ];
    public $HASH_ALGO = 'sha256';
    public $FORM_ID = null;
    public $TOKEN_NAME = '_token';
    public $LOG_ERRORS = true;

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args, $this->_chained_mode);
    }

    /**
     * @param mixed $name
     * @param mixed $func
     */
    public function _extend($name, $func)
    {
        $this->_extend[$name] = $func;
    }

    /**
     * @param mixed $params
     */
    public function configure($params = [])
    {
        if (isset($params['form_id'])) {
            $this->FORM_ID = $params['form_id'];
        }
        if (isset($params['token_name'])) {
            $this->TOKEN_NAME = $params['token_name'];
        }
        if (isset($params['hash_algo'])) {
            $this->HASH_ALGO = $params['hash_algo'];
        }
        return $this;
    }


    public function generate()
    {
        if (function_exists('hash_algos') && in_array($this->HASH_ALGO, hash_algos())) {
            $token = hash($this->HASH_ALGO, mt_rand(0, mt_getrandmax()) . '|' . $_SERVER['REMOTE_ADDR'] . '|' . microtime());
        } else {
            $token = '';
            for ($i = 0; $i < 64; ++$i) {
                $r = mt_rand(0, 35);
                if ($r < 26) {
                    $c = chr(ord('a') + $r);
                } else {
                    $c = chr(ord('0') + $r - 26);
                }
                $token .= $c;
            }
        }
        $this->set($token);
        return $token;
    }

    /**
     * @param mixed $token_value
     */
    public function validate($token_value)
    {
        if ( ! $this->ENABLED || ! $this->ENABLED_FOR[MAIN_TYPE]) {
            return true;
        }
        $token = $this->get();
        if ($token === false) {
            $result = false;
        } elseif ($token === $token_value) {
            $result = true;
        } else {
            $result = false;
        }
        $this->del();
        return $result;
    }


    public function get()
    {
        $key = $this->FORM_ID;
        if (isset($_SESSION[$this->TOKEN_NAME][$key])) {
            return $_SESSION[$this->TOKEN_NAME][$key];
        }
        return false;
    }

    /**
     * @param mixed $value
     */
    public function set($value)
    {
        $key = $this->FORM_ID;
        $_SESSION[$this->TOKEN_NAME][$key] = $value;
        return $this;
    }


    public function del()
    {
        $key = $this->FORM_ID;
        $_SESSION[$this->TOKEN_NAME][$key] = '';
        unset($_SESSION[$this->TOKEN_NAME][$key]);
        return $this;
    }

    /**
     * @param mixed $params
     */
    public function log_error($params = [])
    {
        if ( ! $this->LOG_ERRORS) {
            return false;
        }
        $main = main();
        // slice 2 first elements (__FUNCTION__ and $this->_go) and leave only 5 more trace elements to save space
        $trace = implode(PHP_EOL, array_slice(explode(PHP_EOL, $main->trace_string()), 2, 7));
        $is_https = ($_SERVER['HTTPS'] || $_SERVER['SSL_PROTOCOL']);
        return db()->insert_safe('log_csrf_errors', [
            'form_id' => $params['form_id'],

            'date' => date('Y-m-d H:i:s'),
            'ip' => common()->get_ip(),
            'url' => ($is_https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''),
            'ua' => $_SERVER['HTTP_USER_AGENT'],
            'referer' => $_SERVER['HTTP_REFERER'],
            'object' => $_GET['object'],
            'action' => $_GET['action'],
            'user_id' => MAIN_TYPE_ADMIN ? $main->ADMIN_ID : $main->USER_ID,
            'user_group' => MAIN_TYPE_ADMIN ? $main->ADMIN_GROUP : $main->USER_GROUP,
            'site_id' => (int) $main->SITE_ID,
            'server_id' => (int) $main->SERVER_ID,

            'is_logged_in' => (bool) $main->is_logged_in(),
            'is_common_page' => $main->is_common_page(),
            'is_https' => $main->is_https(),
            'is_post' => $main->is_post(),
            'is_no_graphics' => (bool) $main->no_graphics(),
            'is_ajax' => $main->is_ajax(),
            'is_spider' => $main->is_spider(),
            'is_redirect' => $main->is_redirect(),
            'is_console' => $main->is_console(),
            'is_unit_test' => $main->is_unit_test(),
            'is_dev' => $main->is_dev(),
            'is_debug' => $main->is_debug(),
            'is_banned' => $main->is_banned(),
            'is_403' => $main->is_403(),
            'is_404' => $main->is_404(),
            'is_503' => $main->is_503(),
            'is_cache_on' => $main->is_cache_on(),
            'is_mobile' => $main->is_mobile(),

            'lang' => (string) conf('language'),
            'country' => (string) (conf('country') ?: $_SERVER['GEOIP_COUNTRY_CODE']),
            'utm_source' => (string) ($_GET['utm_source'] ?: ($_POST['utm_source'] ?: $_SESSION['utm_source'])),
            'post_data' => json_encode($_POST),
            'trace' => $trace,
            'memory' => (int) memory_get_peak_usage(),
            'exec_time' => str_replace(',', '.', round(microtime(true) - $main->_time_start, 4)),
            'num_dbq' => (int) db()->NUM_QUERIES,
            'page_size' => (int) tpl()->_output_body_length,
        ]);
    }

    /**
     * Do save.
     */
    public function go()
    {
        if ( ! $this->allow()) {
            return false;
        }
        $is = $this->is;
        $data = [
            'user_id' => (int) $_SESSION['user_id'],
            'user_group' => (int) $_SESSION['user_group'],
            'date' => time(),
            'user_agent' => (string) $_SERVER['HTTP_USER_AGENT'],
            'referer' => (string) $_SERVER['HTTP_REFERER'],
            'query_string' => (string) $_SERVER['QUERY_STRING'],
            'request_uri' => (string) $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'exec_time' => str_replace(',', '.', common()->_format_time_value($GLOBALS['time_end'] ?: microtime(true) - main()->_time_start)),
            'num_dbq' => (int) db()->NUM_QUERIES,
            'page_size' => (int) tpl()->_output_body_length,
            'memory' => (int) memory_get_peak_usage(),
            'site_id' => (int) conf('SITE_ID'),
            'ip' => (string) common()->get_ip(),
            'country' => (string) (conf('country') ?: $_SERVER['GEOIP_COUNTRY_CODE']),
            'lang' => (string) conf('language'),
            'utm_source' => (string) ($_GET['utm_source'] ?: ($_POST['utm_source'] ?: $_SESSION['utm_source'])),
            'is_common_page' => (int) $is['is_common_page'],
            'is_https' => (int) $is['is_https'],
            'is_post' => (int) $is['is_post'],
            'is_no_graphics' => (int) $is['is_no_graphics'],
            'is_ajax' => (int) $is['is_ajax'],
            'is_spider' => (int) $is['is_spider'],
            'is_redirect' => (int) $is['is_redirect'],
            'is_console' => (int) $is['is_console'],
            'is_unit_test' => (int) $is['is_unit_test'],
            'is_dev' => (int) $is['is_dev'],
            'is_debug' => (int) $is['is_debug'],
            'is_banned' => (int) $is['is_banned'],
            'is_403' => (int) $is['is_403'],
            'is_404' => (int) $is['is_404'],
            'is_503' => (int) $is['is_503'],
            'is_cache_on' => (int) $is['is_cache_on'],
//			'is_mobile'		=> (int)$is['is_mobile'],
        ];
        if (in_array('db', $this->LOG_DRIVER)) {
            $sql = db()->insert_safe('log_exec', $data);
            db()->_add_shutdown_query($sql);
        }
        if (in_array('file', $this->LOG_DRIVER)) {
            $data['output_cache'] = '0';  // mean: exec full mode (not from output cache)
            $log_file_path = STORAGE_PATH . $this->LOG_DIR_NAME . 'log_exec_' . gmdate('Y-m-d') . '.log';
            $log_dir_path = dirname($log_file_path);
            if ( ! file_exists($log_dir_path)) {
                _mkdir_m($log_dir_path);
            }
            file_put_contents($log_file_path, implode('#@#', $data) . PHP_EOL, FILE_APPEND);
        }
    }
}
