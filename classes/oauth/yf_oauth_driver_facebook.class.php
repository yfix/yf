<?php

load('oauth_driver', 'framework', 'classes/oauth/');
class yf_oauth_driver_facebook extends yf_oauth_driver {

	protected $url_authorize = 'https://www.facebook.com/dialog/oauth';
	protected $url_access_token = 'https://graph.facebook.com/oauth/access_token';
	protected $url_user = 'https://graph.facebook.com/me';
	protected $provider = 'facebook';
	protected $scope = '';

	/**
	*/
	function get_access_token () {
		$access_token = $this->_storage_get('access_token');
		if ($access_token) {
			return $access_token;
		}
		$code = $_GET['code'];
		if (!$code) {
			return $this->authorize();
		}
		$url = $this->url_access_token.'?'.http_build_query(array(
			'client_id'		=> $this->client_id,
			'client_secret' => $this->client_secret,
			'redirect_uri' 	=> $this->redirect_uri,
			'code'			=> $code,
		));
		$result = common()->get_remote_page($url, $cache = false, $opts, $response);

		// Force content_type here as facebook return text/plain, but in form urlencoded format
		$result = $this->_decode_result($result, array('content_type' => 'application/x-www-form-urlencoded'));

		if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
			return js_redirect( $this->redirect_uri, $url_rewrite = false );
		} else {
			$this->_storage_set('access_token_request', array('result'	=> $result, 'response' => $response));
			$this->_storage_set('access_token', $result['access_token']);
		}
		return $this->_storage_get('access_token');
	}
}