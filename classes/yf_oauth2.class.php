<?php

class yf_oauth2 {

	private $_providers = array();

	/**
	*/
	function _init() {
		foreach ($this as $k => $v) {
			$def[$k] = $v;
		}
		$this->_default_values = $def;
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
	function login($provider) {
		$providers = $this->_load_oauth_providers();
		$config = $this->_load_oauth_config();
		if (!$config[$provider]) {
			$this->error = 'Error: no config client_id and client_secret for provider: '.$provider;
			return false;
		}
		if (DEBUG_MODE) {
			$this->debug = true;
			$this->debug_http = true;
		}
		$this->server = $provider;
		$this->redirect_uri = _force_get_url(array('object' => $_GET['object'], 'action' => $_GET['action'], 'id' => $_GET['id']));
		$this->client_id = $config[$provider]['client_id'] ?: ''; $application_line = __LINE__;
		$this->client_secret = $config[$provider]['client_secret'] ?: '';
		if (strlen($this->client_id) == 0 || strlen($this->client_secret) == 0) {
			$this->error = 'Error: Please set the client_id with Key and client_secret with Secret. The URL must be '.$this->redirect_uri;
			return false;
		}
		$settings = $this->_providers[$provider];
		if (!$settings) {
			$this->error = 'Error: no settings for provider: '.$provider;
			return false;
		}
		foreach ((array)$settings as $k => $v) {
			$this->$k = $v;
		}

		$params = array(
			'client_id' 		=> $this->client_id,
			'redirect_uri' 		=> $this->redirect_uri,
#			'state' 			=> $state,
			'scope'				=> 12,//$this->scope,
			'response_type' 	=> 'code',
#			'approval_prompt'   => 'force', // - google force-recheck
			'v'					=> '5.5',
		);
		$url_authorize = 'http://oauth.vk.com/authorize';
		if (!$_GET['code']) {
			main()->NO_GRAPHICS = true;
			$url = $url_authorize.'?'.http_build_query($params);
#echo $url;
			$this->_redirect($url);
			exit;
		}
/*
#		if ($_SESSION['oauth'][$provider]['token']) {
#			$success = $_SESSION['oauth'][$provider]['token'];
#			$user = $_SESSION['oauth'][$provider]['user_info'];
#		} else {
			$error = 'Cannot process';
			if (($success = $this->process())) {
				if (strlen($this->access_token)) {
					$error = '';
					$func = $this->get_user_info_callback;
					$user = $func($settings, $this);
					if ($user) {
						$success = true;
					}
#					$_SESSION['oauth'][$provider]['token'] = $this->access_token;
#					$_SESSION['oauth'][$provider]['user_info'] = $user;
				} else {
					$error = $this->authorization_error;
				}
#			}
		}
		$body = $this->output();

		if ($error) {
			return $body.'<h1 class="text-error">Error: '.$error.'</h1>'.(DEBUG_MODE ? '<pre>'.print_r($this, 1).'</pre>' : '');
		} elseif ($success) {
			return $body.'<h1 class="text-success">Success</h1><pre>'.print_r($user, 1).'</pre>';
		}
*/
	}

	/**
	*/
	function _get_providers() {
		$providers = $this->_load_oauth_providers();
		return $this->_providers;
	}

	/**
	*/
	function _redirect($url) {
		header('HTTP/1.0 302 OAuth _redirection');
		header('Location: '.$url);
	}
}