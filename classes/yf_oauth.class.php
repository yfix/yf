<?php

class yf_oauth {

	private $_providers = array();

	/**
	*/
	function _init() {
// TODO: create special section in debug panel with curl requests inside
		conf('USE_CURL_DEBUG', true);
	}

	/**
	*/
	function login($provider) {
		$user_info = _class('oauth_driver_'.$provider, 'classes/oauth/')->login();
		if ($user_info) {
			return '<h1 class="text-success">User info</h1>
				<pre><small>'.print_r($user_info, 1).'</small></pre>
				<pre><small>'.print_r($_SESSION['oauth'][$provider], 1).'</small></pre>';
		}
		return '<h1 class="text-error">Error</h1>';
	}

	/**
	* Example of $this->_providers item:
	*	'github' => array(
	*		'oauth_version' => '2.0',
	*		'dialog_url' => 'https://github.com/login/oauth/authorize?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={STATE}',
	*		'access_token_url' => 'https://github.com/login/oauth/access_token',
	*		'user_info_url' => 'https://api.github.com/user',
	*	),
	*/
	function _load_oauth_providers() {
		if (isset($this->_providers_loaded)) {
			return $this->_providers;
		}
/*
		$paths = array(
			YF_PATH. 'share/oauth_providers/',
			PROJECT_PATH. 'share/oauth_providers/',
		);
		foreach ((array)_class('dir')->scan($paths, 1, '-f /[a-z0-9_-]+\.php$/i') as $path) {
			$name = trim(substr(trim(basename($path)), 0, -strlen('.php')));
			if (!$name) {
				continue;
			}
			require_once $path;
			$this->_providers[$name] = $data;
		}
*/
		$paths = array(
			YF_PATH. 'classes/oauth/',
		);
		foreach ((array)_class('dir')->scan($paths, 1, '-f /yf_oauth_driver_[a-z0-9_-]+\.class\.php$/i') as $path) {
			$name = trim(substr(trim(basename($path)), strlen('yf_oauth_driver_'), -strlen('.class.php')));
			if (!$name) {
				continue;
			}
			$this->_providers[$name] = $data;
		}
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