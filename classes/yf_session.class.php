<?php

/**
* Core session handler
*/
class yf_session {

	/** @var string Current session name */
	public $NAME			= '';
	/** @var bool Disable sessions or not */
	public $OFF				= false;
	/** @var string Custom session save dir (leave ampty to skip), example: 'session_data/' */
	public $SAVE_DIR		= '';
	/** @var int Session life time (in seconds) */
	public $LIFE_TIME		= 18000; // 5 hours
	/** @var string */
	public $DOMAIN			= ''; // Default empty, means current domain
	/** @var string */
	public $COOKIE_PATH		= '/';
	/** @var bool */
	public $COOKIE_SECURE	= false;
	/** @var bool */
	public $COOKIE_HTTPONLY	= false;
	/** @var string */
	public $REFERER_CHECK	= ''; // WEB_PATH
	/** @var string */
	public $DESTROY_EXPIRED	= false;
	/** @var string Custom session name */
	public $USE_UNIQUE_NAME	= true;
	/** @var string Custom handler name */
	public $CUSTOM_HANDLER	= '';
	/** @var bool @conf_skip */
	public $_driver			= null;
	/** @var bool @conf_skip */
	public $_started		= false;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function _init() {
		$main = main();
		// Import session settings from main class, but prefixed with "SESSION_"
		$prefix = 'SESSION_';
		$plen = strlen($prefix);
		foreach (get_object_vars($main) as $k => $v) {
			if (substr($k, 0, $plen) !== $prefix) {
				continue;
			}
			$name = substr($k, $plen);
			$this->$name = $v;
		}
	}

	/**
	*/
	function start () {
		$main = main();
		if (!empty($this->_started) || $main->CONSOLE_MODE || conf('SESSION_OFF') || $this->OFF) {
			return false;
		}
		// Set custom session name
		if ($this->USE_UNIQUE_NAME) {
			$force_name_path = conf('_SESSION_FORCE_NAME_PATH');
			$_name_path = isset($force_name_path) ? $force_name_path : (MAIN_TYPE_ADMIN ? ADMIN_SITE_PATH : SITE_PATH);
			$_name_path = str_replace("\\", '/', OS_WINDOWS ? strtolower($_name_path) : $_name_path);
			$this->NAME = 'sid_'.intval(abs(crc32($_name_path)));
			session_name($this->NAME);
		} else {
			$this->NAME = ini_get('session.name'); // Usually PHPSESSID
		}
		$main->SESSION_NAME = $this->NAME;
		if (session_id() !== '') { // (session_status() == PHP_SESSION_ACTIVE) => PHP 5.4+ only
			return true;
		}
		@ini_set('session.use_trans_sid',	0); // We need @ here to avoid error when session already started
		ini_set('url_rewriter.tags',		'');
		if (!empty($this->LIFE_TIME)) {
			ini_set('session.cookie_lifetime',	$this->LIFE_TIME);
			ini_set('session.gc_probability',	0);
			ini_set('session.gc_maxlifetime',	$this->LIFE_TIME);
		}
		ini_set('session.use_cookies',		1);
		ini_set('session.use_only_cookies',	1);
		if ($this->COOKIE_PATH) {
			ini_set('session.cookie_path', $this->COOKIE_PATH);
		}
		if ($this->DOMAIN) {
			ini_set('session.cookie_domain', $this->DOMAIN);
		}
		if ($this->COOKIE_SECURE) {
			ini_set('session.cookie_secure', 1);
		}
		if ($this->COOKIE_HTTPONLY) {
			ini_set('session.cookie_httponly', 1);
		}
		if ($this->REFERER_CHECK) { // WEB_PATH
			ini_set('session.referer_check', $this->REFERER_CHECK);
		}
		conf('COOKIES_ENABLED', !is_null($_COOKIE[$this->NAME]) ? 1 : 0);
		// Check if we have valid session name
		if (!is_null($_COOKIE[$this->NAME])) {
			$_test_result = preg_replace('/[^a-z0-9]/i', '', $_COOKIE[$this->NAME]);
			if ($_test_result !== $_COOKIE[$this->NAME]) {
				$_COOKIE[$this->NAME] = abs(crc32(microtime(true)));
				session_id($_COOKIE[$this->NAME]);
			}
		}
		if (!empty($this->SAVE_DIR)) {
			$s_path = STORAGE_PATH. $this->SAVE_DIR;
			if (!file_exists($s_path)) {
				mkdir($s_path, 0755, true);
			}
			session_save_path($s_path);
		}
		if ($this->CUSTOM_HANDLER && $this->_driver_setup($this->CUSTOM_HANDLER)) {
			$main->session = $this->_driver;
		}
		session_start();
		// Instruct bots to totally ignore current page
		if (DEBUG_MODE || MAIN_TYPE_ADMIN) {
			header('X-Robots-Tag: noindex,nofollow,noarchive,nosnippet');
		}
		$now = time();
		$last_update = $_SESSION['last_update'];
		if ($last_update) {
			$diff = $now - $last_update;
			$percent = $diff / $this->LIFE_TIME * 100;
			// Session expired
			if ($percent > 100 && $this->DESTROY_EXPIRED) {
				session_destroy();
				session_start();
			// Session need to be regenerated
			} elseif ($percent > 10) {
				session_regenerate_id(/*$delete_old_session = true*/);
				$_SESSION['last_update'] = $now;
			}
		} else {
			$_SESSION['last_update'] = $now;
		}
		$main->_session_init_complete = true;
		$this->_started = true;
		return $this->_started;
	}

	/**
	*/
	function stop () {
		session_write_close();
		return true;
	}

	/**
	*/
	function _driver_setup ($driver_name) {
		$session_class_name = 'session_driver_'.$driver_name;
		$session_loaded_class_name = $this->load_class_file($session_class_name, 'classes/session/');
		if (empty($session_loaded_class_name)) {
			return false;
		}
		$this->_driver = new $session_loaded_class_name();
		main()->set_module_conf($session_class_name, $this->_driver);
		session_set_save_handler(
			array($this->_driver, 'open'),
			array($this->_driver, 'close'),
			array($this->_driver, 'read'),
			array($this->_driver, 'write'),
			array($this->_driver, 'destroy'),
			array($this->_driver, 'gc')
		);
		return $this->_driver;
	}
}
