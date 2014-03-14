<?php

abstract class yf_oauth_driver2 {

	protected $provider = '';
	protected $url_authorize = '';
	protected $url_access_token = '';
	protected $url_user = '';
	protected $scope = '';
	protected $get_access_token_method = 'POST';
	protected $url_params = array();
	protected $url_params_authorize = array();
	protected $url_params_access_token = array();
	protected $url_params_user_info = array();
	protected $storage = array();
	protected $redirect_uri = '';
	protected $client_id = '';
	protected $client_secret = '';
	protected $client_public = '';
	protected $get_user_info_user_bearer = false;
	protected $redirect_uri_force_https = false;

// TODO: refresh_token

	/**
	*/
	abstract function _get_user_info_for_auth($raw = array());

	/**
	*/
	function login($params = array()) {
		$config = _class('oauth')->_load_oauth_config();
		$this->provider = substr(get_called_class(), strlen(__CLASS__)); // yf_oauth_driver_vk, yf_oauth_driver2
		if (!$config[$this->provider] || !$config[$this->provider]['client_id'] || !$config[$this->provider]['client_secret']) {
			return '<h1 class="text-error">Error: no config client_id and client_secret for provider: '.$this->provider.'</h1>';
		}
		$this->redirect_uri = _force_get_url(array('object' => $_GET['object'], 'action' => $_GET['action'], 'id' => $_GET['id']));
		if ($this->redirect_uri_force_https) {
			$this->redirect_uri = str_replace('http://', 'https://', $this->redirect_uri);
		}
		$this->client_id = $config[$this->provider]['client_id'] ?: ''; $application_line = __LINE__;
		$this->client_secret = $config[$this->provider]['client_secret'] ?: '';
		$this->client_public = $config[$this->provider]['client_public'] ?: '';
		return $this->get_user_info();
	}

	/**
	*/
	function get_user_info() {
		if (DEBUG_MODE && $_GET['oauth_clean']) {
			$this->_storage_clean();
		}
		$access_token = $this->_storage_get('access_token');
		if (!$access_token) {
			$access_token = $this->get_access_token();
			if (!$access_token) {
				$this->_storage_clean();
				js_redirect( $this->redirect_uri, $url_rewrite = false );
				return false;
			}
		}
#		if (!$this->_storage_get('user')) {
			$url = $this->url_user.'?'.http_build_query((array)$this->url_params + (array)$this->url_params_user_info + array(
				'access_token'	=> $access_token,
			));
			if ($this->get_user_info_user_bearer) {
				$url = $this->url_user;
				$opts = array(
					'custom_header'	=> array('Authorization: Bearer '.$access_token),
				);
			}
			$result = common()->get_remote_page($url, $cache = false, $opts, $response);
			$result = $this->_decode_result($result, $response, __FUNCTION__);
			if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
				$this->_storage_clean();
				js_redirect( $this->redirect_uri, $url_rewrite = false );
				return false;
			} else {
				$this->_storage_set('user_info_request', array('result' => $result, 'response' => $response));
				$this->_storage_set('user', $result);
			}
#		}
		return $this->_storage_get('user');
	}

	/**
	*/
	function get_access_token() {
		$access_token = $this->_storage_get('access_token');
		if ($access_token) {
			return $access_token;
		}
		$code = $_GET['code'];
		if (!$code) {
			return $this->authorize();
		}
		$url_params = (array)$this->url_params + (array)$this->url_params_access_token + array(
			'client_id'		=> $this->client_id,
			'client_secret' => $this->client_secret,
			'redirect_uri' 	=> $this->redirect_uri,
			'code'			=> $code,
		);
		if ($this->get_access_token_method == 'POST') {
			$url = $this->url_access_token;
			$opts = array(
				'post'	=> $url_params,
			);
		} else {
			$url = $this->url_access_token.'?'.http_build_query($url_params);
		}
		$result = common()->get_remote_page($url, $cache = false, $opts, $response);
		$result = $this->_decode_result($result, $response, __FUNCTION__);
		if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
			js_redirect( $this->redirect_uri, $url_rewrite = false );
			return false;
		} else {
			$this->_storage_set('access_token_request', array('result' => $result, 'response' => $response));
			$this->_storage_set('access_token', $result['access_token']);
		}
		return $this->_storage_get('access_token');
	}

	/**
	*/
	function authorize() {
		$url = $this->url_authorize.'?'.http_build_query((array)$this->url_params + (array)$this->url_params_authorize + array(
			'client_id' 	=> $this->client_id,
			'redirect_uri' 	=> $this->redirect_uri,
			'scope'			=> $this->scope,
			'response_type' => 'code',
			'state'			=> md5(microtime().rand(1,10000000)), // An unguessable random string. It is used to protect against cross-site request forgery attacks.
		));
		js_redirect($url, $url_rewrite = false);
		return false;
	}

	/**
	*/
	function _decode_result($result, $response, $for_method = '') {
		if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
			$result = json_decode($result, $assoc = true);
		} elseif (strpos($response['content_type'], 'application/x-www-form-urlencoded') !== false) {
			parse_str($result, $try_parsed);
			if (is_array($try_parsed) && count($try_parsed) > 1) {
				$result = $try_parsed;
			}
		}
		return $result;
	}

	/**
	*/
	function _storage_get($key) {
		return isset($_SESSION['oauth'][$this->provider][$key]) ? $_SESSION['oauth'][$this->provider][$key] : false;
	}

	/**
	*/
	function _storage_set($key, $val = null) {
		$_SESSION['oauth'][$this->provider][$key] = $val;
		return $val;
	}

	/**
	*/
	function _storage_clean() {
		if (isset($_SESSION['oauth'][$this->provider])) {
			unset($_SESSION['oauth'][$this->provider]);
		}
	}
}