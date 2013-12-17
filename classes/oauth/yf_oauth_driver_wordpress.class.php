<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_wordpress extends yf_oauth_driver2 {

	protected $url_authorize = 'https://public-api.wordpress.com/oauth2/authorize';
	protected $url_access_token = 'https://public-api.wordpress.com/oauth2/token';
	protected $url_user = 'https://public-api.wordpress.com/rest/v1/me';
	protected $scope = '';
	protected $get_access_token_method = 'POST';
	protected $get_user_info_user_bearer = true;
	protected $url_params_access_token = array(
		'grant_type'	=> 'authorization_code',
	);

	/**
	*/
	function _get_user_info_for_auth($raw = array()) {
		$user_info = array(
			'user_id'		=> $raw['ID'],
			'login'			=> $raw['username'],
			'name'			=> $raw['display_name'],
			'email'			=> $raw['email'],
			'avatar_url'	=> $raw['avatar_URL'],
			'profile_url'	=> $raw['profile_URL'],
		);
		return $user_info;
	}
}