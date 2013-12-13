<?php

load('oauth_driver1', 'framework', 'classes/oauth/');
class yf_oauth_driver_yahoo extends yf_oauth_driver1 {

	protected $url_request_token = 'https://api.login.yahoo.com/oauth/v2/get_request_token';
	protected $url_access_token = 'https://api.login.yahoo.com/oauth/v2/get_token';
	protected $url_authenticate = 'https://api.login.yahoo.com/oauth/v2/request_auth';
	protected $url_user = 'http://query.yahooapis.com/v1/yql';
#	protected $oauth_version = '1.0a';
#	protected $authorization_header = false;

// TODO

	/**
	*/
#	function _decode_result($result, $response, $for_method = '') {
#		// Force content_type here as facebook return text/plain, but in form urlencoded format
#		if ($for_method == 'authorize') {
#			$response['content_type'] = 'application/x-www-form-urlencoded';
#		}
#		return parent::_decode_result($result, $response, $for_method);
#	}
}