<?php

class yf_oauth {

	private $_providers = array();

	/**
	*/
	function login($provider) {
		$providers = $this->_load_oauth_providers();
		$config = $this->_load_oauth_config();
		if (!$config[$provider] || !$config[$provider]['client_id'] || !$config[$provider]['client_secret']) {
			$this->error = 'Error: no config client_id and client_secret for provider: '.$provider;
			return false;
		}
		$this->server = $provider;
		$this->redirect_uri = _force_get_url(array('object' => $_GET['object'], 'action' => $_GET['action'], 'id' => $_GET['id']));
		$this->client_id = $config[$provider]['client_id'] ?: ''; $application_line = __LINE__;
		$this->client_secret = $config[$provider]['client_secret'] ?: '';
		$settings = $this->_providers[$provider];
		if (!$settings) {
			$this->error = 'Error: no settings for provider: '.$provider;
			return false;
		}
		foreach ((array)$settings as $k => $v) {
			$this->$k = $v;
		}

if ($provider == 'vk') {

		if ($_SESSION['oauth'][$provider]['access_token']) {
			$body = '';
			if ($_SESSION['oauth'][$provider]['user']) {
				$body .= '<h4>user</h4><pre>'.print_r($_SESSION['oauth'][$provider]['user'], 1).'</pre>';
			} else {
				$url = 'https://api.vk.com/method/users.get?'.http_build_query(array(
					'user_id'		=> $_SESSION['oauth'][$provider]['access_token_request']['result']['user_id'],
					'access_token'	=> $_SESSION['oauth'][$provider]['access_token'],
					'v'				=> '5.5',
				));
				$result = common()->get_remote_page($url, $cache = false, $opts = array(), $response);
				if (strpos($response['content_type'], 'json') !== false) {
					$result = _class('utils')->object_to_array(json_decode($result));
				}
				$_SESSION['oauth'][$provider]['user_info_request'] = array(
					'result'	=> $result,
					'response'	=> $response,
				);
				$_SESSION['oauth'][$provider]['user'] = $result;
				$body .= '<h4>user</h4><pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
			$user_info_request = $_SESSION['oauth'][$provider]['user_info_request'];
			if ($user_info_request) {
				$arr = $user_info_request;
				$body .= '<h4>user_info_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			$access_token_request = $_SESSION['oauth'][$provider]['access_token_request'];
			if ($access_token_request) {
				$arr = $access_token_request;
				$body .= '<h4>access_token_request</h4>Result:<pre>'.print_r($arr['result'], 1).'</pre>Response:<pre>'.print_r($arr['response'], 1).'</pre>';
			}
			return $body;
		}
		if ($_GET['code'] || $_GET['error']) {
			if ($_GET['error']) {
				return '<h1 class="text-error">Error: '.$_GET['error'].'</h1>';
			} elseif ($_GET['code']) {
				$url = 'https://oauth.vk.com/access_token?'.http_build_query(array(
					'client_id'		=> $this->client_id,
					'client_secret' => $this->client_secret,
					'code'			=> $_GET['code'],
					'redirect_uri' 	=> $this->redirect_uri,
					'v'				=> '5.5',
				));
				$response = array(); // Will be filled with debug information about request
				$result = common()->get_remote_page($url, $cache = false, $opts = array(), $response);
				if ($response['http_code'] == 401) {
					return js_redirect( $this->redirect_uri, $url_rewrite = false );
				} elseif ($response['http_code'] == 200) {
					if (strpos($response['content_type'], 'json') !== false) {
						$result = _class('utils')->object_to_array(json_decode($result));
					}
					$_SESSION['oauth'][$provider]['access_token_request'] = array(
						'result'	=> $result,
						'response'	=> $response,
					);
					$_SESSION['oauth'][$provider]['access_token'] = $result['access_token'];
				}
				return '<pre>'.print_r($result, 1).'</pre><br>'.PHP_EOL.'<pre>'.print_r($response, 1).'</pre>';
			}
		} else {
			$url = 'https://oauth.vk.com/authorize?'.http_build_query(array(
				'client_id' 		=> $this->client_id,
				'redirect_uri' 		=> $this->redirect_uri,
				'scope'				=> 'offline,wall,friends,email', // Comma or space separated names
				'response_type' 	=> 'code',
				'v'					=> '5.5',
			));
			return js_redirect($url, $url_rewrite = false);
		}

} elseif ($provider == 'odnoklassniki') {

// TODO

} elseif ($provider == 'github') {

// TODO

}
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
	function _get_providers() {
		$providers = $this->_load_oauth_providers();
		return $this->_providers;
	}
}