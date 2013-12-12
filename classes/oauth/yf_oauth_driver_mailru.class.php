<?php

load('oauth_driver', 'framework', 'classes/oauth/');
class yf_oauth_driver_mailru extends yf_oauth_driver {

	protected $url_authorize = 'https://connect.mail.ru/oauth/authorize';
	protected $url_access_token = 'https://connect.mail.ru/oauth/token';
	protected $url_user = 'http://www.appsmail.ru/platform/api';
	protected $provider = 'mailru';

	/**
	*/
	function get_user_info() {
		if (!$_SESSION['oauth'][$this->provider]['access_token']) {
			$this->get_access_token();
		}
		if (!$_SESSION['oauth'][$this->provider]['access_token']) {
			return false;
		}
		if (!$_SESSION['oauth'][$this->provider]['user']) {
			$method = 'users.getInfo';
			$access_token = $_SESSION['oauth'][$this->provider]['access_token'];
			$sign = md5('app_id='.$this->client_id. 'method='. $method. 'secure=1'. 'session_key='.$access_token. $this->client_public);
			$url = $this->url_user.'?'.http_build_query(array(
				'session_key'	=> $access_token,
				'secure'		=> 1,
				'app_id'		=> $this->client_id,
				'method'		=> $method,
				'sig'			=> $sign,
			));
			$result = common()->get_remote_page($url, $cache = false, $opts, $response);
			$result = $this->_decode_result($result, $response);
			$_SESSION['oauth'][$this->provider]['user_info_request'] = array(
				'result'	=> $result,
				'response'	=> $response,
			);
			$_SESSION['oauth'][$this->provider]['user'] = $result;
		}
		return $_SESSION['oauth'][$this->provider]['user'];
	}

	/**
	*/
	function get_access_token () {
		if ($_SESSION['oauth'][$this->provider]['access_token']) {
			return $_SESSION['oauth'][$this->provider]['access_token'];
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
				'code'			=> $_GET['code'],
				'grant_type'	=> 'authorization_code',
			),
		);
		$result = common()->get_remote_page($url, $cache = false, $opts, $response);
		$result = $this->_decode_result($result, $response);
		if (substr($response['http_code'], 0, 1) == '4') { // 4xx
			return js_redirect( $this->redirect_uri, $url_rewrite = false );
		} elseif ($response['http_code'] == 200) {
			$_SESSION['oauth'][$this->provider]['access_token_request'] = array(
				'result'	=> $result,
				'response'	=> $response,
			);
			$_SESSION['oauth'][$this->provider]['access_token'] = $result['access_token'];
		}
		return $_SESSION['oauth'][$this->provider]['access_token'];
	}

	/**
	*/
	function authorize () {
		$url = $this->url_authorize.'?'.http_build_query(array(
			'client_id' 		=> $this->client_id,
			'redirect_uri' 		=> $this->redirect_uri,
			'response_type' 	=> 'code',
#			'scope'				=> '',
		));
		return js_redirect($url, $url_rewrite = false);
	}
}