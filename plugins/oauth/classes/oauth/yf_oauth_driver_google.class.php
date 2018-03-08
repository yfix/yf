<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_google extends yf_oauth_driver2 {

	// Register for API client_id and client_secret here: https://cloud.google.com/console#/project

	protected $url_authorize = 'https://accounts.google.com/o/oauth2/auth';
	protected $url_access_token = 'https://accounts.google.com/o/oauth2/token';
	protected $url_user = 'https://www.googleapis.com/oauth2/v1/userinfo';
#	protected $url_user = 'https://www.googleapis.com/plus/v1/people/me';
	public $scope = 'email profile https://www.googleapis.com/auth/plus.login';
	public $get_access_token_method = 'POST';
	public $url_params_access_token = [
		'grant_type'	=> 'authorization_code',
	];

	/**
	*/
	function _get_user_info_for_auth($raw = []) {
		$user_info = [
			'user_id'		=> $raw['id'],
			'login'			=> $raw['email'],
			'name'			=> $raw['name'],
			'email'			=> $raw['email'],
			'avatar_url'	=> $raw['picture'],
			'profile_url'	=> $raw['link'],
			'gender'		=> $raw['gender'],
			'locale'		=> $raw['locale'],
		];
		return $user_info;
	}
}
