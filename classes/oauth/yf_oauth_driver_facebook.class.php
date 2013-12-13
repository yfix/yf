<?php

load('oauth_driver', 'framework', 'classes/oauth/');
class yf_oauth_driver_facebook extends yf_oauth_driver {

	protected $url_authorize = 'https://www.facebook.com/dialog/oauth';
	protected $url_access_token = 'https://graph.facebook.com/oauth/access_token';
	protected $url_user = 'https://graph.facebook.com/me';
	protected $provider = 'facebook';

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
		$url = $this->url_access_token.'?'.http_build_query(array(
			'client_id'		=> $this->client_id,
			'client_secret' => $this->client_secret,
			'code'			=> $code,
			'redirect_uri' 	=> $this->redirect_uri,
		));
		$result = common()->get_remote_page($url, $cache = false, $opts, $response);
		// Force content_type here as facebook return text/plain, but in form urlencoded format
		$result = $this->_decode_result($result, array('content_type' => 'application/x-www-form-urlencoded'));
		if (isset($result['error']) || !is_array($result) || $response['http_code'] == 400) {
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
			'response_type' 	=> 'code',
#			'scope'				=> '',
		));
		return js_redirect($url, $url_rewrite = false);
	}
}