<?php

load('oauth_driver2', '', 'classes/oauth/');
class yf_oauth_driver_wordpress extends yf_oauth_driver2 {

	protected $url_authorize = 'https://public-api.wordpress.com/oauth2/authorize';
	protected $url_access_token = 'https://public-api.wordpress.com/oauth2/token';
	protected $url_user = 'https://public-api.wordpress.com/rest/v1/me';
	public $scope = '';
	public $get_access_token_method = 'POST';
	public $get_user_info_user_bearer = true;
	public $url_params_access_token = [
		'grant_type'	=> 'authorization_code',
	];

	/**
	*/
	function _get_user_info_for_auth($raw = []) {
		$user_info = [
			'user_id'		=> $raw['ID'],
			'login'			=> $raw['username'],
			'name'			=> $raw['display_name'],
			'email'			=> $raw['email'],
			'avatar_url'	=> $raw['avatar_URL'],
			'profile_url'	=> $raw['profile_URL'],
		];
		return $user_info;
	}
}
