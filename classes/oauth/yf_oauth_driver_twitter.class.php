<?php

load('oauth_driver', 'framework', 'classes/oauth/');
class yf_oauth_driver_twitter extends yf_oauth_driver {

	protected $url_authorize = 'https://api.twitter.com/oauth/authorize';
	protected $url_access_token = 'https://api.twitter.com/oauth/access_token';
	protected $url_user = 'https://api.twitter.com/1/users/lookup.json';// ?user_id={user_id}';
	protected $provider = 'twitter';

// TODO: oauth v1

	/**
	*/
	function get_user_info() {
/*
		if (!$_SESSION['oauth'][$this->provider]['access_token']) {
			$this->get_access_token();
		}
		if (!$_SESSION['oauth'][$this->provider]['access_token']) {
			return false;
		}
		if (!$_SESSION['oauth'][$this->provider]['user']) {
			$url = $this->url_user.'?'.http_build_query(array(
				'user_id'		=> $_SESSION['oauth'][$this->provider]['access_token_request']['result']['user_id'],
				'access_token'	=> $_SESSION['oauth'][$this->provider]['access_token'],
				'v'				=> '5.5',
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
*/
	}

	/**
	*/
	function get_access_token () {
/*
		if ($_SESSION['oauth'][$this->provider]['access_token']) {
			return $_SESSION['oauth'][$this->provider]['access_token'];
		}
		$code = $_GET['code'];
		if (!$code) {
			return $this->authorize();
		}
		$url = $this->url_access_token.'?'.http_build_query(array(
			'client_id'		=> $this->client_id,
			'client_secret' => $this->client_secret,
			'code'			=> $code,
			'redirect_uri' 	=> $this->redirect_uri,
			'v'				=> '5.5',
		));
		$result = common()->get_remote_page($url, $cache = false, $opts, $response);
		$result = $this->_decode_result($result, $response);
		if ($response['http_code'] == 401) {
			return js_redirect( $this->redirect_uri, $url_rewrite = false );
		} elseif ($response['http_code'] == 200) {
			$_SESSION['oauth'][$this->provider]['access_token_request'] = array(
				'result'	=> $result,
				'response'	=> $response,
			);
			$_SESSION['oauth'][$this->provider]['access_token'] = $result['access_token'];
		}
		return $_SESSION['oauth'][$this->provider]['access_token'];
*/
	}

	/**
	*/
	function authorize () {
/*
		$url = $this->url_authorize.'?'.http_build_query(array(
			'client_id' 		=> $this->client_id,
			'redirect_uri' 		=> $this->redirect_uri,
			'scope'				=> 'offline,wall,friends,email', // Comma or space separated names
			'response_type' 	=> 'code',
			'v'					=> '5.5',
		));
		return js_redirect($url, $url_rewrite = false);
*/
	}
}