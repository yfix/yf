<?php

load('oauth_driver', 'framework', 'classes/oauth/');
class yf_oauth_driver_github extends yf_oauth_driver {

	protected $url_authorize = 'https://github.com/login/oauth/authorize';
	protected $url_access_token = 'https://github.com/login/oauth/access_token';
	protected $url_user = 'https://api.github.com/user';
	protected $provider = 'github';

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
			$url = $this->url_user.'?'.http_build_query(array(
				'access_token'	=> $_SESSION['oauth'][$this->provider]['access_token'],
			));
			$result = common()->get_remote_page($url, $cache = false, $opts, $response);
			$result = $this->_decode_result($result, $response);
			$_SESSION['oauth'][$this->provider]['user_info_request'] = array(
				'result'	=> $result,
				'response'	=> $response,
			);
			$_SESSION['oauth'][$this->provider]['user'] = $result;

			// Emails
			$url_emails = $this->url_user.'/emails?'.http_build_query(array(
				'access_token'	=> $_SESSION['oauth'][$this->provider]['access_token'],
			));
			$result = common()->get_remote_page($url_emails, $cache = false, $opts = array(), $response);
			$result = $this->_decode_result($result, $response);
			$_SESSION['oauth'][$this->provider]['user']['emails'] = $result;
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
				'code'			=> $code,
				'redirect_uri' 	=> $this->redirect_uri,
			),
		);
		$result = common()->get_remote_page($url, $cache = false, $opts, $response);
		$result = $this->_decode_result($result, $response);
		if (isset($result['error']) && strlen($result['error']) || !is_array($result)) {
			return js_redirect( $this->redirect_uri, $url_rewrite = false );
		} else {
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
			'client_id' 	=> $this->client_id,
			'redirect_uri' 	=> $this->redirect_uri,
			'scope'			=> 'user', // http://developer.github.com/v3/oauth/#scopes // user Read/write access to profile info only. Note: this scope includes user:email and user:follow.
// TODO save this in session and implement for all other providers too to prevent CSRF
			'state'			=> md5(microtime().rand(1,10000000)), // An unguessable random string. It is used to protect against cross-site request forgery attacks.
		));
		return js_redirect($url, $url_rewrite = false);
	}
}