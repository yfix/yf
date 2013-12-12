<?php

load('oauth_driver', 'framework', 'classes/oauth/');
class yf_oauth_driver_odnoklassniki extends yf_oauth_driver {

	protected $url_authorize = 'http://www.odnoklassniki.ru/oauth/authorize';
	protected $url_access_token = 'http://api.odnoklassniki.ru/oauth/token.do';
	protected $url_user = 'http://api.odnoklassniki.ru/fb.do';
	protected $provider = 'odnoklassniki';

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
			$method = 'users.getCurrentUser';
			$access_token = $_SESSION['oauth'][$this->provider]['access_token'];
			$sign = md5('application_key='.$this->client_public. 'method='. $method. md5($access_token. $this->client_secret));
			$url = $this->url_user.'?'.http_build_query(array(
				'access_token'		=> $access_token,
				'application_key'	=> $this->client_public,
				'method'			=> $method,
				'sig'				=> $sign,
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
			'client_id' 		=> $this->client_id,
			'redirect_uri' 		=> $this->redirect_uri,
			'scope'				=> 'SET_STATUS;VALUABLE_ACCESS',
			'response_type' 	=> 'code',
#			'layout'			=> 'm', // http://apiok.ru/wiki/pages/viewpage.action?pageId=42476652    layout ="m"- мобильная форма авторизации, если не используете iOS или Android интеграцию
		));
		return js_redirect($url, $url_rewrite = false);
	}
}