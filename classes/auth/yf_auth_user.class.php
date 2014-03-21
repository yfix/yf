<?php

/**
* Special methods for user authentification
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_auth_user {

	/** @var bool Online users vars */
	public $STORE_ONLINE_USERS		= false;// Use db table to store online users or not
	/** @var int Online TTL if inactive */
	public $ONLINE_AUTO_CLEAN		= 1800; // Default value for cleaning up inactive online users
	/** @var int Online TTL if inactive */
	public $ONLINE_MAX_IDS			= 1000; // Max number of online records (to prevent server flooding)
	/** @var string Login field name to use @conf_skip */
	public $LOGIN_FIELD				= 'login';
	/** @var string Alternative login field name to use @conf_skip */
	public $LOGIN_ALIAS				= array('email','phone');
	/** @var string Password field name to use @conf_skip */
	public $PSWD_FIELD				= 'password';
	/** @var string Remeber field name to use @conf_skip */
	public $REMEMBER_FIELD			= 'remember_me';
	/** @var string Cookie name @conf_skip */
	public $VAR_COOKIE_NAME			= 'remember_user_id';
	/** @var int Cookie TTL */
	public $VAR_COOKIE_LIFE_TIME	= 2592000;	// 60*60*24*30 = 30 days (default value)
	/** @var string Default user object (module) to redirect */
	public $DEF_USER_MODULE			= 'signup';
	/** @var string Redirect URL */
	public $URL_WRONG_LOGIN			= './?object=login_form&action=wrong_login';
	/** @var string Redirect URL */
	public $URL_ACCOUNT_INACTIVE	= './?object=login_form&action=account_inactive';
	/** @var string Redirect URL */
	public $URL_SUCCESS_LOGIN		= ''; // './?object=blogs' // If empty or not isset - then last referer will be used
	/** @var string Redirect URL */
	public $URL_AFTER_LOGOUT		= './';
	/** @var string field name @conf_skip */
	public $VAR_USER_ID				= 'user_id';
	/** @var string field name @conf_skip */
	public $VAR_USER_GROUP_ID		= 'user_group';
	/** @var string field name @conf_skip */
	public $VAR_USER_LOGIN_TIME		= 'user_login_time';
	/** @var string field name @conf_skip */
	public $VAR_USER_GO_URL			= 'user_go_url';
	/** @var string field name @conf_skip */
	public $VAR_USER_INFO			= 'user_info';
	/** @var string field name @conf_skip */
	public $VAR_LOCK_IP				= 'auth_lock_to_ip';
	/** @var bool Do log into db user login actions */
	public $DO_LOG_LOGINS			= true;
	/** @var bool Set cookie 'member_id', useful for fast_init before session start */
	public $SET_MEMBER_ID_COOKIE	= '';
	/** @var bool Set cookie 'is_logged_in', different from member_id, useful for nginx cache difference, if field empty - will be disabled */
	public $SET_IS_LOGGED_COOKIE	= '';
	/** @var string Site closed stpl name */
	public $SITE_CLOSED_STPL		= 'site_closed';
	/** @var array @conf_skip 
	* Methods to execute after success login or logout
	* @example	$EXEC_AFTER_LOGIN = array(array('test_method', array('Working!')));
	* @example	$EXEC_AFTER_LOGIN = array(array(array('custom_class', 'custom_method'), array('my_param_1' => 'Working!')));
	*/
	public $EXEC_AFTER_LOGIN		= array();
	/** @var array @conf_skip */
	public $EXEC_AFTER_LOGOUT		= array();
	/** @var string	*/
// TODO: be able to import cookies settings from main()->_init_session()
	public $COOKIE_PATH				= '/';
	/** @var string	*/
	public $COOKIE_DOMAIN			= '';
	/** @var string	*/
	public $COOKIE_SECURE			= false;
	/** @var string	*/
	public $COOKIE_HTTPONLY			= false;
	/** @var bool Store or not user info in session sub array or not */
	public $USER_INFO_IN_SESSION	= 0;
	/** @var bool Check if user have multiple accounts @security */
	public $CHECK_MULTI_ACCOUNTS	= false;
	/** @var bool Catch ref codes */
	public $CATCH_REF_CODES			= false;
	/** @var array Pages where we do not need to track online stats @conf_skip */
	public $ONLINE_SKIP_PAGES		= array(
		'user_profile->compact_info',
		'help->show_tip',
		'forum->compact_topic_repliers',
		'aff',
		'task_loader'
	);
	/** @var bool Store cookie with geo info for guests */
	public $TRACK_GEO_LOCATION		= false;
	/** @var bool Save failed logins @security */
	public $LOG_FAILED_LOGINS		= true;
	/** @var bool Block failed logins after several attempts (To prevent password bruteforcing, hacking, etc) @security */
	public $BLOCK_FAILED_LOGINS		= false;
	/** @var bool Track failed logins TTL @security */
	public $BLOCK_FAILED_TTL		= 3600;
	/** @var bool @security */
	public $BLOCK_FAILS_BY_LOGIN_COUNT	= 5;
	/** @var bool @security */
	public $BLOCK_FAILS_BY_IP_COUNT	= 10;
	/** @var bool Track banned IPs list @security */
	public $BLOCK_BANNED_IPS		= false;
	/** @var bool Allow to login only by HTTPS protocol, else raise error @security */
	public $AUTH_ONLY_HTTPS			= false;
	/** @var bool Check referer in session @security */
	public $SESSION_REFERER_CHECK	= false;
	/** @var bool Lock session to IP address (to prevent hacks) @security */
	public $SESSION_LOCK_TO_IP		= true;
	/** @var bool Allow to use 'remember me in cookies' feature @security */
	public $ALLOW_REMEMBER_ME		= true;
	/** @var string */
	public $USER_PASSWORD_SALT		= '';

	/**
	*/
	function __construct () {
		$cookie_life_time = conf('cookie_life_time');
		if (isset($cookie_life_time)) {
			$this->VAR_COOKIE_LIFE_TIME = 86400 * $cookie_life_time;
		}
		$online_auto_clean = conf('online_auto_clean');
		if (isset($online_auto_clean)) {
			$this->ONLINE_AUTO_CLEAN = $online_auto_clean;
		}
		if (defined('DEFAULT_USER_MODULE')) {
			$this->DEF_USER_MODULE = DEFAULT_USER_MODULE;
		}
		if (empty($this->COOKIE_PATH)) {
			$url_parts = @parse_url(WEB_PATH);
			$this->COOKIE_PATH = $url_parts['path'];
		}
	}

	/**
	* Initialize auth
	*/
	function init () {
		// Chained config rule
		if ($this->BLOCK_FAILED_LOGINS) {
			$this->LOG_FAILED_LOGINS = true;
		}
		// Check if we need to skip online stats
		if ($this->STORE_ONLINE_USERS) {
			if (in_array($_GET['object'].'->'.$_GET['action'], $this->ONLINE_SKIP_PAGES) || in_array($_GET['object'], $this->ONLINE_SKIP_PAGES)) {
				$this->STORE_ONLINE_USERS = false;
			}
			// Check CPU Load
			if (conf('HIGH_CPU_LOAD') == 1) {
				$this->STORE_ONLINE_USERS = false;
			}
		}
		// Delete expired users (expiration time for now 300 seconds == 5 minutes)
		// make this to run randomly every ~10th page call
// TODO: this needed to be done on global cron rarely (every minute or every hour), not on every request
		if ($this->STORE_ONLINE_USERS && $this->ONLINE_AUTO_CLEAN && rand(1,10) == 1) {
			db()->_add_shutdown_query('DELETE FROM '.db('online').' WHERE ('.time().'-time) > '.intval($this->ONLINE_AUTO_CLEAN));
		}
		// Remember last query string to process it after succesful login
		if (empty($_SESSION[$this->VAR_USER_ID]) && isset($_GET['task']) && $_GET['task'] == 'login') {
			if (false === strpos($_SERVER['HTTP_REFERER'], 'login')) {
				$_SESSION[$this->VAR_USER_GO_URL] = $_SERVER['HTTP_REFERER'];
			}
		}
		// Check for session IP
		if ($this->SESSION_LOCK_TO_IP && !empty($_SESSION[$this->VAR_USER_ID])) {
			// User has changed IP, logout immediately
			if (!isset($_SESSION[$this->VAR_LOCK_IP]) || $_SESSION[$this->VAR_LOCK_IP] != common()->get_ip()) {
				trigger_error('AUTH: Attempt to use session with changed IP blocked, auth_ip:'.$_SESSION[$this->VAR_LOCK_IP].', new_ip:'.common()->get_ip().', user_id: '.intval($_SESSION[$this->VAR_USER_ID]), E_USER_WARNING);
				$_GET['task'] = 'logout';
			}
		}
		// Check referer matched to WEB_PATH
		if ($this->SESSION_REFERER_CHECK && (!$_SERVER['HTTP_REFERER'] || substr($_SERVER['HTTP_REFERER'], 0, strlen(WEB_PATH)) != WEB_PATH)) {
			trigger_error('AUTH: Referer not matched and session blocked, referer:'.$_SERVER['HTTP_REFERER'], E_USER_WARNING);
			$_GET['task'] = 'logout';
		}
		// Switch between login/logout actions
		if (isset($_GET['task']) && $_GET['task'] == 'logout') {
			return $this->_do_logout();
		}
		if (!empty($_COOKIE[$this->VAR_COOKIE_NAME]) && empty($_SESSION[$this->VAR_USER_ID])) {
			$this->_process_cookie();
		}
		if (isset($_GET['task']) && $_GET['task'] == 'login') {
			if ($_GET['id'] && strlen($_GET['id']) > 16 && !is_numeric($_GET['id'])) {
				$this->_do_login_with_encrypted();
			}
			if (empty($_SESSION[$this->VAR_USER_ID])) {
				$this->_do_login(array(
					'login'	=> $_POST[$this->LOGIN_FIELD],
					'pswd'	=> $_POST[$this->PSWD_FIELD],
				));
			}
		}
		// Check if current user session has expired
		if ($this->STORE_ONLINE_USERS) {
			$online_users = &main()->_online_users;
			if (!isset($online_users)) {
				$online_users = array();
				$sql = 'SELECT id, user_id FROM '.db('online'). (MAIN_TYPE_USER ? ' WHERE type != "admin"' : ''). ($this->ONLINE_MAX_IDS ? ' LIMIT '.intval($this->ONLINE_MAX_IDS) : '');
				foreach ((array)db()->get_2d($sql) as $online_id => $user_id) {
					$online_users[$online_id] = $user_id;
				}
			}
			// Create default record if not exists one
			if (!isset($online_users[session_id()])) {
				$_cur_user_data = $this->_update_online_info();
				$online_users[session_id()] = $_cur_user_data['user_id'];
			}
		}
		if ($this->TRACK_GEO_LOCATION && main()->USE_GEO_IP) {
			$this->_track_geo_location();
		}
		if ($this->CATCH_REF_CODES) {
			$this->_catch_ref_codes();
		}
		// Try to assign first page of the site (if $_GET['object'] is empty)
		if (empty($_GET['object'])) {
			$go = defined('SITE_DEFAULT_PAGE') ? SITE_DEFAULT_PAGE : conf('site_first_page');
			// Check if default url is not empty and then use it
			if (!empty($go)) {
				$go = str_replace(array('./?','./'), '', $go);
				$tmp_array = array();
				parse_str($go, $tmp_array);
				foreach ((array)$tmp_array as $k => $v) {
					$_GET[$k] = $v;
				}
			}
		}
		if (empty($_GET['object'])) {
			$_GET['object'] = $this->DEF_USER_MODULE;
		}
		// Store user inside the main module
		if (!empty($_SESSION[$this->VAR_USER_ID]) && !empty($_SESSION[$this->VAR_USER_GROUP_ID])) {
			main()->USER_ID		= $_SESSION[$this->VAR_USER_ID];
			main()->USER_GROUP	= $_SESSION[$this->VAR_USER_GROUP_ID];
			if ($this->USER_INFO_IN_SESSION && !empty($_SESSION[$this->VAR_USER_INFO])) {
				main()->USER_INFO = $_SESSION[$this->VAR_USER_INFO];
			}
		}
	}

	/**
	* Try to log in user
	*/
	function _do_login ($params = array()) {
		$AUTH_LOGIN	= trim($params['login']);
		$AUTH_PSWD	= trim($params['pswd']);

		if ($this->AUTH_ONLY_HTTPS && !($_SERVER['HTTPS'] || $_SERVER['SSL_PROTOCOL'])) {
			$redirect_url = '';
			if ($_SERVER['HTTP_REFERER']) {
				$redirect_url = str_replace('http://', 'https://', $_SERVER['HTTP_REFERER']);
			}
			if (!$redirect_url) {
				$request_uri	= getenv('REQUEST_URI');
				$cur_web_path	= $request_uri[strlen($request_uri) - 1] == '/' ? substr($request_uri, 0, -1) : dirname($request_uri);
				$redirect_url	= 'https://'.getenv('HTTP_HOST').str_replace(array("\\","//"), array('/','/'), (MAIN_TYPE_ADMIN ? dirname($cur_web_path) : $cur_web_path).'/');
			}
			return js_redirect($redirect_url);
		}

		if (!empty($AUTH_LOGIN) && !empty($AUTH_PSWD)) {
			$NEED_QUERY_DB = true;

			$CUR_IP = common()->get_ip();
			if ($this->BLOCK_BANNED_IPS) {
				if (common()->_ip_is_banned()) {
					$NEED_QUERY_DB = false;
					trigger_error('AUTH: Attempt to login from banned IP ('.$CUR_IP.') as "'.$AUTH_LOGIN.'" blocked', E_USER_WARNING);
					return js_redirect($this->URL_WRONG_LOGIN);
				}
			}
			if ($this->BLOCK_FAILED_LOGINS) {
				list($_fails_by_login) = db()->query_fetch(
					'SELECT COUNT(*) AS `0` FROM '.db('log_auth_fails').' WHERE time > '.(time() - $this->BLOCK_FAILED_TTL).' AND login="'._es($AUTH_LOGIN).'"'
				);
				list($_fails_by_ip) = db()->query_fetch(
					'SELECT COUNT(*) AS `0` FROM '.db('log_auth_fails').' WHERE time > '.(time() - $this->BLOCK_FAILED_TTL).' AND ip="'._es(common()->get_ip()).'"'
				);
				if ($_fails_by_login >= $this->BLOCK_FAILS_BY_LOGIN_COUNT || $_fails_by_ip >= $this->BLOCK_FAILS_BY_IP_COUNT) {
					$NEED_QUERY_DB = false;
					trigger_error('AUTH: Attempt to login as "'.$AUTH_LOGIN.'" blocked, fails_by_login: '.intval($_fails_by_login).', fails_by_ip: '.intval($_fails_by_ip), E_USER_WARNING);
				}
			}

			$PSWD_OK = false;
			if ($NEED_QUERY_DB) {
				$user_info = $this->_get_user_info($AUTH_LOGIN);
				// Allow md5 passwords
				if (strlen($user_info['password']) == 32 && md5($AUTH_PSWD. $this->USER_PASSWORD_SALT) == $user_info['password']) {
					$PSWD_OK = true;
				} elseif ($user_info['password'] == $AUTH_PSWD) {
					$PSWD_OK = true;
				}
			}
			if ($PSWD_OK) {
				// Set member id cookie expired on session end
				if ($this->SET_MEMBER_ID_COOKIE && preg_match('/^[a-z0-9_\-]+$/ims', $this->SET_MEMBER_ID_COOKIE)) {
					$this->_cookie_set($this->SET_MEMBER_ID_COOKIE, $user_info['id']);
				}
				if ($this->SET_IS_LOGGED_COOKIE && preg_match('/^[a-z0-9_\-]+$/ims', $this->SET_IS_LOGGED_COOKIE)) {
					$this->_cookie_set($this->SET_IS_LOGGED_COOKIE, '1');
				}
			} else {
				unset($user_info);
				if ($this->LOG_FAILED_LOGINS) {
					db()->INSERT('log_auth_fails', array(
						'time'	=> _es(str_replace(',', '.', microtime(true))),
						'ip'	=> _es(common()->get_ip()),
						'login'	=> _es($AUTH_LOGIN),
						'pswd'	=> _es($AUTH_PSWD),
						'reason'=> $NEED_QUERY_DB ? 'w' : 'b', // 'w' means wrong login, 'b' means blocked
					));
				}
			}
		}
		$this->_save_login_in_session($user_info, $params['no_redirect']);
	}

	/**
	* Try to log in user with encrypted string (used to quickly login admin as common user)
	*/
	function _do_login_with_encrypted () {
		header('X-Robots-Tag: noindex,nofollow,noarchive,nosnippet');
		if (!$_GET['id'] || strlen($_GET['id']) < 16 || is_numeric($_GET['id'])) {
			$this->_encrypted_error = 'GET_id is not like an encrypted string';
			return false;
		}
		$secret_key = db()->get_one('SELECT MD5(CONCAT(`password`, "'.str_replace(array('http://', 'https://'), '//', WEB_PATH).'")) FROM '.db('admin').' WHERE id=1');
		if (!$secret_key) {
			$this->_encrypted_error = 'secret key generation failed';
			return false;
		}
		// Should contain this: 'userid-%id-%time-%md5';
		$decrypted = _class('encryption')->_safe_decrypt_with_base64($_GET['id'], $secret_key);
		if (!$decrypted || !strlen($decrypted) || substr($decrypted, 0, strlen('userid-')) != 'userid-') {
			$this->_encrypted_error = 'decryption failed, possibly broken string or hack attempt';
			return false;
		}
		$d = explode('-', $decrypted);
		if (count($d) != 5) {
			$this->_encrypted_error = 'decrypted string is not a valid array';
			return false;
		}
		if (md5($d[0].'-'.$d[1].'-'.$d[2].'-'.$d[3]) != $d[4]) {
			$this->_encrypted_error = 'md5 hash not matches';
			return false;
		}
		$user_id = intval($d[1]);
		$time = intval($d[2]);
		$pswd_hash = strtolower(trim($d[3]));
		if (!$user_id || !$time) {
			$this->_encrypted_error = 'wrong user_id or time';
			return false;
		}
		if (!preg_match('~^[a-z0-9]{32}$~', $pswd_hash)) {
			$this->_encrypted_error = 'wrong pswd_hash';
			return false;
		}
		// Allowing only 6 hours for link to keep alive
		if ($time < (time() - 3600 * 6)) {
			$this->_encrypted_error = 'time elapsed';
			return false;
		}
		$user_info = db()->get('SELECT *, MD5(`password`) AS md5_pswd FROM '.db('user').' WHERE id='.intval($user_id));
		if (!$user_info || $user_info['id'] != $user_id) {
			$this->_encrypted_error = 'user not found';
			return false;
		}
		if (!$user_info['md5_pswd'] || $user_info['md5_pswd'] != $pswd_hash) {
			$this->_encrypted_error = 'user pswd_hash not matched';
			return false;
		}
		return $this->_save_login_in_session($user_info);
	}

	/**
	*/
	function _get_user_info ($login = '') {
		if (empty($login)) {
			return false;
		}
		$sql = 'SELECT * FROM '.db('user').' WHERE '.db()->escape_key($this->LOGIN_FIELD).'="'.db()->es($login).'" ';
		$login_aliases = $this->LOGIN_ALIAS;
		if ($login_aliases) {
			if (!is_array($login_aliases)) {
				$login_aliases = array($login_aliases);
			}
			$alias_sql = array();
			foreach ((array)$login_aliases as $alias) {
				$alias = trim($alias);
				if (preg_match('/^[a-z0-9_]+$/ims', $alias)) {
					$alias_sql[] = db()->escape_key($alias).'="'.db()->es($login).'"';
				}
			}
			if ($alias_sql) {
				$sql .= ' OR '.implode(' OR ', $alias_sql);
			}
		}
		return db()->get($sql);
	}

	/**
	*/
	function _is_user_inactive ($user_info = array()) {
		if (empty($user_info)) {
			return true;
		}
		if (empty($user_info['active'])) {
			return true;
		}
		return false;
	}

	/**
	* Alias
	*/
	function auto_login ($user_info = array(), $no_redirect_on_success = false) {
		return $this->_save_login_in_session ($user_info, $no_redirect_on_success);
	}

	/**
	* Save auth information in session
	*/
	function _save_login_in_session ($user_info = array(), $no_redirect_on_success = false) {
		if (empty($user_info['id'])) {

			return js_redirect($this->URL_WRONG_LOGIN);

		} elseif ($this->_is_user_inactive($user_info)) {

			return js_redirect($this->URL_ACCOUNT_INACTIVE);

		} else {

			$_SESSION[$this->VAR_USER_ID]			= $user_info['id'];
			$_SESSION[$this->VAR_USER_GROUP_ID]		= $user_info['group'];
			$_SESSION[$this->VAR_USER_LOGIN_TIME]	= time();
			$_SESSION[$this->VAR_LOCK_IP]			= common()->get_ip();
			$main = main();
			$main->_init_cur_user_info($main);
			$main->USER_INFO = &$main->_user_info;
			$main->_LOGGED_IN_USER_INFO	= &$main->_user_info;

			if ($this->DO_LOG_LOGINS) {
				_class('logs')->store_user_auth($user_info);
			}
			if (!empty($_POST[$this->REMEMBER_FIELD]) && $this->ALLOW_REMEMBER_ME) {
				$encrypted_string = _class('encryption')->_safe_encrypt_with_base64($user_info['id'].'-'.$user_info[$this->LOGIN_FIELD].'-'.$user_info[$this->PSWD_FIELD].'-'.time());
				$this->_cookie_set($this->VAR_COOKIE_NAME, $encrypted_string, time() + $this->VAR_COOKIE_LIFE_TIME);
				$this->_cookie_set('quick_login', xsb_encode($user_info[$this->LOGIN_FIELD]), time() + 86400 * 365);
			}
			if ($this->CHECK_MULTI_ACCOUNTS) {
				$this->_check_multi_accounts();
			}
			$this->_update_user_info_on_login($user_info);
			$this->_update_online_info();
			$this->_exec_method_on_action('login');
			$group_info = array();
			if (!empty($user_info['group'])) {
				$groups = main()->get_data('user_groups_details');
				$group_info = $groups[$user_info['group']];
			}
			if (empty($no_redirect_on_success)) {
				$this->_success_login_redirect($user_info, $group_info);
			}
			if ($this->USER_INFO_IN_SESSION) {
				$_SESSION[$this->VAR_USER_INFO] = $user_info;
			}
		}
	}

	/**
	*/
	function _update_user_info_on_login($user_info) {
	}

	/**
	*/
	function _success_login_redirect ($user_info = array(), $group_info = array()) {
		// Auto-redirect to the page before login form if needed
		if (!empty($_SESSION[$this->VAR_USER_GO_URL]) && !($this->URL_SUCCESS_LOGIN && $_POST['skip_auto_url'])) {
			$REDIRECT_URL = (substr($_SESSION[$this->VAR_USER_GO_URL], 0, 2) != './' ? './?' : ''). str_replace(WEB_PATH, '', str_replace(array('http:','https:'), '', $_SESSION[$this->VAR_USER_GO_URL]));
			$_SESSION[$this->VAR_USER_GO_URL] = '';
		} elseif (!empty($user_info['go_after_login'])) {
			$REDIRECT_URL = $user_info['go_after_login'];
		} elseif (!empty($group_info['go_after_login'])) {
			$REDIRECT_URL = $group_info['go_after_login'];
		// Force redirect user to the default location
		} elseif (!empty($this->URL_SUCCESS_LOGIN)) {
			$REDIRECT_URL = $this->URL_SUCCESS_LOGIN;
		}
		if ($REDIRECT_URL) {
			js_redirect($REDIRECT_URL);
		}
	}

	/**
	* Do log out user
	*/
	function _do_logout () {
		if ($this->STORE_ONLINE_USERS) {
			db()->_add_shutdown_query('DELETE FROM '.db('online').' WHERE user_id='.intval($_SESSION[$this->VAR_USER_ID]));
			$MAIN = &main();
			if (isset($MAIN->_online_users[session_id()])) {
				unset($MAIN->_online_users[session_id()]);
			}
		}
		$this->_exec_method_on_action('logout');
		$user_session_vars = array(
			$this->VAR_USER_ID,
			$this->VAR_USER_GROUP_ID,
			$this->VAR_USER_LOGIN_TIME,
			$this->VAR_LOCK_IP,
		);
		foreach ((array)$_SESSION as $k => $v) {
			if (in_array($k, $user_session_vars)) {
				unset($_SESSION[$k]);
			}
		}
		$main = main();
		$main->_init_cur_user_info($main);
		$main->USER_INFO = &$main->_user_info;
		$main->_LOGGED_IN_USER_INFO	= &$main->_user_info;

		$this->_cleanup_cookie();
		session_destroy();

		js_redirect($this->URL_AFTER_LOGOUT);
	}

	/**
	* Processing user cookie
	*/
	function _process_cookie() {
		// No need to process cookie if user is logged in or this feature not allowed
		if (!empty($_SESSION[$this->VAR_USER_ID]) || !$this->ALLOW_REMEMBER_ME) {
			return false;
		}
		// Decrypt cookie contents
		list($user_id, $login, $password, $cookie_created) = @explode('-', _class('encryption')->_safe_decrypt_with_base64($_COOKIE[$this->VAR_COOKIE_NAME]));
		// Check if user with such login and password exists and cookie has not expired
		if (time() < ($cookie_created + $this->VAR_COOKIE_LIFE_TIME) && !empty($login) && !empty($password)) {
			// Empty redirect address (in every case)
			$_SESSION[$this->VAR_USER_GO_URL] = null;
			$this->_do_login(array(
				'login'			=> $login,
				'pswd'			=> $password,
				'no_redirect'	=> 1,
			));
		} else {
			$this->_cleanup_cookie();
		}
	}

	/**
	* Unset user cookie
	*/
	function _cleanup_cookie() {
		$this->_cookie_del($this->VAR_COOKIE_NAME);
		if (isset($_COOKIE[$this->VAR_COOKIE_NAME])) {
			unset($_COOKIE[$this->VAR_COOKIE_NAME]);
		}
		if ($this->SET_MEMBER_ID_COOKIE && preg_match('/^[a-z0-9_\-]+$/ims', $this->SET_MEMBER_ID_COOKIE)) {
			$this->_cookie_del($this->SET_MEMBER_ID_COOKIE);
		}
		if ($this->SET_IS_LOGGED_COOKIE && preg_match('/^[a-z0-9_\-]+$/ims', $this->SET_IS_LOGGED_COOKIE)) {
			$this->_cookie_del($this->SET_IS_LOGGED_COOKIE);
		}
	}

	/**
	* Execute user method after specified action
	*/
	function _exec_method_on_action($action = 'login') {
		if ($action == 'login') {
			$CALLBACKS = $this->EXEC_AFTER_LOGIN;
		} elseif ($action == 'logout') {
			$CALLBACKS = $this->EXEC_AFTER_LOGOUT;
		}
		if (empty($CALLBACKS)) {
			return false;
		}
		foreach ((array)$CALLBACKS as $cur_method) {
			if (is_callable($cur_method[0])) {
				call_user_func_array($cur_method[0], (array)$cur_method[1]);
			}
		}
	}

	/**
	* Multiple accounts checker
	*/
	function _check_multi_accounts() {
		if (empty($_SESSION[$this->VAR_USER_ID]) || empty($_SESSION[$this->VAR_USER_GROUP_ID])) {
			return false;
		}
		$_SPECIAL_NAME = 'accounts';
		if (empty($_COOKIE[$_SPECIAL_NAME])) {
			return $this->_set_special_cookie($_SESSION[$this->VAR_USER_ID]);
		}
		$cookie_users = array();
		foreach (explode('_', $_COOKIE[$_SPECIAL_NAME]) as $_user_id) {
			$cookie_users[$_user_id] = $_user_id;
		}
		if ($_COOKIE[$_SPECIAL_NAME] == $_SESSION[$this->VAR_USER_ID] 
			|| (count($cookie_users) > 1 && isset($cookie_users[$_SESSION[$this->VAR_USER_ID]]))
		) {
			return false;
		}
		$data = db()->query_fetch('SELECT * FROM '.db('check_multi_accounts').' WHERE user_id='.intval($_SESSION[$this->VAR_USER_ID]));
		if (isset($cookie_users[$_SESSION[$this->VAR_USER_ID]])) {
			unset($cookie_users[$_SESSION[$this->VAR_USER_ID]]);
		}
		$matching_users = implode(',', $cookie_users);
		if (empty($data)) {
			db()->INSERT('check_multi_accounts', array(
				'user_id'		=> intval($_SESSION[$this->VAR_USER_ID]),
				'matching_users'=> _es($matching_users),
				'last_update'	=> time(),
				'cookie_match'	=> 1,
			));
		} else {
			db()->UPDATE('check_multi_accounts', array(
				'matching_users'=> _es($matching_users),
				'last_update'	=> time(),
				'cookie_match'	=> 1,
			), 'user_id='.intval($_SESSION[$this->VAR_USER_ID]));
		}
		$this->_set_special_cookie($_COOKIE[$_SPECIAL_NAME].'_'.$_SESSION[$this->VAR_USER_ID]);
		main()->_HAS_MULTI_ACCOUNTS = true;
		_debug_log('_check_multi_accounts: found possible multi-account, old_id='.$last_id.',new_id='.$_SESSION[$this->VAR_USER_ID].',cookie='.$_COOKIE[$_SPECIAL_NAME], E_NOTICE);
	}

	/**
	* Set cookie for the _check_multi_accounts()
	*/
	function _set_special_cookie($value = '') {
		return $this->_cookie_set('accounts', $value, strtotime((date('Y') + 10).'-01-01 00:00:00'));
	}

	/**
	* Update online info
	*/
	function _update_online_info() {
		if (!$this->STORE_ONLINE_USERS) {
			return false;
		}
		if (!$_SESSION[$this->VAR_USER_ID] && main()->SPIDERS_DETECTION) {
			if (conf('SPIDER_NAME')) {
				return false;
			}
		}
		$data = array(
			'id'			=> _es(session_id()),
			'user_id'		=> intval($_SESSION[$this->VAR_USER_ID]),
			'user_group'	=> intval($_SESSION[$this->VAR_USER_GROUP_ID]),
			'time'			=> time(),
			'type'			=> _es(MAIN_TYPE),
			'ip'			=> _es(common()->get_ip()),
			'user_agent'	=> _es($_SERVER['HTTP_USER_AGENT']),
			'query_string'	=> _es($_SERVER['QUERY_STRING']),
			'site_id'		=> (int)conf('SITE_ID'),
		);
		$sql = db()->REPLACE('online', $data, 1);
		db()->_add_shutdown_query($sql);
		return $data;
	}

	/**
	* Do save geo location for guests
	*/
	function _track_geo_location() {
		if (!$this->TRACK_GEO_LOCATION || !main()->USE_GEO_IP) {
			return false;
		}
		if (!$_SESSION[$this->VAR_USER_ID] && main()->SPIDERS_DETECTION) {
			if (conf('SPIDER_NAME')) {
				return false;
			}
		}
		// Cleanup old-style geo location cookie
		$old_cookie_name = 'geo_location';
		if (isset($_COOKIE[$old_cookie_name])) {
			$this->_cookie_del($old_cookie_name);
			unset($_COOKIE[$old_cookie_name]);
		}
		$cur_ip		= _es(common()->get_ip());
		// Try to get data from cookie (current user selection)
		$SEL_COOKIE_NAME = 'geo_selected';
		if (empty($geo_data) && isset($_COOKIE[$SEL_COOKIE_NAME]) && !empty($_COOKIE[$SEL_COOKIE_NAME])) {
			$geo_data = unserialize($_COOKIE[$SEL_COOKIE_NAME]);
			$geo_data['_source'] = 'sel_cookie';
		}
		// Try to get data from nginx geoip module
		if (empty($geo_data) && !empty($_SERVER['HTTP_X_GEO_CITY'])) {
			// Nginx currently not support lon/lat here:
			/*
				HTTP_X_GEO_CITY => 'Kiev',
				HTTP_X_GEO_REGION => '13',
				HTTP_X_GEO_COUNTRY
			*/
		}
		// Try to get data from 'mod_geoip'
		if (empty($geo_data) && !empty($_SERVER['GEOIP_LATITUDE'])) {
			$geo_data = array(
				'country_code'	=> $_SERVER['GEOIP_COUNTRY_CODE'],
				'country_name'	=> $_SERVER['GEOIP_COUNTRY_NAME'],
				'region_code'	=> $_SERVER['GEOIP_REGION'],
				'city_name'		=> $_SERVER['GEOIP_CITY'],
				'dma_code'		=> $_SERVER['GEOIP_DMA_CODE'],
				'area_code'		=> $_SERVER['GEOIP_AREA_CODE'],
				'longitude'		=> $_SERVER['GEOIP_LONGITUDE'],
				'latitude'		=> $_SERVER['GEOIP_LATITUDE'],
			);
			$geo_data['_source'] = 'mod_geoip';
		}
		// Try to get data from 'geo_city_location' table
		if (empty($geo_data)) {
			$geo_data = common()->_get_geo_data_from_db($cur_ip);
			$geo_data['_source'] = 'db_by_ip';
		}
		// Check data consistency
		if (!empty($geo_data['city_name']) && !strlen($geo_data['region_code'])) {
			$geo_data['city_name'] = '';
		}
		if (!empty($geo_data['region_code']) && empty($geo_data['country_code'])) {
			$geo_data['region_code'] = '';
			$geo_data['region_name'] = '';
		}
		// Assign global array for use by all other code
		main()->_USER_GEO_DATA = $geo_data;
	}

	/**
	* Ref codes
	*/
	function _catch_ref_codes() {
		if (!$this->CATCH_REF_CODES) {
			return false;
		}
		$COOKIE_VAR_NAME = 'ref_code';
		$where = $_SERVER['REQUEST_URI'];
		$ref_code = '';
		if (!empty($where) && preg_match('/\?r=([0-9]{1,8})$/ims', $where, $m)) {
			$ref_code = $m[1];
		}
		if (empty($ref_code)) {
			return false;
		}
		$this->_cookie_set($COOKIE_VAR_NAME, $ref_code, time() + 86400 * 30);
		// Return user to the initial page without ref_code
		$location = (($_SERVER['HTTPS'] || $_SERVER['SSL_PROTOCOL']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].substr($where, 0, -strlen('?r='.$ref_code));
		common()->redirect($location, false, 'http', '', 0);
		exit();
	}

	/**
	*/
	function _cookie_set($name, $value = '', $expire = 0, $path = null, $domain = null, $secure = null, $httponly = null) {
		$path		= is_null($path)	? $this->COOKIE_PATH : $path;
		$domain		= is_null($domain)	? $this->COOKIE_DOMAIN : $domain;
		$secure		= is_null($secure)	? $this->COOKIE_SECURE : $secure;
		$httponly	= is_null($httponly)? $this->COOKIE_HTTPONLY : $httponly;
		return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
	}

	/**
	*/
	function _cookie_del($name, $value = '', $expire = 0, $path = null, $domain = null, $secure = null, $httponly = null) {
		$path		= is_null($path)	? $this->COOKIE_PATH : $path;
		$domain		= is_null($domain)	? $this->COOKIE_DOMAIN : $domain;
		$secure		= is_null($secure)	? $this->COOKIE_SECURE : $secure;
		$httponly	= is_null($httponly)? $this->COOKIE_HTTPONLY : $httponly;
		return setcookie($name, '', $expire, $path, $domain, $secure, $httponly);
	}
}
