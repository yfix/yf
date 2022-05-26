<?php

/**
 * Special methods for admin authentification.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_auth_admin
{
    /** @var string Login field name to use @conf_skip */
    public $LOGIN_FIELD = 'login';
    /** @var string Password field name to use @conf_skip */
    public $PSWD_FIELD = 'password';
    /** @var string Default user object (module) to redirect */
    public $DEF_ADMIN_MODULE = 'settings';
    /** @var string Redirect URL */
    public $URL_WRONG_LOGIN = './';
    /** @var string Redirect URL */
    public $URL_SUCCESS_LOGIN = '';
    /** @var string Redirect URL */
    public $URL_AFTER_LOGOUT = './';
    /** @var string field name @conf_skip */
    public $VAR_ADMIN_ID = 'admin_id';
    /** @var string field name @conf_skip */
    public $VAR_ADMIN_GROUP_ID = 'admin_group';
    /** @var string field name @conf_skip */
    public $VAR_ADMIN_LOGIN_TIME = 'admin_login_time';
    /** @var string field name @conf_skip */
    public $VAR_ADMIN_GO_URL = 'admin_go_url';
    /** @var string field name @conf_skip */
    public $VAR_LOCK_IP = 'admin_auth_lock_to_ip';
    /** @var string field name @conf_skip */
    public $VAR_LOCK_UA = 'admin_auth_lock_to_ua';
    /** @var string field name @conf_skip */
    public $VAR_LOCK_HOST = 'admin_auth_lock_to_host';
    /** @var array @conf_skip Methods to execute after success login or logout */
    public $EXEC_AFTER_LOGIN = [];
    /** @var array @conf_skip */
    public $EXEC_AFTER_LOGOUT = [];
    /** @var array @conf_skip */
    public $EXEC_AFTER_INIT = [];
    /** @var bool Do log into db admin login actions */
    public $DO_LOG_LOGINS = true;
    /** @var bool Save failed logins @security */
    public $LOG_FAILED_LOGINS = true;
    /** @var bool Block failed logins after several attempts (To prevent password bruteforcing, hacking, etc) @security */
    public $BLOCK_FAILED_LOGINS = false;
    /** @var bool Track failed logins TTL @security */
    public $BLOCK_FAILED_TTL = 3600;
    /** @var bool Track banned IPs list @security */
    public $BLOCK_BANNED_IPS = false;
    /** @var bool Check referer in session @security */
    public $SESSION_REFERER_CHECK = false;
    /** @var bool Lock session to IP address (to prevent hacks) @security */
    public $SESSION_LOCK_TO_IP = true;
    /** @var bool Lock session to User Agent (to prevent hacks) @security */
    public $SESSION_LOCK_TO_UA = false;
    /** @var bool Lock session to Hostname (to prevent hacks) @security */
    public $SESSION_LOCK_TO_HOST = false;
    /** @var bool Allow to login only by HTTPS protocol, else raise error @security */
    public $AUTH_ONLY_HTTPS = false;

    /**
     * Initialize auth.
     */
    public function init()
    {
        // Chanined config rule
        if ($this->BLOCK_FAILED_LOGINS) {
            $this->LOG_FAILED_LOGINS = true;
        }
        // Remember last query string to process it after succesful login
        if (empty($_SESSION[$this->VAR_ADMIN_ID]) && ($_GET['task'] ?? '') != 'login') {
            $_SESSION[$this->VAR_ADMIN_GO_URL] = $_SERVER['QUERY_STRING'] ?? '';
        }
        // Try to assign first page of the site (if $_GET['object'] is empty)
        if (empty($_GET['object'])) {
            $go = defined('SITE_DEFAULT_PAGE') ? SITE_DEFAULT_PAGE : '';
            // Check if default url is not empty and then use it
            if ( ! empty($go)) {
                $go = str_replace(['./?', './'], '', $go);
                $tmp_array = [];
                parse_str($go, $tmp_array);
                foreach ((array) $tmp_array as $k => $v) {
                    $_GET[$k] = $v;
                }
            }
        }
        if (empty($_GET['object'])) {
            $_GET['object'] = $this->DEF_ADMIN_MODULE;
        }
        // Check for session IP
        if ($this->SESSION_LOCK_TO_IP && ! empty($_SESSION[$this->VAR_ADMIN_ID]) && $_GET['task'] !== 'logout') {
            // User has changed IP, logout immediately
            $ip = common()->get_ip();
            if ( ! isset($_SESSION[$this->VAR_LOCK_IP]) || $_SESSION[$this->VAR_LOCK_IP] !== $ip) {
                trigger_error('AUTH: Attempt to use session with changed IP blocked, auth_ip:' . $_SESSION[$this->VAR_LOCK_IP] . ', new_ip:' . $ip . ', admin_id: ' . (int) ($_SESSION[$this->VAR_ADMIN_ID]), E_USER_WARNING);
                $this->_log_fail([
                    'reason' => 'auth_blocked_by_ip',
                ]);
                $_GET['task'] = 'logout';
            }
        }
        // Check for session User Agent
        if ($this->SESSION_LOCK_TO_UA && ! empty($_SESSION[$this->VAR_ADMIN_ID]) && $_GET['task'] !== 'logout') {
            // User has changed User Agent, logout immediately
            $ua = $_SERVER['HTTP_USER_AGENT'];
            if ( ! isset($_SESSION[$this->VAR_LOCK_UA]) || $_SESSION[$this->VAR_LOCK_UA] !== $ua) {
                trigger_error('AUTH: Attempt to use session with changed User Agent blocked, auth_ua:"' . $_SESSION[$this->VAR_LOCK_UA] . '", new_ua:"' . $ua . '", admin_id: ' . (int) ($_SESSION[$this->VAR_ADMIN_ID]), E_USER_WARNING);
                $this->_log_fail([
                    'reason' => 'auth_blocked_by_ua',
                ]);
                $_GET['task'] = 'logout';
            }
        }
        // Check for session hostname
        if ($this->SESSION_LOCK_TO_HOST && ! empty($_SESSION[$this->VAR_ADMIN_ID]) && $_GET['task'] !== 'logout') {
            // User has changed Host, logout immediately
            $host = $_SERVER['HTTP_HOST'];
            if ( ! isset($_SESSION[$this->VAR_LOCK_HOST]) || $_SESSION[$this->VAR_LOCK_HOST] !== $host) {
                trigger_error('AUTH: Attempt to use session with changed Host blocked, auth_host:"' . $_SESSION[$this->VAR_LOCK_HOST] . '", new_host:"' . $ua . '", admin_id: ' . (int) ($_SESSION[$this->VAR_ADMIN_ID]), E_USER_WARNING);
                $this->_log_fail([
                    'reason' => 'auth_blocked_by_host',
                ]);
                $_GET['task'] = 'logout';
            }
        }
        // Check referer matched to WEB_PATH
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($this->SESSION_REFERER_CHECK && ( ! $referer || substr($referer, 0, strlen(WEB_PATH)) != WEB_PATH) && ($_GET['task'] ?? '') !== 'logout') {
            trigger_error('AUTH: Referer not matched and session blocked, referer:' . $referer, E_USER_WARNING);
            $this->_log_fail([
                'reason' => 'auth_blocked_by_referer',
            ]);
            $_GET['task'] = 'logout';
        }
        // Process log in or log out
        if (($_GET['task'] ?? '') == 'login') {
            if (empty($_SESSION[$this->VAR_ADMIN_ID])) {
                $this->_do_login();
            } elseif (($_GET['id'] ?? '') == 'prev_info' && ! empty($_SESSION['admin_prev_info'])) {
                $this->_login_with_prev_info();
            }
        } elseif (($_GET['task'] ?? '') == 'logout') {
            $this->_do_logout();
        }
        // Store admin info inside the main module
        if ( ! empty($_SESSION[$this->VAR_ADMIN_ID]) && ! empty($_SESSION[$this->VAR_ADMIN_GROUP_ID])) {
            $main = main();
            $main->ADMIN_ID = $_SESSION[$this->VAR_ADMIN_ID];
            $main->ADMIN_GROUP = $_SESSION[$this->VAR_ADMIN_GROUP_ID];
            if ($this->ADMIN_INFO_IN_SESSION && ! empty($_SESSION[$this->VAR_ADMIN_INFO])) {
                main()->ADMIN_INFO = &$_SESSION[$this->VAR_ADMIN_INFO];
            }
        }
        $this->_exec_method_on_action('init');
    }

    /**
     * Process login.
     */
    public function _do_login()
    {
        $AUTH_LOGIN = trim($_POST[$this->LOGIN_FIELD]);
        $AUTH_PSWD = trim($_POST[$this->PSWD_FIELD]);

        if (empty($AUTH_LOGIN) || empty($AUTH_PSWD)) {
            return false;
        }
        if ($this->AUTH_ONLY_HTTPS && ! ($_SERVER['HTTPS'] || $_SERVER['SSL_PROTOCOL'])) {
            $redirect_url = '';
            if ($_SERVER['HTTP_REFERER']) {
                $redirect_url = str_replace('http://', 'https://', $_SERVER['HTTP_REFERER']);
            }
            if ( ! $redirect_url) {
                $request_uri = getenv('REQUEST_URI');
                $cur_web_path = $request_uri[strlen($request_uri) - 1] == '/' ? substr($request_uri, 0, -1) : dirname($request_uri);
                $redirect_url = 'https://' . getenv('HTTP_HOST') . str_replace(['\\', '//'], ['/', '/'], (MAIN_TYPE_ADMIN ? dirname($cur_web_path) : $cur_web_path) . '/');
            }
            return js_redirect($redirect_url);
        }
        $NEED_QUERY_DB = true;

        $CUR_IP = common()->get_ip();
        if ($this->BLOCK_BANNED_IPS) {
            if (common()->_ip_is_banned()) {
                $NEED_QUERY_DB = false;
                trigger_error('AUTH ADMIN: Attempt to login from banned IP (' . $CUR_IP . ') as "' . $AUTH_LOGIN . '" blocked', E_USER_WARNING);
                return js_redirect($this->URL_WRONG_LOGIN);
            }
        }
        if ($this->BLOCK_FAILED_LOGINS) {
            $_fails_by_login = db()->get_one('SELECT COUNT(*) AS `0` FROM ' . db('log_admin_auth_fails') . ' WHERE time > ' . (time() - $this->BLOCK_FAILED_TTL) . ' AND login="' . _es($AUTH_LOGIN) . '"');
            $_fails_by_ip = db()->get_one('SELECT COUNT(*) AS `0` FROM ' . db('log_admin_auth_fails') . ' WHERE time > ' . (time() - $this->BLOCK_FAILED_TTL) . ' AND ip="' . _es(common()->get_ip()) . '"');
            if ($_fails_by_login >= 5 || $_fails_by_ip >= 10) {
                $NEED_QUERY_DB = false;
                trigger_error('AUTH ADMIN: Attempt to login as "' . $AUTH_LOGIN . '" blocked, fails_by_login: ' . (int) $_fails_by_login . ', fails_by_ip: ' . (int) $_fails_by_ip, E_USER_WARNING);
            }
        }
        if ($NEED_QUERY_DB) {
            $admin_info = db()->query_fetch(
                'SELECT * FROM ' . db('admin') . ' WHERE ' . $this->LOGIN_FIELD . '="' . _es($AUTH_LOGIN) . '" AND active="1" '
                . ' AND (`password`="' . md5(_es($AUTH_PSWD)) . '" OR `password`="' . hash('sha256', _es($AUTH_PSWD), false) . '")'
            );
        }
        if ( ! empty($admin_info['id'])) {
            $groups = main()->get_data('admin_groups_details');
            $group_info = $groups[$admin_info['group']];
        }
        // Login is ok
        if ($admin_info['id'] && $group_info['id']) {
            ob_start();
            if ($this->DO_LOG_LOGINS) {
                _class('logs')->store_admin_auth($admin_info);
            }
            session_regenerate_id($destroy = true);
            $_SESSION[$this->VAR_ADMIN_ID] = $admin_info['id'];
            $_SESSION[$this->VAR_ADMIN_GROUP_ID] = $admin_info['group'];
            $_SESSION[$this->VAR_ADMIN_LOGIN_TIME] = time();
            $_SESSION[$this->VAR_LOCK_IP] = common()->get_ip();
            $_SESSION[$this->VAR_LOCK_UA] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION[$this->VAR_LOCK_HOST] = $_SERVER['HTTP_HOST'];
            $main = main();
            $main->_init_cur_user_info($main);

            // Auto-redirect to the page before login form if needed
            if ( ! empty($_SESSION[$this->VAR_ADMIN_GO_URL])) {
                $REDIRECT_URL = (substr($_SESSION[$this->VAR_ADMIN_GO_URL], 0, 2) != './' ? './?' : '') . $_SESSION[$this->VAR_ADMIN_GO_URL];
                // Cleanup redirect url
                $_SESSION[$this->VAR_ADMIN_GO_URL] = '';
            // Redirect user to the user default
            } elseif ( ! empty($admin_info['go_after_login'])) {
                $REDIRECT_URL = $admin_info['go_after_login'];
            // Redirect user to the group default
            } elseif ( ! empty($group_info['go_after_login'])) {
                $REDIRECT_URL = $group_info['go_after_login'];
            // Force redirect user to the default location
            } elseif ( ! empty($this->URL_SUCCESS_LOGIN)) {
                $REDIRECT_URL = $this->URL_SUCCESS_LOGIN;
            }
            if ($REDIRECT_URL) {
                js_redirect($REDIRECT_URL);
            }
            // Execute custom code
            $this->_exec_method_on_action('login');
            ob_end_flush();

        // Login is wrong
        } else {
            unset($admin_info);
            $this->_log_fail([
                'login' => $AUTH_LOGIN,
                'pswd' => $AUTH_PSWD,
                'reason' => $NEED_QUERY_DB ? 'wrong_login' : 'blocked',
            ]);
            // Force redirect if given info is wrong
            if ( ! empty($this->URL_WRONG_LOGIN)) {
                js_redirect($this->URL_WRONG_LOGIN);
            }
        }
    }

    /**
     * @param mixed $data
     */
    public function _log_fail($data = [])
    {
        if ( ! $this->LOG_FAILED_LOGINS) {
            return false;
        }
        return db()->insert_safe('log_admin_auth_fails', [
            'time' => str_replace(',', '.', microtime(true)),
            'ip' => common()->get_ip(),
            'user_id' => $data['user_id'] ?: $_SESSION[$this->VAR_ADMIN_ID],
            'login' => $data['login'],
            'pswd' => $data['pswd'],
            'reason' => $data['reason'],
            'host' => $_SERVER['HTTP_HOST'],
            'ua' => $_SERVER['HTTP_USER_AGENT'],
            'referer' => $_SERVER['HTTP_REFERER'],
            'query_string' => $_SERVER['QUERY_STRING'],
            'site_id' => (int) conf('SITE_ID'),
            'server_id' => (int) conf('SERVER_ID'),
        ]);
    }

    /**
     * @param mixed $id
     */
    public function login_as($id)
    {
        if ( ! $id) {
            return _e('Wrong id');
        }
        if (main()->ADMIN_GROUP != 1) {
            return _e('Allowed only for super-admins');
        }
        $a = db()->from('admin')->whereid($id)->get();
        if ( ! $a) {
            return _e('Target admin user info not found');
        }
        $t_group = db()->from('admin_groups')->whereid((int) $a['group'])->get();
        // Save previous session
        $tmp = $_SESSION;
        $_SESSION['admin_prev_info'] = $tmp;
        // Login as different admin user
        session_regenerate_id($destroy = true);
        $_SESSION[$this->VAR_ADMIN_ID] = $a['id'];
        $_SESSION[$this->VAR_ADMIN_GROUP_ID] = $a['group'];
        $_SESSION[$this->VAR_ADMIN_LOGIN_TIME] = time();
        $_SESSION[$this->VAR_LOCK_IP] = common()->get_ip();
        $_SESSION[$this->VAR_LOCK_UA] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION[$this->VAR_LOCK_HOST] = $_SERVER['HTTP_HOST'];

        $after_login = $t_group['go_after_login'] ?: $t_group['go_after_login'];
        return js_redirect($after_login ?: './');
    }


    /**
     * Process previous login (used when super-admin logged in as less privileged user and want to login back as super-admin).
     */
    public function _login_with_prev_info()
    {
        $prev_info = $_SESSION['admin_prev_info'];
        if ( ! $prev_info || ! $prev_info[$this->VAR_ADMIN_ID] || ! $prev_info[$this->VAR_ADMIN_GROUP_ID]) {
            if ( ! empty($this->URL_WRONG_LOGIN)) {
                return js_redirect($this->URL_WRONG_LOGIN);
            }
            return false;
        }
        $a = db()->from('admin')->whereid((int) $prev_info[$this->VAR_ADMIN_ID])->get();
        if ( ! $a) {
            if ( ! empty($this->URL_WRONG_LOGIN)) {
                return js_redirect($this->URL_WRONG_LOGIN);
            }
            return false;
        }
        $t_group = db()->from('admin_groups')->whereid((int) $a['group'])->get();
        // Save previous session
        $_SESSION = $_SESSION['admin_prev_info'];
        unset($_SESSION['admin_prev_info']); // Prevent recursion
        // Login as different admin user
        session_regenerate_id($destroy = true);
        $_SESSION[$this->VAR_ADMIN_ID] = $a['id'];
        $_SESSION[$this->VAR_ADMIN_GROUP_ID] = $a['group'];
        $_SESSION[$this->VAR_ADMIN_LOGIN_TIME] = time();
        $_SESSION[$this->VAR_LOCK_IP] = common()->get_ip();
        $_SESSION[$this->VAR_LOCK_UA] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION[$this->VAR_LOCK_HOST] = $_SERVER['HTTP_HOST'];

        $after_login = $t_group[$this->VAR_ADMIN_GO_URL] ?: $t_group[$this->VAR_ADMIN_GO_URL];
        return js_redirect($after_login ?: './');
    }
    /**
     * Process logout.
     */
    public function _do_logout()
    {
        $this->_exec_method_on_action('logout');
        $admin_session_vars = [
            $this->VAR_ADMIN_ID,
            $this->VAR_ADMIN_GROUP_ID,
            $this->VAR_ADMIN_LOGIN_TIME,
            $this->VAR_LOCK_IP,
            $this->VAR_LOCK_UA,
            $this->VAR_LOCK_HOST,
        ];
        // Unset session variables except user id and group
        // (in case when session contains both user and admin info)
        foreach ((array) $_SESSION as $k => $v) {
            if (in_array($k, $admin_session_vars)) {
                unset($_SESSION[$k]);
            }
        }
        $main = main();
        $main->_admin_info = null;
        $main->ADMIN_ID = null;
        $main->ADMIN_GROUP = null;
        //		$main->_init_admin_info($main);
        $main->ADMIN_INFO = &$main->_admin_info;

        session_regenerate_id($destroy = true);

        if ( ! empty($this->URL_AFTER_LOGOUT)) {
            js_redirect($this->URL_AFTER_LOGOUT);
        }
    }

    /**
     * Execute user method after specified action.
     * @param mixed $action
     */
    public function _exec_method_on_action($action = 'login')
    {
        if ($action == 'login') {
            $callbacks = $this->EXEC_AFTER_LOGIN;
        } elseif ($action == 'logout') {
            $callbacks = $this->EXEC_AFTER_LOGOUT;
        } elseif ($action == 'init') {
            $callbacks = $this->EXEC_AFTER_INIT;
        }
        if (empty($callbacks) || ! is_array($callbacks)) {
            return false;
        }
        foreach ((array) $callbacks as $callback) {
            if (is_callable($callback)) {
                call_user_func_array($callback, ['self' => $this]);
            }
        }
    }
}
