<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
abstract class yf_oauth_driver1 extends yf_oauth_driver2 {

	protected $url_request_token = '';
	protected $url_access_token = '';
	protected $url_authenticate = '';
	protected $url_user = '';
	protected $scope = '';
	protected $oauth_version = '1.0';
	protected $access_token_use_header = true;
	protected $url_params = array();
	protected $url_params_authorize = array();
	protected $url_params_authenticate = array();
	protected $url_params_request_token = array();
	protected $url_params_access_token = array();
	protected $url_params_user_info = array();

	/**
	*/
	function get_user_info() {
#$this->_storage_clean();
		$access_token = $this->_storage_get('access_token');
		$access_token_secret = $this->_storage_get('access_token_secret');
		if (!$access_token || !$access_token_secret) {
			$access_token = $this->get_access_token();
			$access_token_secret = $this->_storage_get('access_token_secret');
			if (!$access_token || !$access_token_secret) {
#				$this->_storage_clean();
				js_redirect( $this->redirect_uri, $url_rewrite = false );
				return false;
			}
		}
		if (!$this->_storage_get('user')) {
			$user_id = $this->_storage_get('user_id');
			$url = $this->url_user.'?'.http_build_query($this->url_params + (array)$this->url_params_user_info + array(
				'user_id'	=> $user_id,
			));
			$this->_storage_set('nonce', md5(microtime().rand(1,10000000)));
			$this->_storage_set('last_time', time());
			$params = array(
				'oauth_version'			=> $this->oauth_version,
				'oauth_consumer_key'	=> $this->client_id,
				'oauth_nonce'			=> $this->_storage_get('nonce'),
				'oauth_timestamp'		=> $this->_storage_get('last_time'),
				'oauth_signature_method'=> 'HMAC-SHA1',
				'oauth_token'			=> $access_token,
			) + (array)$this->url_params + (array)$this->url_params_user_info;
			$opts = array(
				'custom_header' => $this->_get_oauth_header($this->url_user, $params, 'GET', $access_token_secret, array('user_id' => $user_id)),
			);
			$result = common()->get_remote_page($url, $cache = false, $opts, $response);
			$result = $this->_decode_result($result, $response, __FUNCTION__);
			if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
#				$this->_storage_clean();
				js_redirect( $this->redirect_uri, $url_rewrite = false );
				return false;
			} else {
				$this->_storage_set('user_info_request', array('result' => $result, 'response' => $response));
				$this->_storage_set('user', $result);
			}
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
		$oauth_verifier = $_GET['oauth_verifier'];
		$oauth_token	= $_GET['oauth_token'];
		if ((!$oauth_verifier || !$oauth_token) && !$this->_storage_get('oauth_verifier')) {
			return $this->authenticate();
		}

		$request_token = $this->_storage_get('request_token');

		$this->_storage_set('nonce', md5(microtime().rand(1,10000000)));
		$this->_storage_set('last_time', time());

		$params = array(
			'oauth_version'			=> $this->oauth_version,
			'oauth_consumer_key'	=> $this->client_id,
			'oauth_nonce'			=> $this->_storage_get('nonce'),
			'oauth_timestamp'		=> $this->_storage_get('last_time'),
			'oauth_signature_method'=> 'HMAC-SHA1',
			'oauth_token'			=> $oauth_token,
			'oauth_verifier'		=> $oauth_verifier,
		) + (array)$this->url_params + (array)$this->url_params_access_token;
		$url = $this->url_access_token;
		if ($this->access_token_use_header) {
			$opts = array(
				'post'	=> array(
					'oauth_verifier' => $oauth_verifier,
				),
				'custom_header' => $this->_get_oauth_header($url, $params),
			);
		} else {
			$opts = array(
				'post'	=> $params,
				'custom_header' => $this->_get_oauth_header($url, $params, 'POST', $request_token['oauth_token_secret']),
			);
		}
		$result = common()->get_remote_page($url, $cache = false, $opts, $response);
		$result = $this->_decode_result($result, array('content_type' => 'application/x-www-form-urlencoded') + $response, __FUNCTION__);
		if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
			js_redirect( $this->redirect_uri, $url_rewrite = false );
			return false;
		} else {
			$this->_storage_set('access_token_request', array('result' => $result, 'response' => $response));
			$this->_storage_set('access_token', $result['oauth_token']);
			$this->_storage_set('access_token_secret', $result['oauth_token_secret']);
			$this->_storage_set('user_id', $result['user_id']);
		}
		$this->_storage_set('oauth_verifier', $oauth_verifier);
		return $this->_storage_get('access_token');
	}

	/**
	*/
	function authenticate() {
		$request_token_info = $this->_storage_get('request_token');
		if (!$request_token_info) {
			return $this->authorize();
		}
		$url = $this->url_authenticate.'?'.http_build_query((array)$this->url_params + (array)$this->url_params_authenticate + array(
			'oauth_token' 	=> $request_token_info['oauth_token'],
		));
		js_redirect($url, $url_rewrite = false);
		return false;
	}

	/**
	*/
	function authorize() {
		$request_token_info = $this->_storage_get('request_token');
		if ($request_token_info) {
			return $request_token_info;
		}
		$url = $this->url_request_token;

		$this->_storage_set('nonce', md5(microtime().rand(1,10000000)));
		$this->_storage_set('last_time', time());

		$params = array(
			'oauth_version'			=> $this->oauth_version,
			'oauth_callback'		=> $this->redirect_uri,
			'oauth_consumer_key'	=> $this->client_id,
			'oauth_nonce'			=> $this->_storage_get('nonce'),
			'oauth_timestamp'		=> $this->_storage_get('last_time'),
			'oauth_signature_method'=> 'HMAC-SHA1',
		) + (array)$this->url_params + (array)$this->url_params_authorize;
		$opts = array(
			'post'	=> array(),
			'custom_header' => $this->_get_oauth_header($url, $params),
		);
		$result = common()->get_remote_page($url, $cache = false, $opts, $response);
		$result = $this->_decode_result($result, array('content_type' => 'application/x-www-form-urlencoded') + $response, __FUNCTION__);
		$this->_storage_set('authorize_request', array('result' => $result, 'response' => $response));
		if ($result['oauth_token'] && $result['oauth_token_secret']) {
			$this->_storage_set('request_token', $result);
			return $result;
		}
		return false;
	}

	/**
	*/
	function _get_oauth_header($url, $params, $method = 'POST', $oauth_token_secret = '', $add_to_sign = array()) {
		ksort($params);
		$params['oauth_signature'] = $this->_do_sign_request($url, $params + (array)$add_to_sign, $method, $oauth_token_secret);
		$keyval = array();
		foreach($params as $k => $v) {
			$keyval[$k] = $k.'="'.$v.'"';
		}
		return 'Authorization: OAuth '.implode(', ', $keyval);
	}

	/**
	*/
	function _do_sign_request($url, $params, $method = 'POST', $oauth_token_secret = '') {
		$sign_str = array();
		ksort($params);
		foreach ($params as $k => $v) {
			$sign_str[$k] = $k.'="'.$this->_encode($v).'"';
		}
		$sign_str = $method. '&'. $this->_encode($url). '&'. $this->_encode(http_build_query($params));
		// $oauth_token_secret here is empty, it is ok    http://habrahabr.ru/post/145988/
		$sign = $this->_hmac_sha1($sign_str, $this->client_secret.'&'.$oauth_token_secret);
		$sign = $this->_encode(base64_encode($sign));
		return $sign;
	}

	/**
	*/
	function _hmac_sha1($data, $key) {
		$pack = 'H40';
		if (strlen($key) > 64) {
			$key = pack($pack, sha1($key));
		}
		if (strlen($key) < 64) {
			$key = str_pad($key, 64, "\0");
		}
		return pack($pack, sha1( (str_repeat("\x5c", 64) ^ $key) .pack($pack, sha1(	(str_repeat("\x36", 64) ^ $key)	.$data )) ));
	}

	/**
	*/
	function _encode($value) {
		return is_array($value) ? $this->_encode_array($value) : str_replace('%7E', '~', str_replace('+',' ', rawurlencode($value)));
	}

	/**
	*/
	function _encode_array($array) {
		foreach($array as $key => $value) {
			$array[$key] = $this->_encode($value);
		}
		return $array;
	}
}