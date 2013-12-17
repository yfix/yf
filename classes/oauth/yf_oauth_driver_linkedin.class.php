<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_linkedin extends yf_oauth_driver2 {

	protected $url_authorize = 'https://www.linkedin.com/uas/oauth2/authorization';
	protected $url_access_token = 'https://www.linkedin.com/uas/oauth2/accessToken';
	protected $url_user = 'https://api.linkedin.com/v1/people/~:(id,first-name,last-name,formatted-name,picture-url,public-profile-url,email-address,location:(name),industry,headline)';
	protected $scope = '';
	protected $get_access_token_method = 'POST';
	protected $url_params_access_token = array(
		'grant_type'	=> 'authorization_code',
	);
// TODO
#	protected $http_headers_add = array(
#		'x-li-format: json',
#	);

	/**
	*/
	function _get_user_info_for_auth($raw = array()) {
		$user_info = array(
			'user_id'		=> $raw['id'],
			'login'			=> $raw['emailAddress'],
			'name'			=> $raw['formattedName'],
			'email'			=> $raw['emailAddress'],
			'avatar_url'	=> $raw['pictureUrl'], // Can be empty
			'profile_url'	=> $raw['publicProfileUrl'],
		);
		return $user_info;
	}

	/**
	*/
	function get_user_info() {
		$access_token = $this->_storage_get('access_token');
		if (!$access_token) {
			$access_token = $this->get_access_token();
			if (!$access_token) {
				$this->_storage_clean();
				js_redirect( $this->redirect_uri, $url_rewrite = false );
				return false;
			}
		}
		if (!$this->_storage_get('user')) {
			$url = $this->url_user.'?'.http_build_query(array(
				'oauth2_access_token'	=> $access_token,
			));
			$opts['custom_header'][] = 'x-li-format: json';

			$result = common()->get_remote_page($url, $cache = false, $opts, $response);
			$result = $this->_decode_result($result, $response, __FUNCTION__);
			if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
				$this->_storage_clean();
				js_redirect( $this->redirect_uri, $url_rewrite = false );
				return false;
			} else {
				$this->_storage_set('user_info_request', array('result' => $result, 'response' => $response));
				$this->_storage_set('user', $result);
			}
		}
		return $this->_storage_get('user');
	}
}