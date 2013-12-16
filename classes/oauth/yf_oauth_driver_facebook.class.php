<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_facebook extends yf_oauth_driver2 {

	protected $url_authorize = 'https://www.facebook.com/dialog/oauth';
	protected $url_access_token = 'https://graph.facebook.com/oauth/access_token';
	protected $url_user = 'https://graph.facebook.com/me';
	protected $scope = '';
	protected $get_access_token_method = 'GET';

	/**
	*/
	function _decode_result($result, $response, $for_method = '') {
		// Force content_type here as facebook return text/plain, but in form urlencoded format
		if ($for_method == 'get_access_token') {
			$response['content_type'] = 'application/x-www-form-urlencoded';
		}
		return parent::_decode_result($result, $response, $for_method);
	}
}