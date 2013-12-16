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
		$user_info = _class('oauth_driver_'.$provider, 'classes/oauth/')->login();
		if ($user_info) {
			$body .= '<h1 class="text-success">User info</h1><pre><small>'.print_r($user_info, 1).'</small></pre>';
		} else {
			$body .= '<h1 class="text-error">Error</h1>';
		}
		if (DEBUG_MODE) {
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