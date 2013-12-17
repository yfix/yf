<?php

class yf_oauth {

	private $_providers = array();

	/**
	*/
	function _init() {
		conf('USE_CURL_DEBUG', true);
	}

	/**
	*/
	function login($provider) {
		if (!$provider) {
			return false;
		}
		$normalized_info = array();
		$driver = _class('oauth_driver_'.$provider, 'classes/oauth/');
		$oauth_user_info = $driver->login();
		if ($oauth_user_info) {
			$normalized_info = $driver->_get_user_info_for_auth($oauth_user_info);
		}
		if ($normalized_info['user_id']) {
			$oauth_registration = db()->get('SELECT * FROM '.db('oauth_users').' WHERE provider="'._es($provider).'" AND provider_uid="'._es($normalized_info['user_id']).'"');
			if (!$oauth_registration) {
				db()->insert_safe('oauth_users', array(
					'provider'		=> $provider,
					'provider_uid'	=> $normalized_info['user_id'],
					'login'			=> $normalized_info['user_id'],
					'email'			=> $normalized_info['email'],
					'name'			=> $normalized_info['name'],
					'avatar_url'	=> $normalized_info['avatar_url'],
					'profile_url'	=> $normalized_info['profile_url'],
					'json_normalized'=> json_encode($normalized_info),
					'json_raw'		=> json_encode($oauth_user_info),
					'add_date'		=> time(),
					'user_id'		=> 0, // Here it is 0, will be updated later if OK
				));
				$oauth_user_id = db()->insert_id();
				if ($oauth_user_id) {
					$oauth_registration = db()->get('SELECT * FROM '.db('oauth_users').' WHERE provider="'._es($provider).'" AND provider_uid="'._es($normalized_info['user_id']).'" AND id='.intval($oauth_user_id));
				}
			}
			$sys_user_info = array();
			// merge oauth if user is logged in
			if (main()->USER_ID) {
				$sys_user_info = db()->get('SELECT * FROM '.db('user').' WHERE id='.intval(main()->USER_ID));
			}
// TODO: try to merge accounts by email if it is not empty
			if ($oauth_registration && !$oauth_registration['user_id']) {
				if (!$sys_user_info) {
					$login = $normalized_info['login'] ?: 'oauth_auto__'.$provider.'__'.$normalized_info['user_id'];
// TODO: auto-login user if email exists or show dialog to enter email
					$self_host = parse_url(WEB_PATH, PHP_URL_HOST);
					if (!$self_host) {
						$self_host = $_SERVER['HTTP_HOST'];
					}
					$email = $normalized_info['email'] ?: $login.'@'.$self_host;
					db()->insert_safe('user', array(
						'group'			=> 2,
						'login'			=> $login,
						'email'			=> $email,
						'name'			=> $normalized_info['name'] ?: $login,
						'nick'			=> $normalized_info['name'] ?: $login,
						'password'		=> md5(time().'some_salt'.uniqid()),
// TODO: make verification by email
						'active'		=> 1,
						'add_date'		=> time(),
						'verify_code'	=> md5(time().'some_salt'.uniqid()),
// TODO: add other fields: locale, lang, age, gender, location, birthday, avatar_url, profile_url
					));
					$sys_user_id = db()->insert_id();
					if ($sys_user_id) {
						$sys_user_info = db()->get('SELECT * FROM '.db('user').' WHERE id='.intval($sys_user_id));
					}
				}
				// Link oauth record with system user account
				if ($sys_user_info['id']) {
					db()->update_safe('oauth_users', array('user_id' => $sys_user_info['id']), 'id='.intval($oauth_registration['id']));
					$oauth_registration['user_id'] = $sys_user_info['id'];
				}
			}
			if ($oauth_registration['user_id'] && !$sys_user_info['id']) {
				$sys_user_info = db()->get('SELECT * FROM '.db('user').' WHERE id='.intval($oauth_registration['user_id']));
			}
			// Auto-login user if everything fine
			if ($oauth_registration['user_id'] && $sys_user_info['id'] && !main()->USER_ID) {
				_class('auth_user', 'classes/auth/')->_save_login_in_session($sys_user_info);
			}
		}
		if (DEBUG_MODE) {
			if ($oauth_user_info) {
				$body .= '<h1 class="text-success">User info</h1><pre><small>'.print_r($normalized_info, 1).'</small></pre>';
				$body .= '<h1 class="text-success">Raw user info</h1><pre><small>'.print_r($oauth_user_info, 1).'</small></pre>';
			} else {
				$body .= '<h1 class="text-error">Error</h1>';
			}
			$body .= '<pre><small>'.print_r($_SESSION['oauth'][$provider], 1).'</small></pre>';
		}
		return $body;
	}

	/**
	* Example of $this->_providers item (can also be empty):
	*	'github' => array(
	*		'user_info_url' => 'https://api.github.com/user',
	*	),
	*/
	function _load_oauth_providers() {
		$config = $this->_load_oauth_config();
		if (isset($this->_providers_loaded)) {
			return $this->_providers;
		}
		$paths = array(
			YF_PATH. 'classes/oauth/',
			PROJECT_PATH. 'classes/oauth/',
		);
		foreach ((array)_class('dir')->scan($paths, 1, '-f /yf_oauth_driver_[a-z0-9_-]+\.class\.php$/i') as $path) {
			$name = trim(substr(trim(basename($path)), strlen('yf_oauth_driver_'), -strlen('.class.php')));
			if (!$name) {
				continue;
			}
			if (!isset($config[$name])) {
				continue;
			}
			$p_config = $config[$name];
			if (!strlen($p_config['client_id']) || !strlen($p_config['client_secret'])) {
				continue;
			}
			$this->_providers[$name] = $data;
		}
		ksort($this->_providers);
		$this->_providers_loaded = true;
		return $this->_providers;
	}

	/**
	* Usually client_id and client_secret stored like this:
	* $oauth_config = array(
	*	'github' => array('client_id' => '_put_github_client_id_here_', 'client_secret' => '_put_github_client_secret_here_'),
	*	'google' => array('client_id' => '_put_google_client_id_here_', 'client_secret' => '_put_google_client_secret_here_'),
	*	...
	* )
	*/
	function _load_oauth_config() {
		global $oauth_config;
		return $oauth_config;
	}

	/**
	*/
	function _get_providers() {
		$providers = $this->_load_oauth_providers();
		return $this->_providers;
	}
}