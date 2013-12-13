<?php

abstract class yf_oauth_driver {

	protected $url_authorize = '';
	protected $url_access_token = '';
	protected $url_user = '';
	protected $provider = '';
	protected $scope = '';
	protected $storage = array();

	/**
	*/
	function _init() {
		$this->storage = &$_SESSION['oauth'][$this->provider];
	}

	/**
	*/
	function login() {
		$config = _class('oauth')->_load_oauth_config();
		if (!$config[$this->provider] || !$config[$this->provider]['client_id'] || !$config[$this->provider]['client_secret']) {
			return '<h1 class="text-error">Error: no config client_id and client_secret for provider: '.$this->provider.'</h1>';
		}
		$this->redirect_uri = _force_get_url(array('object' => $_GET['object'], 'action' => $_GET['action'], 'id' => $_GET['id']));
		$this->client_id = $config[$this->provider]['client_id'] ?: ''; $application_line = __LINE__;
		$this->client_secret = $config[$this->provider]['client_secret'] ?: '';
		$this->client_public = $config[$this->provider]['client_public'] ?: '';
		return $this->get_user_info();
	}

	/**
	*/
	function get_user_info() {
		$access_token = $this->_storage_get('access_token');
		if (!$access_token) {
			$access_token = $this->get_access_token();
			if (!$access_token) {
				return false;
			}
		}
		if (!$this->_storage_get('user')) {
			$url = $this->url_user.'?'.http_build_query(array(
				'access_token'	=> $access_token,
			));
			$result = common()->get_remote_page($url, $cache = false, $opts, $response);
			$result = $this->_decode_result($result, $response, __FUNCTION__);
			$this->_storage_set('user_info_request', array('result' => $result, 'response' => $response));
			$this->_storage_set('user', $result);
		}
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
		$url = $this->url_access_token;
		$opts = array(
			'post'	=> array(
				'client_id'		=> $this->client_id,
				'client_secret' => $this->client_secret,
				'redirect_uri' 	=> $this->redirect_uri,
				'code'			=> $code,
			),
		);
		$result = common()->get_remote_page($url, $cache = false, $opts, $response);
		$result = $this->_decode_result($result, $response, __FUNCTION__);
		if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
			return js_redirect( $this->redirect_uri, $url_rewrite = false );
		} else {
			$this->_storage_set('access_token_request', array('result' => $result, 'response' => $response));
			$this->_storage_set('access_token', $result['access_token']);
		}
		return $this->_storage_get('access_token');
	}

	/**
	*/
	function authorize() {
		$url = $this->url_authorize.'?'.http_build_query(array(
			'client_id' 	=> $this->client_id,
			'redirect_uri' 	=> $this->redirect_uri,
			'scope'			=> $this->scope,
			'response_type' => 'code',
			'state'			=> md5(microtime().rand(1,10000000)), // An unguessable random string. It is used to protect against cross-site request forgery attacks.
		));
		return js_redirect($url, $url_rewrite = false);
	}

	/**
	*/
	function _decode_result($result, $response, $for_method = '') {
		if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {

			$result = _class('utils')->object_to_array(json_decode($result));

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
		return isset($this->storage[$key]) ? $this->storage[$key] : false;
	}

	/**
	*/
	function _storage_set($key, $val = null) {
		$this->storage[$key] = $val;
		return $val;
	}
}