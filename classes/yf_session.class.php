<?php

/**
* Core session handler
*/
class yf_session {

	/** @var string Custom session save dir (leave ampty to skip), example: 'session_data/' */
	public $SESSION_SAVE_DIR		= '';
	/** @var int Session life time (in seconds) */
	public $SESSION_LIFE_TIME		= 18000; // 5 hours
	/** @var string */
	public $SESSION_DOMAIN			= ''; // Default empty, means current domain
	/** @var string */
	public $SESSION_COOKIE_PATH		= '/';
	/** @var bool */
	public $SESSION_COOKIE_SECURE	= false;
	/** @var bool */
	public $SESSION_COOKIE_HTTPONLY	= false;
	/** @var string */
	public $SESSION_REFERER_CHECK	= ''; // WEB_PATH
	/** @var bool */
	public $SESSION_DESTROY_EXPIRED	= false;
	/** @var bool Custom session name */
	public $SESSION_UNIQUE_NAME		= true;

	/**
	*/
	function init_session () {
/*
		if (isset($this->_session_init_complete) || main()->CONSOLE_MODE) {
			return false;
		}
		if (main()->SPIDERS_DETECTION && conf('IS_SPIDER')) {
			return false;
		}
		if (conf('SESSION_OFF') || main()->SESSION_OFF) {
			return false;
		}
*/
		// Set custom session name
		if ($this->SESSION_UNIQUE_NAME) {
			$force_name_path = conf('_SESSION_FORCE_NAME_PATH');
			$_name_path = isset($force_name_path) ? $force_name_path : (MAIN_TYPE_ADMIN ? ADMIN_SITE_PATH : SITE_PATH);
			$_name_path = str_replace("\\", '/', OS_WINDOWS ? strtolower($_name_path) : $_name_path);
			$this->SESSION_NAME = 'sid_'.intval(abs(crc32($_name_path)));
			session_name($this->SESSION_NAME);
		} else {
			$this->SESSION_NAME = ini_get('session.name'); // Usually PHPSESSID
		}
		if (session_id() !== '') { // (session_status() == PHP_SESSION_ACTIVE) => PHP 5.4+ only
			return true;
		}
		@ini_set('session.use_trans_sid',	0); // We need @ here to avoid error when session already started
		ini_set('url_rewriter.tags',		'');
		if (!empty($this->SESSION_LIFE_TIME)) {
			ini_set('session.gc_maxlifetime',	$this->SESSION_LIFE_TIME);
			ini_set('session.cookie_lifetime',	$this->SESSION_LIFE_TIME);
		}
		ini_set('session.use_cookies',		1);
		ini_set('session.use_only_cookies',	1);
		if ($this->SESSION_COOKIE_PATH) {
			ini_set('session.cookie_path', $this->SESSION_COOKIE_PATH);
		}
		if ($this->SESSION_DOMAIN) {
			ini_set('session.cookie_domain', $this->SESSION_DOMAIN);
		}
		if ($this->SESSION_COOKIE_SECURE) {
			ini_set('session.cookie_secure', 1);
		}
		if ($this->SESSION_COOKIE_HTTPONLY) {
			ini_set('session.cookie_httponly', 1);
		}
		if ($this->SESSION_REFERER_CHECK) { // WEB_PATH
			ini_set('session.referer_check', $this->SESSION_REFERER_CHECK);
		}
		conf('COOKIES_ENABLED', !is_null($this->_cookie($this->SESSION_NAME)) ? 1 : 0);
		// Check if we have valid session name
		if (!is_null($this->_cookie($this->SESSION_NAME))) {
			$_test_result = preg_replace('/[^a-z0-9]/i', '', $this->_cookie($this->SESSION_NAME));
			if ($_test_result !== $this->_cookie($this->SESSION_NAME)) {
				$this->_cookie($this->SESSION_NAME, abs(crc32(microtime(true))));
				session_id($this->_cookie($this->SESSION_NAME));
			}
		}
		if (!empty($this->SESSION_SAVE_DIR)) {
			$s_path = PROJECT_PATH.$this->SESSION_SAVE_DIR;
			if (!file_exists($s_path)) {
				mkdir($s_path, 0755, true);
			}
			session_save_path($s_path);
		}
		if ($this->CUSTOM_SESSION_HANDLER) {
			$session_class_name = 'session_driver_'.$this->CUSTOM_SESSION_HANDLER;
			$session_loaded_class_name = $this->load_class_file($session_class_name, 'classes/session/');
			if (empty($session_loaded_class_name)) {
				return false;
			}
			$this->session = new $session_loaded_class_name();
			$this->_set_module_conf($session_class_name, $this->session);
			// Change the save_handler to use the class functions
			session_set_save_handler (
				array($this->session, 'open'),
				array($this->session, 'close'),
				array($this->session, 'read'),
				array($this->session, 'write'),
				array($this->session, 'destroy'),
				array($this->session, 'gc')
			);
		}
		session_start();
		// Instruct bots to totally ignore current page
		if (DEBUG_MODE || MAIN_TYPE_ADMIN) {
			header('X-Robots-Tag: noindex,nofollow,noarchive,nosnippet');
		}
		$now = time();
		$last_update = $this->_session('last_update');
		if ($last_update) {
			$diff = $now - $last_update;
			$percent = $diff / $this->SESSION_LIFE_TIME * 100;
			// Session expired
			if ($percent > 100 && $this->SESSION_DESTROY_EXPIRED) {
				session_destroy();
				session_start();
			// Session need to be regenerated
			} elseif ($percent > 10) {
				session_regenerate_id(/*$delete_old_session = true*/);
				$this->_session('last_update', $now);
			}
		} else {
			$this->_session('last_update', $now);
		}
		$this->_session_init_complete = true;
	}
}
